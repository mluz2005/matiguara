<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MIDDLEWARE: EsDocente
 * -------------------------------------------------------------------------
 * Verifica que el usuario autenticado tenga perfil 'docente'.
 * Se aplica a todas las rutas del panel docente (RF-08, CU-09 a CU-14).
 *
 * Si el usuario no es docente, redirige al login con mensaje de error.
 * Si no está autenticado, el middleware 'auth' lo intercepta antes.
 *
 * Registro en app/Http/Kernel.php:
 *   protected $middlewareAliases = [
 *       'es.docente' => EsDocente::class,
 *   ];
 *
 * Uso en rutas:
 *   Route::middleware(['auth', 'es.docente'])->group(function () {
 *       Route::get('/panel-docente', [PanelDocenteController::class, 'index']);
 *   });
 *
 * Referencia: RF-01 (autenticación por perfil)
 * -------------------------------------------------------------------------
 */
class EsDocente
{
    /**
     * Maneja la solicitud entrante.
     *
     * @param  Request  $request  Solicitud HTTP actual
     * @param  Closure  $next     Siguiente middleware en la cadena
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar perfil del usuario autenticado
        if (!$request->user() || !$request->user()->esDocente()) {
            // No es docente: redirigir con mensaje de error de autorización
            return redirect()
                ->route('login')
                ->with('error', 'Acceso restringido al panel docente.');
        }

        // Es docente y está activo: continuar con la solicitud
        return $next($request);
    }
}
