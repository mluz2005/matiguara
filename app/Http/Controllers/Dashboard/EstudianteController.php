<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\LogroRepository;
use App\Repositories\SesionRepository;
use App\Services\LogroService;
use App\Services\MatriculaService;
use App\Services\MemoriaService;
use App\Services\RetroalimentacionService;
use App\Services\SesionService;
use App\Services\SistemaAdaptativoService;
use App\Support\DesbloqueoNiveles;
use App\Support\FrutasJuego;
use App\Support\PistasSumaFrutas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


/**
 * CONTROLADOR: EstudianteController — Semana 7 (completo)
 * =========================================================================
 * Agrega funcionalidad real para:
 *   miProgreso() → vista con progreso por nivel y juego
 *   misLogros()  → vista con medallas obtenidas y disponibles
 *   procesarRespuesta() → integra LogroService al cerrar sesión
 * =========================================================================
 */
class EstudianteController extends Controller
{
    protected $sesionService;
    protected $adaptativoService;
    protected $retroService;
    protected $logroService;
    protected $sesionRepo;
    protected $ejercicioRepo;
    protected $estudianteRepo;
    protected $juegoRepo;
    protected $logroRepo;
    protected $matriculaService;

    public function __construct(
    SesionService            $sesionService,
    SistemaAdaptativoService $adaptativoService,
    RetroalimentacionService $retroService,
    LogroService             $logroService,
    SesionRepository         $sesionRepo,
    EjercicioRepository      $ejercicioRepo,
    EstudianteRepository     $estudianteRepo,
    JuegoRepository          $juegoRepo,
    LogroRepository          $logroRepo,
    MatriculaService         $matriculaService
) {
    $this->sesionService      = $sesionService;
    $this->adaptativoService  = $adaptativoService;
    $this->retroService       = $retroService;
    $this->logroService       = $logroService;
    $this->sesionRepo         = $sesionRepo;
    $this->ejercicioRepo      = $ejercicioRepo;
    $this->estudianteRepo     = $estudianteRepo;
    $this->juegoRepo          = $juegoRepo;
    $this->logroRepo          = $logroRepo;
    $this->matriculaService   = $matriculaService;
}

    // =========================================================================
    // SELECCIÓN DE JUEGO
    // =========================================================================

    public function seleccionJuego(): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        $matricula  = $estudiante->matriculaActiva;

        // Egresado (matrícula cerrada): ya no juega, pero conserva su progreso hasta la baja.
        if (!$matricula) {
            return $this->sinMatriculaActiva();
        }

        $juegosBloqueados = $matricula
            ? $this->juegoRepo->idsDeshabilitadosPorDocente($matricula->docente_id ?? 0, $matricula->grado)
            : [];

        $juegos = $this->juegoRepo->activosExcepto($juegosBloqueados);

        $rachas = $this->estudianteRepo->rachasPorJuego($estudiante->id, $juegos->pluck('id'));

        // Niveles que el niño ya tiene abiertos (los demás se ven con candado)
        $nivelesAbiertos = DesbloqueoNiveles::idsAbiertos($estudiante->id);

        return view('estudiante.juegos', compact('estudiante','matricula','juegos','rachas','nivelesAbiertos'));
    }

    // =========================================================================
    // FLUJO DE SESIÓN
    // =========================================================================

    public function iniciarSesion(Request $request, Juego $juego, Nivel $nivel): RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if (!$estudiante->matriculaActiva) {
            return $this->sinMatriculaActiva();
        }

        if ($nivel->juego_id !== $juego->id) {
            return redirect()->route('estudiante.juegos');
        }

        // Nivel con candado: se abre superando el anterior.
        if (!DesbloqueoNiveles::estaAbierto($estudiante->id, $nivel)) {
            return redirect()->route('estudiante.juegos')
                ->with('cambio_nivel', '🔒 Ese nivel todavía está cerrado. ¡Superá el anterior para abrirlo!');
        }

        $sesion = $this->sesionService->iniciar(
            $estudiante->id, $juego->id, $nivel->id,
            $this->detectarDispositivo($request)
        );

        // Se crea el registro de progreso si no existía. NO se suma la partida acá:
        // se cuenta una sola vez, al terminarla (SesionService::actualizarProgreso).
        // (Antes se sumaba en los dos lugares y el contador mostraba el doble.)
        $this->estudianteRepo->asegurarProgreso(
            $estudiante->id, $juego->id, $nivel->id, $nivel->grado
        );

        return redirect()->route('estudiante.sesion.ejercicio', $sesion->id);
    }

    public function mostrarEjercicio(SesionJuego $sesion): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        if ($sesion->estudiante_id !== $estudiante->id) return redirect()->route('estudiante.juegos');
        if (!$sesion->estaEnCurso()) return redirect()->route('estudiante.sesion.resumen', $sesion->id);

        $ejercicio = $this->sesionService->obtenerProximoEjercicio($sesion);
        if (!$ejercicio) {
            $sesionCerrada = $this->sesionService->cerrar($sesion, false);
            return redirect()->route('estudiante.sesion.resumen', $sesionCerrada->id);
        }

        $nivel           = $sesion->nivel;

        // Segunda oportunidad (Suma de Frutas): el mismo ejercicio vuelve a mostrarse con una pista.
        // El ejercicio fallado todavía no cuenta como respondido ni se repite la opción ya elegida.
        $esReintento     = $this->sesionService->esReintento($sesion, $ejercicio);
        $pista           = $esReintento ? PistasSumaFrutas::para($ejercicio) : null;

        $respondidos     = $this->sesionRepo->contarEjerciciosRespondidos($sesion) - ($esReintento ? 1 : 0);
        $totalEjercicios = $nivel->total_ejercicios;
        $porcentaje      = $totalEjercicios > 0 ? round(($respondidos / $totalEjercicios) * 100) : 0;
        $opciones        = collect($ejercicio->opciones_json ?? [])->shuffle()->values();

        if ($esReintento) {
            $fallida  = $this->sesionRepo->respuestaDelPrimerIntento($sesion, $ejercicio->id);
            $opciones = $opciones->reject(fn ($o) => (string) $o === (string) $fallida)->values();
        }

        $puntaje  = $sesion->puntaje_obtenido;
        $aciertos = $sesion->aciertos;

        // Suma de Frutas: en vez de "3 + 5" se muestran las frutas (ver
        // App\Support\FrutasJuego). Los demás juegos no cambian: $escena = null.
        $escena = $sesion->juego->nombre_es === 'Suma de Frutas'
            ? FrutasJuego::escena($ejercicio, $sesion, $respondidos)
            : null;

        return view('estudiante.ejercicio', compact(
            'sesion','ejercicio','nivel','opciones',
            'respondidos','totalEjercicios','porcentaje',
            'puntaje','aciertos','escena','esReintento','pista'
        ));
    }

    public function procesarRespuesta(Request $request, SesionJuego $sesion): RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return redirect()->route('estudiante.juegos');
        }

        $datos = $request->validate([
            'contenido_id' => ['required','integer','exists:contenido_nivel,id'],
            'respuesta'    => ['required','string','max:50'],
            'tiempo_ms'    => ['nullable','integer','min:0','max:300000'],
        ]);

        $ejercicio = $this->ejercicioRepo->buscar((int) $datos['contenido_id']);
        $resultado = $this->sesionService->registrarRespuesta(
            $sesion, $ejercicio, $datos['respuesta'], $datos['tiempo_ms'] ?? null
        );

        // Primer fallo en Suma de Frutas: se vuelve a mostrar el mismo ejercicio con una pista.
        // No cuenta todavía para la racha ni para la regla 3/2 (cuenta el resultado final del ejercicio).
        if ($resultado['reintento_pendiente']) {
            $progresoPrimero = $this->retroService->obtenerProgreso(
                $estudiante->id, $sesion->juego_id, $sesion->nivel_id, $sesion->nivel->grado
            );
            $mensajePrimero = $this->retroService->obtenerYRegistrar('error', $progresoPrimero, true, false);

            return redirect()->route('estudiante.sesion.ejercicio', $sesion->id)
                ->with('ultimo_resultado', [
                    'es_correcto' => false,
                    'puntos'      => 0,
                    'mensaje_es'  => $mensajePrimero?->texto_es,
                    'mensaje_gn'  => $mensajePrimero?->texto_gn,
                    'reintento'   => true,
                ]);
        }

        $esCorrect    = $resultado['es_correcto'];
        $esPrimerInto = $resultado['numero_intento'] === 1;

        $diasRacha = $this->adaptativoService->obtenerDiasRacha($sesion->juego, $estudiante);
        $racha     = $this->sesionService->actualizarRacha($estudiante->id, $sesion->juego_id, $esCorrect, $diasRacha);

        $sesion->refresh();
        $evaluacion = $this->adaptativoService->evaluar($sesion);

        $maximoRacha = $racha->racha_actual_aciertos > 0
            && $racha->racha_actual_aciertos === $racha->racha_maxima_aciertos
            && $racha->racha_actual_aciertos > 1;

        $nivel   = $sesion->nivel;
        $progreso = $this->retroService->obtenerProgreso(
            $estudiante->id, $sesion->juego_id, $sesion->nivel_id, $nivel->grado
        );
        $mensaje = $this->retroService->obtenerYRegistrar(
            $evaluacion['mensaje_tipo'], $progreso, $esPrimerInto, $maximoRacha
        );

        $ultimoResultado = [
            'es_correcto' => $esCorrect,
            'puntos'      => $resultado['puntos'],
            'mensaje_es'  => $mensaje?->texto_es,
            'mensaje_gn'  => $mensaje?->texto_gn,
        ];

        // ── Terminar sesión ───────────────────────────────────────────────
        if ($this->sesionService->debeTerminar($sesion, $nivel)) {
            $nivelSuperado  = $evaluacion['tipo_cambio'] === 'sube' || $sesion->aciertos >= $nivel->min_aciertos_pasar;
            $sesionCerrada  = $this->sesionService->cerrar($sesion, $nivelSuperado);

            // ── Evaluar y otorgar logros ──────────────────────────────────
            $logrosNuevos = $this->logroService->evaluar($sesionCerrada, $racha);

            return redirect()->route('estudiante.sesion.resumen', $sesionCerrada->id)
                ->with('ultimo_resultado', $ultimoResultado)
                ->with('logros_nuevos', $logrosNuevos->pluck('logro_id')->toArray());
        }

        // ── Cambio de nivel por algoritmo 3/2 ────────────────────────────
        if ($evaluacion['cambio_nivel'] && $evaluacion['nivel_nuevo']) {
            $nivelNuevo    = $evaluacion['nivel_nuevo'];
            $sesionCerrada = $this->sesionService->cerrar($sesion, $evaluacion['tipo_cambio'] === 'sube');
            $this->logroService->evaluar($sesionCerrada, $racha);

            $sesionNueva = $this->sesionService->iniciar(
                $estudiante->id, $sesion->juego_id, $nivelNuevo->id, $sesion->device_tipo
            );

            $msgCambio = $evaluacion['tipo_cambio'] === 'sube'
                ? "🎉 ¡Subiste al nivel {$nivelNuevo->numero_nivel}!"
                : "🔄 Vamos al nivel {$nivelNuevo->numero_nivel} para practicar más.";

            return redirect()->route('estudiante.sesion.ejercicio', $sesionNueva->id)
                ->with('cambio_nivel', $msgCambio)
                ->with('ultimo_resultado', $ultimoResultado);
        }

        return redirect()->route('estudiante.sesion.ejercicio', $sesion->id)
            ->with('ultimo_resultado', $ultimoResultado);
    }

    public function resumenSesion(SesionJuego $sesion): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        if ($sesion->estudiante_id !== $estudiante->id) return redirect()->route('estudiante.juegos');

        $total  = $sesion->aciertos + $sesion->errores;
        $pctOk  = $total > 0 ? round(($sesion->aciertos / $total) * 100) : 0;
        $racha  = $this->estudianteRepo->rachaDelJuego($estudiante->id, $sesion->juego_id);

        // Logros obtenidos en esta sesión
        $logrosNuevosIds = session('logros_nuevos', []);
        $logrosNuevos    = $this->logroRepo->obtenidosPorIds($estudiante->id, $logrosNuevosIds);

        // "Encuentra el par": el botón principal del resumen respeta la MISMA regla
        // que usa MemoriaController al cerrar la partida (2026-09-29):
        //   3 partidas ganadas seguidas en el nivel → sube · 2 perdidas seguidas → baja
        //   cualquier otro caso → repite el mismo nivel.
        // (Antes este cálculo seguía la regla vieja "ganó = sube, perdió = baja" y
        // la vista ni siquiera lo usaba: el botón repetía siempre el mismo nivel.)
        // Los otros juegos no cambian: repiten el mismo nivel.
        $nivelParaJugarDeNuevo = $sesion->nivel_id;
        $cambioNivel           = null;
        $nivelDestino          = $sesion->nivel;
        if ($sesion->juego->tipoMecanica?->tipo === 'memoria' && $sesion->estado === 'completada') {
            $memoria    = app(MemoriaService::class);
            $evaluacion = $memoria->evaluarRachaPorPartida($sesion, (bool) $sesion->nivel_superado);
            if ($evaluacion['direccion'] !== null) {
                $adyacente = $memoria->buscarNivelAdyacente($sesion->nivel, $evaluacion['direccion']);
                if ($adyacente) {
                    $cambioNivel           = $evaluacion['direccion'];
                    $nivelDestino          = $adyacente;
                    $nivelParaJugarDeNuevo = $adyacente->id;
                }
            }
        }

        // BOTÓN "Siguiente nivel": si superó el nivel, el siguiente ya quedó abierto
        // (ver SesionService::actualizarProgreso). Se le ofrece jugarlo ahora en vez
        // de obligarlo a volver al menú. No se muestra si no hay nivel siguiente
        // (último nivel) o si el botón principal ya lleva a ese mismo nivel.
        $siguienteNivel = null;
        if ($sesion->nivel_superado) {
            $candidato = $sesion->nivel?->nivelSiguiente();
            if ($candidato
                && (int) $candidato->id !== (int) $nivelParaJugarDeNuevo
                && DesbloqueoNiveles::estaAbierto($estudiante->id, $candidato)) {
                $siguienteNivel = $candidato;
            }
        }

        return view('estudiante.resumen', [
            'sesion'                => $sesion,
            'estudiante'            => $estudiante,
            'pctOk'                 => $pctOk,
            'racha'                 => $racha,
            'logrosNuevos'          => $logrosNuevos,
            'duracion'              => $sesion->duracionSegundos(),
            'nivelParaJugarDeNuevo' => $nivelParaJugarDeNuevo,
            'cambioNivel'           => $cambioNivel,
            'nivelDestino'          => $nivelDestino,
            'siguienteNivel'        => $siguienteNivel,
        ]);
    }

    // =========================================================================
    // PROGRESO Y LOGROS
    // =========================================================================

    /**
     * Vista de progreso real del estudiante por juego y nivel.
     */
    public function miProgreso(): View
    {
        $estudiante = Auth::guard('estudiante')->user();

        // RF-17: se ve el grado activo; los anteriores solo si la institución lo habilita.
        $vista      = $this->matriculaService->gradoParaProgreso($estudiante, ((int) request("grado")) ?: null);
        $grado      = $vista['grado'];
        $gradoActual = $vista['actual'];
        $anteriores = $vista['anteriores'];
        $egresado   = $vista['activa'] === null;

        $progresos = $this->estudianteRepo->progresosDelGrado($estudiante->id, $grado);

        $totalSesiones = $this->sesionRepo->contarCompletadas($estudiante->id);
        $puntajeTotal  = $this->sesionRepo->puntajeTotalCompletadas($estudiante->id);
        $rachaMax      = $this->estudianteRepo->rachaActualMaximaEnDias($estudiante);

        return view('estudiante.progreso', compact(
            'estudiante','progresos','totalSesiones','puntajeTotal','rachaMax',
            'grado','gradoActual','anteriores','egresado'
        ));
    }

    /**
     * Vista de logros y medallas obtenidas.
     */
    public function misLogros(): View
    {
        $estudiante = Auth::guard('estudiante')->user();

        $logrosObtenidos = $this->logroRepo->obtenidosDe($estudiante->id);

        $idsObtenidos   = $logrosObtenidos->pluck('logro_id')->toArray();
        $logrosPendientes = $this->logroRepo->pendientes($idsObtenidos);

        $totalLogros = $this->logroRepo->contarActivos();

        return view('estudiante.logros', compact(
            'estudiante','logrosObtenidos','logrosPendientes','totalLogros'
        ));
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /** Aviso y salida para el estudiante que ya no tiene matrícula activa (egresó). */
    private function sinMatriculaActiva(): RedirectResponse
    {
        return redirect()->route('estudiante.progreso')
            ->with('cambio_nivel', '🎓 ¡Terminaste tus grados! Ya no se puede jugar, pero podés ver tu progreso.');
    }

    private function detectarDispositivo(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        if (str_contains($ua,'mobile') || str_contains($ua,'android')) return 'mobile';
        if (str_contains($ua,'ipad')   || str_contains($ua,'tablet'))  return 'tablet';
        return 'desktop';
    }
}
