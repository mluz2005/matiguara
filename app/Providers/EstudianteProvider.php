<?php

namespace App\Providers;

use App\Models\Estudiante;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

/**
 * PROVIDER PERSONALIZADO: EstudianteProvider
 * =========================================================================
 * Implementa el mecanismo de autenticación para estudiantes.
 *
 * DIFERENCIAS con el provider estándar de Laravel:
 *   - Estándar: busca por 'email', valida con Hash::check($password, $hash)
 *   - Este:     busca por 'codigo_acceso', valida el PIN de 4 dígitos
 *
 * Laravel llama a estos métodos automáticamente cuando se usa:
 *   Auth::guard('estudiante')->attempt(['codigo_acceso' => ..., 'pin' => ...])
 *
 * Registro en AppServiceProvider::boot():
 *   Auth::provider('estudiante-provider', fn($app, $config) =>
 *       new EstudianteProvider()
 *   );
 *
 * Referencia: RF-01 (autenticación por perfil), RN-01 (privacidad)
 * CU: CU-02 (Iniciar sesión Estudiante)
 * =========================================================================
 */
class EstudianteProvider implements UserProvider
{
    /**
     * Busca un estudiante por sus credenciales (codigo_acceso).
     * Laravel llama este método primero en el proceso de attempt().
     *
     * @param  array $credentials ['codigo_acceso' => '...', 'pin' => '...']
     * @return Authenticatable|null  El estudiante encontrado, o null si no existe
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['codigo_acceso'])) {
            return null;
        }

        // Buscar por código de acceso entre cuentas ACTIVAS solamente.
        // Las cuentas inactivas (activo=false) no pueden iniciar sesión,
        // incluyendo los egresados desactivados por EgresadoCleanupJob (RF-18).
        return Estudiante::where('codigo_acceso', $credentials['codigo_acceso'])
                         ->where('activo', true)
                         ->first();
    }

    /**
     * Valida el PIN ingresado contra el hash almacenado en la BD.
     * Laravel llama este método si retrieveByCredentials() devolvió un usuario.
     *
     * @param  Authenticatable $user         El estudiante encontrado en la BD
     * @param  array           $credentials  ['codigo_acceso' => ..., 'pin' => ...]
     * @return bool            TRUE si el PIN es correcto
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (empty($credentials['pin'])) {
            return false;
        }

        // Delega la verificación al método del modelo Estudiante.
        // Hash::check($credentials['pin'], $user->pin_hash)
        return $user->verificarPin($credentials['pin']);
    }

    /**
     * Recupera un estudiante por su ID primario (usado para restaurar
     * la sesión en cada request posterior al login).
     *
     * @param  mixed $identifier  El ID del estudiante (guardado en la sesión)
     * @return Authenticatable|null
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return Estudiante::find($identifier);
    }

    /**
     * Recupera un estudiante por su token "remember me".
     * No implementado: los niños de 6-9 años no usan "recordarme".
     * Siempre devuelve null para desactivar esta funcionalidad.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null; // "Recordarme" no aplica para estudiantes infantiles
    }

    /**
     * Actualiza el token "remember me".
     * No implementado por la misma razón anterior.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Intencional: sin "recordarme" para estudiantes
    }

    /**
     * Rehashear el PIN si el factor de costo de bcrypt cambió.
     * Laravel 11 llama este método automáticamente si es necesario.
     *
     * @param  Authenticatable $user         El estudiante autenticado
     * @param  array           $credentials  Las credenciales usadas en el login
     * @param  bool            $force        Si se debe forzar el rehash
     */
    public function rehashPasswordIfRequired(
        Authenticatable $user,
        array $credentials,
        bool $force = false
    ): void {
        if (!$force && !Hash::needsRehash($user->pin_hash)) {
            return;
        }

        // Rehashear el PIN con el costo actual de bcrypt y guardar
        $user->forceFill([
            'pin_hash' => Estudiante::hashearPin($credentials['pin']),
        ])->save();
    }
}
