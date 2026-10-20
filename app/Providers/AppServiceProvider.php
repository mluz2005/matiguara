<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * PROVEEDOR DE SERVICIOS: AppServiceProvider
 * =========================================================================
 * Registra servicios globales de la aplicación.
 *
 * IMPORTANTE: Aquí se registra el EstudianteProvider personalizado para
 * el guard 'estudiante' (autenticación por código_acceso + PIN).
 *
 * En Laravel 11 este es el lugar correcto para registrar providers de auth
 * (ya no existe AuthServiceProvider separado por defecto).
 * =========================================================================
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor IoC de Laravel.
     * Se ejecuta antes de boot(). Usar para bindings.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa los servicios registrados.
     * Se ejecuta después de todos los register(). Usar para configuración.
     */
    public function boot(): void
    {
        // ── Registrar el provider personalizado de estudiantes ─────────────
        // Esto conecta el driver 'estudiante-provider' (definido en config/auth.php)
        // con la clase EstudianteProvider que implementa UserProvider.
        //
        // Flujo al hacer Auth::guard('estudiante')->attempt($credentials):
        //   1. Laravel busca el guard 'estudiante' → driver 'session' + provider 'estudiantes'
        //   2. Provider 'estudiantes' → driver 'estudiante-provider' → esta closure
        //   3. Se instancia EstudianteProvider
        //   4. EstudianteProvider::retrieveByCredentials() busca por codigo_acceso
        //   5. EstudianteProvider::validateCredentials() verifica el PIN
        Auth::provider('estudiante-provider', function ($app, array $config) {
            return new EstudianteProvider();
        });
    }
}
