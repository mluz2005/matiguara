<?php

namespace App\Repositories;

use App\Models\Usuario;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: UsuarioRepository
 * =========================================================================
 * Capa de acceso a datos de las cuentas de usuario (administración,
 * docentes y familias).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class UsuarioRepository
{
    /**
     * ¿Hay una cuenta de personal (docente o administración, es decir, de
     * perfil distinto de 'padre') con este correo? El correo se compara en
     * minúsculas; se espera ya normalizado.
     */
    public function existeCorreoDePersonal(string $correo): bool
    {
        return Usuario::whereRaw('LOWER(email) = ?', [$correo])
            ->where('perfil', '!=', 'padre')
            ->exists();
    }

    /** IDs de las cuentas de FAMILIA (perfil 'padre') con este correo. */
    public function idsDeFamiliaPorCorreo(string $correo): Collection
    {
        return Usuario::whereRaw('LOWER(email) = ?', [$correo])
            ->where('perfil', 'padre')
            ->pluck('id');
    }

    /** Cuenta de usuario con ese correo (comparado en minúsculas; se espera ya normalizado). */
    public function porCorreo(string $correo): ?Usuario
    {
        return Usuario::whereRaw('LOWER(email) = ?', [$correo])->first();
    }

    /** Alta de una cuenta de usuario. */
    public function crear(array $datos): Usuario
    {
        return Usuario::create($datos);
    }
}
