<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Services\NumerosService;
use App\Services\TiempoJuegoService;
use App\Support\Numeros\GeneradorItems;
use App\Support\Numeros\TextosNumeros;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CONTROLADOR: NumerosController — «Mati y los números» (tamizaje)
 * =========================================================================
 * Flujo (un «viaje» = una sesión de 4 a 6 minutos):
 *   1. iniciarSesion()  → crea el viaje que le toca al niño y abre el tablero
 *   2. mostrarTablero() → pantalla única; el navegador pide cada parte
 *   3. parte()          → AJAX, las preguntas de una parte (sin respuestas correctas)
 *   4. responder()      → AJAX, una respuesta; el juego contesta igual a aciertos y errores
 *   5. cerrar()         → AJAX, al terminar o al salir; devuelve la figurita
 *
 * No suma puntos ni entra en el progreso del niño. El grado sale de la matrícula;
 * el límite de tiempo de juego lo aplica el middleware 'limite.juego' (iniciar y tablero).
 * =========================================================================
 */
class NumerosController extends Controller
{
    public function __construct(
        private NumerosService $numeros,
        private TiempoJuegoService $tiempo
    ) {
    }

    /** POST /estudiante/numeros/{juego}/nivel/{nivel}/iniciar */
    public function iniciarSesion(Request $request, Juego $juego, Nivel $nivel): RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        $matricula  = $estudiante->matriculaActiva;

        if (!$matricula) {
            return redirect()->route('estudiante.progreso')
                ->with('cambio_nivel', '🎓 ¡Terminaste tus grados! Ya no se puede jugar, pero podés ver tu progreso.');
        }

        if ($nivel->juego_id !== $juego->id || !$juego->esTamizaje() || (int) $nivel->grado !== (int) $matricula->grado) {
            return redirect()->route('estudiante.juegos');
        }

        $estado = $this->numeros->estadoDe($estudiante->id, $juego->id);

        // Los viajes se abren en orden: solo se juega el que le toca.
        if ((int) $nivel->numero_nivel !== $estado['viaje']) {
            return redirect()->route('estudiante.juegos')
                ->with('cambio_nivel', '🔒 Ese viaje todavía no se abrió. ¡Hacé primero el anterior!');
        }

        // Se sugiere un viaje por día: Mati invita a seguir otro día (RF-14).
        if ($estado['jugo_hoy'] && !$request->boolean('igual')) {
            return redirect()->route('estudiante.numeros.hoy', ['juego' => $juego->id, 'nivel' => $nivel->id]);
        }

        $sesion = $this->numeros->iniciar($estudiante, $juego, $nivel, $this->detectarDispositivo($request));

        return redirect()->route('estudiante.numeros.tablero', $sesion->id);
    }

    /** GET /estudiante/numeros/{juego}/nivel/{nivel}/hoy — Mati invita a seguir otro día */
    public function invitacion(Juego $juego, Nivel $nivel): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        if (!$estudiante->matriculaActiva || !$juego->esTamizaje() || $nivel->juego_id !== $juego->id) {
            return redirect()->route('estudiante.juegos');
        }

        return view('estudiante.numeros-hoy', [
            'juego' => $juego,
            'nivel' => $nivel,
            'viaje' => TextosNumeros::VIAJES[min(3, max(1, (int) $nivel->numero_nivel))],
        ]);
    }

    /** GET /estudiante/numeros/sesion/{sesion}/tablero */
    public function mostrarTablero(SesionJuego $sesion): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return redirect()->route('estudiante.juegos');
        }

        $detalle = $this->numeros->detalleDeSesion($sesion);
        if (!$detalle) {
            return redirect()->route('estudiante.juegos');
        }

        $estado = $this->tiempo->estado($estudiante);
        $viaje  = (int) $detalle->viaje;

        $partes = [];
        foreach (GeneradorItems::partesDelViaje($viaje) as $p) {
            $t = TextosNumeros::PARTES[$p];
            $partes[] = [
                'parte'    => $p,
                'es'       => $t['es'], 'gn' => $t['gn'],
                'ies'      => $t['ies'], 'ign' => $t['ign'],
                'cantidad' => GeneradorItems::PARTES[$p]['fijas'] + GeneradorItems::PARTES[$p]['adaptativas'],
            ];
        }

        $config = [
            'sesionId'    => $sesion->id,
            'grado'       => (int) $detalle->grado,
            'viaje'       => $viaje,
            'ciclo'       => (int) $detalle->ciclo,
            'viajeNombre' => TextosNumeros::VIAJES[$viaje],
            'partes'      => $partes,
            'total'       => (int) $detalle->total_preguntas,
            'limiteSeg'   => (int) $estado['limite_seg'],
            'restanteSeg' => (int) $estado['restante_seg'],
            'voces'       => $this->numeros->voces(),
            'urls' => [
                'parte'     => url('/estudiante/numeros/sesion/' . $sesion->id . '/parte/__P__'),
                'responder' => route('estudiante.numeros.responder', $sesion->id),
                'cerrar'    => route('estudiante.numeros.cerrar', $sesion->id),
                'descanso'  => route('estudiante.descanso'),
                'juegos'    => route('estudiante.juegos'),
            ],
            'img' => asset('img/numeros') . '/',
            'mati' => [
                'senala'     => asset('img/numeros/mati-senala.webp'),
                'piensa'     => asset('img/numeros/mati-piensa.webp'),
                'despidiendo' => asset('img/numeros/mati-despidiendo.webp'),
            ],
        ];

        return view('estudiante.numeros-tablero', [
            'sesion' => $sesion,
            'juego'  => $sesion->juego,
            'config' => $config,
        ]);
    }

    /** GET /estudiante/numeros/sesion/{sesion}/parte/{parte} — AJAX */
    public function parte(SesionJuego $sesion, int $parte): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        try {
            return response()->json($this->numeros->parteDe($sesion, $parte));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'Parte no válida'], 422);
        }
    }

    /** POST /estudiante/numeros/sesion/{sesion}/respuesta — AJAX */
    public function responder(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id || !$sesion->estaEnCurso()) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        $datos = $request->validate([
            'parte'     => ['required', 'integer', 'between:1,10'],
            'orden'     => ['required', 'integer', 'min:0', 'max:40'],
            'respuesta' => ['required', 'string', 'max:24'],
            'tiempo_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        try {
            $r = $this->numeros->registrarRespuesta(
                $sesion, (int) $datos['parte'], (int) $datos['orden'], $datos['respuesta'],
                isset($datos['tiempo_ms']) ? (int) $datos['tiempo_ms'] : null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'Respuesta no válida'], 422);
        }

        // Misma respuesta para aciertos y errores (RF-08): solo se confirma que se guardó.
        return response()->json(['ok' => true, 'duplicada' => $r['duplicada']]);
    }

    /** POST /estudiante/numeros/sesion/{sesion}/cerrar — AJAX */
    public function cerrar(Request $request, SesionJuego $sesion): JsonResponse
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($sesion->estudiante_id !== $estudiante->id) {
            return response()->json(['error' => 'Sesión no válida'], 403);
        }

        $datos = $request->validate([
            'duracion_s' => ['nullable', 'integer', 'min:0', 'max:7200'],
        ]);

        $r = $this->numeros->cerrar($sesion, (int) ($datos['duracion_s'] ?? 0));

        // Al niño no se le muestra ningún resultado: solo si terminó y su figurita.
        return response()->json([
            'completa' => $r['completa'],
            'viaje'    => $r['viaje'],
            'figurita' => $r['figurita'],
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
