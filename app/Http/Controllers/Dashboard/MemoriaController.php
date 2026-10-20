<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Models\TarjetaNivel;
use App\Repositories\EstudianteRepository;
use App\Services\LogroService;
use App\Services\MemoriaService;
use App\Services\SesionService;
use App\Support\DesbloqueoNiveles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: MemoriaController
 * =========================================================================
 * Gestiona el flujo completo del juego "Encuentra el par".
 *
 * Reutiliza SesionService para todo lo genérico (crear/cerrar sesión,
 * actualizar racha) y LogroService para medallas — ambos ya compatibles
 * porque operan sobre SesionJuego/Racha, tablas genéricas a cualquier juego.
 *
 * FLUJO:
 *   1. iniciarSesion()      → crea SesionJuego, redirige al tablero
 *   2. mostrarTablero()     → genera el mazo y renderiza el juego completo
 *   3. registrarMovimiento()→ AJAX, cada vez que el estudiante voltea un par
 *   4. cerrarSesion()       → AJAX, al completar todas las parejas o agotar tiempo
 *   5. Redirige a estudiante.sesion.resumen (vista YA EXISTENTE, reutilizada)
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Sección 11
 * =========================================================================
 */
class MemoriaController extends Controller
{
    protected $memoriaService;
    protected $sesionService;
    protected $logroService;
    protected $estudianteRepo;

    public function __construct(
        MemoriaService       $memoriaService,
        SesionService        $sesionService,
        LogroService         $logroService,
        EstudianteRepository $estudianteRepo
    ) {
        $this->memoriaService = $memoriaService;
        $this->sesionService  = $sesionService;
        $this->logroService   = $logroService;
        $this->estudianteRepo = $estudianteRepo;
    }

    /**
     * Inicia una nueva sesión del juego de memoria.
     * POST /estudiante/memoria/{juego}/nivel/{nivel}/iniciar
     */
    public function iniciarSesion(Request $request, Juego $juego, Nivel $nivel): RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        // Egresado (sin matrícula activa): ya no juega, solo ve su progreso.
        if (!$estudiante->matriculaActiva) {
            return redirect()->route('estudiante.progreso')
                ->with('cambio_nivel', '🎓 ¡Terminaste tus grados! Ya no se puede jugar, pero podés ver tu progreso.');
        }

        if ($nivel->juego_id !== $juego->id) {
            return redirect()->route('estudiante.juegos');
        }

        // Nivel con candado: se abre superando el anterior.
        if (!DesbloqueoNiveles::estaAbierto($estudiante->id, $nivel)) {
            return redirect()->route('estudiante.juegos')
                ->with('cambio_nivel', '🔒 Ese nivel todavía está cerrado. ¡Superá el anterior para abrirlo!');
        }

        $deviceTipo = $this->detectarDispositivo($request);

        $sesion = $this->sesionService->iniciar(
            $estudiante->id, $juego->id, $nivel->id, $deviceTipo
        );

        return redirect()->route('estudiante.memoria.tablero', $sesion->id);
    }

    /**
     * Muestra el tablero completo del juego con el mazo generado.
     * GET /estudiante/memoria/sesion/{sesion}/tablero
     */
    public function mostrarTablero(SesionJuego $sesion): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return redirect()->route('estudiante.juegos');
        }

        $nivel = $sesion->nivel;
        $mazo  = $this->memoriaService->generarMazo($nivel);

        return view('estudiante.memoria-tablero', [
            'sesion'          => $sesion,
            'nivel'           => $nivel,
            'juego'           => $sesion->juego,
            'mazo'            => $mazo,
            'totalPares'      => $nivel->total_ejercicios,
            'tiempoLimiteSeg' => $nivel->tiempo_limite_seg ?? 90,
            'bonusTiempoSeg'  => MemoriaService::BONUS_TIEMPO_SEG,
        ]);
        }

    /**
     * Registra un intento de par (2 cartas volteadas).
     * POST /estudiante/memoria/sesion/{sesion}/movimiento — AJAX
     */
    public function registrarMovimiento(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        $datos = $request->validate([
            'tarjeta_a_id' => ['required', 'integer', 'exists:tarjetas_nivel,id'],
            'tarjeta_b_id' => ['required', 'integer', 'exists:tarjetas_nivel,id'],
            'tiempo_ms'    => ['nullable', 'integer', 'min:0', 'max:60000'],
        ]);

        $resultado = $this->memoriaService->registrarMovimiento(
            $sesion, $datos['tarjeta_a_id'], $datos['tarjeta_b_id'], $datos['tiempo_ms'] ?? null
        );

        // Actualizar racha (reutiliza la lógica genérica de SesionService)
        $diasRacha = 3; // default; podría consultarse config_institucion si se requiere
        $racha = $this->sesionService->actualizarRacha(
            $estudiante->id, $sesion->juego_id, $resultado['es_acierto'], $diasRacha
        );

        // CAMBIO DE REGLA (a pedido explícito): "Encuentra el par" ya NO usa
        // la racha 3/2 de aciertos/errores consecutivos DENTRO de la
        // partida para decidir el nivel. En un tablero chico (el Nivel 1
        // tiene solo 3 pares) esa regla es casi imposible de cumplir — un
        // solo error temprano, aunque el estudiante termine ganando el
        // tablero, ya arruina la racha para siempre en esa partida.
        //
        // Ahora la regla es por PARTIDA COMPLETA, no por movimiento:
        //   - Ganó la partida (completó el tablero a tiempo) → sube de nivel
        //   - Perdió la partida (se acabó el tiempo)          → baja de nivel
        // Esa decisión se toma una sola vez, al cerrar la sesión (ver
        // cerrarSesion() más abajo) — nunca acá, a mitad de juego. Por eso
        // este método ya no evalúa ni actúa sobre ningún cambio de nivel:
        // solo registra el movimiento y devuelve el puntaje. Esto también
        // elimina de raíz la condición de carrera que causaba que la
        // sesión se cerrara dos veces por caminos distintos.
        $respuesta = [
            'es_acierto'      => $resultado['es_acierto'],
            'puntos'          => $resultado['puntos'],
            'bonus_tiempo_seg'=> $resultado['es_acierto'] ? MemoriaService::BONUS_TIEMPO_SEG : 0,
        ];

        return response()->json($respuesta);
    }

    /**
     * Cierra la sesión al completar todas las parejas o agotarse el tiempo.
     * POST /estudiante/memoria/sesion/{sesion}/cerrar — AJAX
     */
    public function cerrarSesion(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        // CORRECCIÓN (respaldo adicional al bug "no guarda el progreso y
        // vuelve a la lista de juegos"): si esta sesión YA fue cerrada por
        // otro camino (por ejemplo, un cambio de nivel disparado por el
        // último movimiento, en algún caso no cubierto por la corrección
        // de registrarMovimiento), no lo tratamos como un error. El
        // estudiante ya jugó y terminó el tablero, así que lo mandamos
        // igual a ver su resumen en vez de devolverlo a la lista de
        // juegos con las manos vacías.
        if (!$sesion->estaEnCurso()) {
            return response()->json([
                'redirect_url' => route('estudiante.sesion.resumen', $sesion->id),
            ]);
        }

        $datos = $request->validate([
            'completado_a_tiempo' => ['required', 'boolean'],
        ]);

        $gano = $datos['completado_a_tiempo'];

        $sesionCerrada = $this->sesionService->cerrar($sesion, $gano);

        // Evaluar logros (reutiliza LogroService sin cambios — CU-07)
        $racha = $this->estudianteRepo->rachaDelJuego($estudiante->id, $sesion->juego_id);
        $logrosNuevos = $racha
            ? $this->logroService->evaluar($sesionCerrada, $racha)
            : collect();

        // NUEVA regla de nivel (2026-09-29, a pedido explícito): ya no
        // decide una sola partida — ahora hace falta una RACHA de varias
        // partidas seguidas con el mismo resultado (3 ganadas para subir,
        // 2 perdidas para bajar), para que el nivel no cambie tan rápido y
        // sea menos estresante. Ver MemoriaService::evaluarRachaPorPartida()
        // para el detalle completo de la regla.
        $evaluacion = $this->memoriaService->evaluarRachaPorPartida($sesionCerrada, $gano);
        $direccion  = $evaluacion['direccion'];

        // Por defecto se queda en el mismo nivel (todavía no se completó
        // la racha necesaria). Si no hay nivel siguiente/anterior
        // (extremos de la progresión: Nivel 1 o Nivel 10), también se
        // repite el mismo nivel.
        $siguienteNivelId     = $sesionCerrada->nivel_id;
        $siguienteNivelNumero = $sesionCerrada->nivel?->numero_nivel;
        $urlIniciarSiguiente  = null; // solo si el nivel realmente cambia
        if ($direccion !== null) {
            $nivelAdyacente = $this->memoriaService->buscarNivelAdyacente($sesionCerrada->nivel, $direccion);
            if ($nivelAdyacente) {
                // Si la regla manda al niño a ese nivel, tiene que poder entrar:
                // se abre (por ejemplo, si la docente había abierto solo un nivel alto).
                DesbloqueoNiveles::abrir($estudiante->id, $nivelAdyacente);
                $siguienteNivelId     = $nivelAdyacente->id;
                $siguienteNivelNumero = $nivelAdyacente->numero_nivel;

                // Cuando el nivel cambia, el tablero salta el resumen y arranca
                // directamente el nivel nuevo (a pedido de la autora: "al cambiar
                // de nivel prefiero saltar el resumen y pasar directo al siguiente").
                $urlIniciarSiguiente = route('estudiante.memoria.iniciar', [
                    'juego' => $sesionCerrada->juego_id,
                    'nivel' => $nivelAdyacente->id,
                ]);
            }
        }

        // Medallas ganadas en esta partida, para mostrarlas en el aviso de
        // transición (si se salta el resumen, ahí es la única oportunidad de verlas).
        $medallas = $logrosNuevos->map(function ($lo) {
            $lo->loadMissing('logro');
            return [
                'emoji'  => $lo->logro?->emoji ?: '🏅',
                'nombre' => $lo->logro?->nombre_es ?? '',
            ];
        })->filter(fn ($m) => $m['nombre'] !== '')->values()->all();

        // Las medallas ganadas se muestran en el resumen (igual que en los otros
        // juegos). Se guardan en la sesión para la próxima pantalla.
        // Si se salta el resumen, no hace falta guardarlas para esa pantalla.
        if ($urlIniciarSiguiente === null) {
            session()->flash('logros_nuevos', $logrosNuevos->pluck('logro_id')->toArray());
        }

        return response()->json([
            'redirect_url'       => route('estudiante.sesion.resumen', $sesionCerrada->id),
            'siguiente_nivel_id' => $siguienteNivelId,
            'siguiente_nivel_numero' => $siguienteNivelNumero,
            'cambio_nivel'       => $direccion,              // 'sube' | 'baja' | null
            'racha_consecutivas' => $evaluacion['consecutivas'],
            'racha_meta'         => $evaluacion['meta'],
            // Si no es null: el tablero salta el resumen y hace POST aquí para jugar el nivel nuevo.
            'iniciar_url'        => $urlIniciarSiguiente,
            'medallas'           => $medallas,
        ]);
    }

    private function detectarDispositivo(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) return 'mobile';
        if (str_contains($ua, 'ipad')   || str_contains($ua, 'tablet'))  return 'tablet';
        return 'desktop';
    }
}
