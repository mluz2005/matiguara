<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\LaberintoRepository;
use App\Services\LaberintoService;
use App\Services\SesionService;
use App\Services\TiempoJuegoService;
use App\Support\DesbloqueoNiveles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * CONTROLADOR: LaberintoController — «El camino a la escuela»
 * =========================================================================
 * Flujo (igual de simple que «Encuentra el par»):
 *   1. iniciarSesion()        → crea la partida y su semilla, redirige al tablero
 *   2. mostrarTablero()       → arma el laberinto (semilla + ejercicios de cada obstáculo)
 *   3. registrarMovimiento()  → AJAX, una respuesta a un ejercicio de un obstáculo
 *   4. cerrarSesion()         → AJAX, al llegar a la escuela: estrellas, logros, regla 3/2
 *   5. el niño ve estudiante.sesion.resumen (vista compartida con los otros juegos)
 *
 * El grado sale de la matrícula del alumno (la docente lo define al matricular):
 * el niño solo ve y juega los niveles de su grado. El límite de tiempo de juego
 * lo aplica el middleware 'limite.juego' en iniciar y tablero.
 * =========================================================================
 */
class LaberintoController extends Controller
{
    /** Personajes del niño en el laberinto (se asigna uno fijo por alumno). */
    private const AVATARES = ['👧🏽', '👦🏽', '🧒🏽', '👧🏻', '👦🏻', '👧🏿', '👦🏿', '🧒🏻'];

    public function __construct(
        private LaberintoService $laberinto,
        private LaberintoRepository $detalles,
        private SesionService $sesionService,
        private EstudianteRepository $estudianteRepo,
        private TiempoJuegoService $tiempo
    ) {
    }

    /** POST /estudiante/laberinto/{juego}/nivel/{nivel}/iniciar */
    public function iniciarSesion(Request $request, Juego $juego, Nivel $nivel): RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        $matricula  = $estudiante->matriculaActiva;

        // Egresado (sin matrícula activa): ya no juega, solo ve su progreso.
        if (!$matricula) {
            return redirect()->route('estudiante.progreso')
                ->with('cambio_nivel', '🎓 ¡Terminaste tus grados! Ya no se puede jugar, pero podés ver tu progreso.');
        }

        if ($nivel->juego_id !== $juego->id) {
            return redirect()->route('estudiante.juegos');
        }

        // El grado lo define la matrícula: no se juega un nivel de otro grado.
        if ((int) $nivel->grado !== (int) $matricula->grado) {
            return redirect()->route('estudiante.juegos');
        }

        // Nivel con candado: se abre superando el anterior.
        if (!DesbloqueoNiveles::estaAbierto($estudiante->id, $nivel)) {
            return redirect()->route('estudiante.juegos')
                ->with('cambio_nivel', '🔒 Ese nivel todavía está cerrado. ¡Superá el anterior para abrirlo!');
        }

        $sesion = $this->sesionService->iniciar(
            $estudiante->id, $juego->id, $nivel->id, $this->detectarDispositivo($request)
        );
        $this->estudianteRepo->asegurarProgreso($estudiante->id, $juego->id, $nivel->id, $nivel->grado);
        $this->detalles->crearDetalle($sesion->id, $this->laberinto->nuevaSemilla(), (int) $nivel->total_ejercicios);

        return redirect()->route('estudiante.laberinto.tablero', $sesion->id);
    }

    /** GET /estudiante/laberinto/sesion/{sesion}/tablero */
    public function mostrarTablero(SesionJuego $sesion): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id) {
            return redirect()->route('estudiante.juegos');
        }
        if (!$sesion->estaEnCurso()) {
            return $sesion->estado === 'completada'
                ? redirect()->route('estudiante.sesion.resumen', $sesion->id)
                : redirect()->route('estudiante.juegos');
        }

        $detalle = $this->detalles->detalleDe($sesion->id)
            ?? $this->detalles->crearDetalle($sesion->id, $this->laberinto->nuevaSemilla(), (int) $sesion->nivel->total_ejercicios);

        try {
            $obstaculos = $this->laberinto->obstaculosDe($sesion);
        } catch (RuntimeException $e) {
            report($e);
            return redirect()->route('estudiante.juegos')
                ->with('cambio_nivel', 'Este nivel todavía no está listo. Avisale a tu maestra.');
        }

        $nivel  = $sesion->nivel;
        $estado = $this->tiempo->estado($estudiante);

        $config = [
            'sesionId'     => $sesion->id,
            'grado'        => (int) $nivel->grado,
            'nivel'        => (int) $nivel->numero_nivel,
            'semilla'      => (int) $detalle->semilla,
            'avatar'       => self::AVATARES[$estudiante->id % count(self::AVATARES)],
            'obstaculos'   => $obstaculos,
            'limiteSeg'    => (int) $estado['limite_seg'],
            'restanteSeg'  => (int) $estado['restante_seg'],
            'urls' => [
                'movimiento' => route('estudiante.laberinto.movimiento', $sesion->id),
                'cerrar'     => route('estudiante.laberinto.cerrar', $sesion->id),
                'descanso'   => route('estudiante.descanso'),
                'juegos'     => route('estudiante.juegos'),
            ],
            'imgs' => [
                'cara'    => asset('img/guara/guara-cara.webp'),
                'festeja' => asset('img/guara/guara-festeja.webp'),
                'guino'   => asset('img/guara/guara-guino.webp'),
                'mira'    => asset('img/guara/guara-mira.webp'),
                'piensa'  => asset('img/guara/guara-piensa.webp'),
            ],
            'snds' => [
                'ok'  => asset('audio/efectos/memoria_acierto.mp3'),
                'bad' => asset('audio/efectos/memoria_error.mp3'),
            ],
        ];

        return view('estudiante.laberinto-tablero', [
            'sesion' => $sesion,
            'nivel'  => $nivel,
            'juego'  => $sesion->juego,
            'config' => $config,
        ]);
    }

    /** POST /estudiante/laberinto/sesion/{sesion}/movimiento — AJAX */
    public function registrarMovimiento(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        $datos = $request->validate([
            'contenido_id' => ['required', 'integer', 'exists:contenido_nivel,id'],
            'respuesta'    => ['required', 'string', 'max:20'],
            'tiempo_ms'    => ['nullable', 'integer', 'min:0', 'max:600000'],
            'intento'      => ['nullable', 'integer', 'min:0', 'max:5'],
        ]);

        try {
            $resultado = $this->laberinto->registrarRespuesta(
                $sesion,
                app(EjercicioRepository::class)->buscar((int) $datos['contenido_id']),
                $datos['respuesta'],
                $datos['tiempo_ms'] ?? null,
                isset($datos['intento']) && (int) $datos['intento'] > 0 ? (int) $datos['intento'] : null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'Ejercicio no válido'], 422);
        }

        return response()->json($resultado);
    }

    /** POST /estudiante/laberinto/sesion/{sesion}/cerrar — AJAX (al llegar a la escuela) */
    public function cerrarSesion(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        $datos = $request->validate([
            'pistas'           => ['nullable', 'integer', 'min:0', 'max:40'],
            'flecha'           => ['nullable', 'boolean'],
            'chipas_recogidas' => ['nullable', 'integer', 'min:0', 'max:20'],
            'chipas_total'     => ['nullable', 'integer', 'min:0', 'max:20'],
            'duracion_s'       => ['nullable', 'integer', 'min:0', 'max:7200'],
        ]);

        $r = $this->laberinto->cerrar($sesion, $datos);

        // Las medallas nuevas se muestran en el resumen (igual que en los otros juegos).
        if (!$r['ya_cerrada']) {
            session()->flash('logros_nuevos', $r['logros']->pluck('logro_id')->toArray());
        }

        $medallas = $r['logros']->map(function ($lo) {
            $lo->loadMissing('logro');
            return ['emoji' => $lo->logro?->emoji ?: '🏅', 'nombre' => $lo->logro?->nombre_es ?? ''];
        })->filter(fn ($m) => $m['nombre'] !== '')->values()->all();

        return response()->json([
            'redirect_url'  => route('estudiante.sesion.resumen', $r['sesion']->id),
            'superado'      => $r['superado'],
            'estrellas'     => $r['estrellas'],
            'puntos'        => (int) $r['sesion']->puntaje_obtenido,
            'medallas'      => $medallas,
            'cambio_nivel'  => $r['direccion'],                       // 'sube' | 'baja' | null
            'nivel_destino' => $r['nivel_destino']?->numero_nivel,
            'consecutivas'  => $r['consecutivas'],
            'meta'          => $r['meta'],
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
