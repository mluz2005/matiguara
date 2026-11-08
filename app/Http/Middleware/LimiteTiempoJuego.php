<?php

namespace App\Http\Middleware;

use App\Services\TiempoJuegoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * MIDDLEWARE: LimiteTiempoJuego  (alias de ruta: 'limite.juego')
 * -------------------------------------------------------------------------
 * Se aplica a las rutas que INICIAN o MUESTRAN un ejercicio/tablero de
 * cualquier juego (no a las que reciben una respuesta). Así el niño siempre
 * termina el ejercicio que tiene delante: al enviar la respuesta, la
 * pantalla siguiente ya lo manda a descansar si llegó al límite.
 *
 * Regla completa: App\Services\TiempoJuegoService (RN-L10, RN-L11).
 *
 *   - Si está bloqueado: cierra sus partidas en curso y lo lleva a
 *     estudiante.descanso (o responde 423 con la URL, si la llamada es AJAX).
 *   - Si no: comparte con las vistas `$tiempoJuego` para dibujar la barra
 *     suave de tiempo del layout (sin cuenta regresiva numérica).
 */
class LimiteTiempoJuego
{
    public function __construct(private TiempoJuegoService $tiempo)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $estudiante = Auth::guard('estudiante')->user();

        // Sin alumno autenticado no hay nada que limitar (otras capas se ocupan).
        if (!$estudiante) {
            return $next($request);
        }

        $estado = $this->tiempo->estado($estudiante);

        if ($estado['bloqueado']) {
            $this->tiempo->mandarADescansar($estudiante);

            if ($request->expectsJson()) {
                return response()->json(
                    ['error' => 'descanso', 'redirect_url' => route('estudiante.descanso')],
                    423
                );
            }

            return redirect()->route('estudiante.descanso');
        }

        if ($estado['limite_seg'] > 0) {
            View::share('tiempoJuego', $estado);
        }

        return $next($request);
    }
}
