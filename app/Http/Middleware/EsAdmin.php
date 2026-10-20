<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MIDDLEWARE: EsAdmin
 * -------------------------------------------------------------------------
 * Verifica que el usuario autenticado tenga perfil 'admin'.
 * La administradora tiene acceso total al panel de administración
 * (CU-25 a CU-31, gestión de instituciones, docentes, juegos, etc.).
 *
 * Referencia: RF-14, CU-26, CU-27, CU-28
 * -------------------------------------------------------------------------
 */
class EsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->esAdmin()) {
            return redirect()
                ->route('login')
                ->with('error', 'Acceso restringido al panel de administración.');
        }

        return $next($request);
    }
}
