<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * MIDDLEWARE: RedirectIfAuthenticated (alias "guest")
 * =========================================================================
 * Se aplica a las pantallas de ingreso (/login y el acceso de estudiantes).
 * Si la persona YA inició sesión, no tiene sentido mostrarle el formulario
 * de ingreso: se la envía a SU panel.
 *
 * CORRECCIÓN (2026-10-11): la versión estándar de Laravel redirigía a
 * RouteServiceProvider::HOME ('/home'), una ruta que este sistema no tiene,
 * por eso aparecía el error 404 al tocar "Soy docente" o "Soy padre/madre"
 * en la pantalla de inicio estando ya con la sesión abierta.
 * Ahora cada perfil va a su panel:
 *   estudiante → estudiante.juegos | docente → docente.panel
 *   padre/madre → padre.panel      | administrador → admin.panel
 * =========================================================================
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect($this->destino($guard));
            }
        }

        return $next($request);
    }

    /** Panel de inicio según el perfil de quien ya tiene la sesión abierta. */
    private function destino(?string $guard): string
    {
        if ($guard === 'estudiante') {
            return route('estudiante.juegos');
        }

        $usuario = Auth::guard($guard)->user();

        if ($usuario) {
            if (method_exists($usuario, 'esDocente') && $usuario->esDocente() && Route::has('docente.panel')) {
                return route('docente.panel');
            }
            if (method_exists($usuario, 'esPadre') && $usuario->esPadre() && Route::has('padre.panel')) {
                return route('padre.panel');
            }
            if (method_exists($usuario, 'esAdmin') && $usuario->esAdmin() && Route::has('admin.panel')) {
                return route('admin.panel');
            }
        }

        // Perfil desconocido: la pantalla de inicio, que siempre existe.
        return url('/');
    }
}
