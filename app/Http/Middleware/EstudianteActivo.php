<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si la docente desactiva la cuenta de un niño mientras él tiene la sesión
 * abierta, en su próxima acción se cierra la sesión y se le explica el motivo.
 * (Al ingresar, el chequeo lo hace LoginEstudianteController.)
 *
 * Se usa en las rutas de estudiante, después de "auth:estudiante".
 */
class EstudianteActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $estudiante = Auth::guard('estudiante')->user();

        if ($estudiante && !$estudiante->activo) {
            Auth::guard('estudiante')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cuenta no activa'], 403);
            }

            return redirect()->route('estudiante.login')->withErrors([
                'codigo_acceso' => 'Tu cuenta no está activa por ahora. Pedile ayuda a tu maestra.',
            ]);
        }

        return $next($request);
    }
}
