<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MIDDLEWARE: EsPadre
 * -------------------------------------------------------------------------
 * Verifica que el usuario autenticado tenga perfil 'padre'.
 * El padre SOLO puede acceder al panel parental de su propio hijo (RN-04).
 *
 * Referencia: RF-09 (panel de control para padres), RN-04
 * -------------------------------------------------------------------------
 */
class EsPadre
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->esPadre()) {
            return redirect()
                ->route('login')
                ->with('error', 'Acceso restringido al panel parental.');
        }

        return $next($request);
    }
}
