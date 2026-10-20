<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use App\Models\Padre;

/**
 * REPOSITORIO: PadreRepository
 * =========================================================================
 * Capa de acceso a datos de los vínculos entre cuentas de familia y
 * estudiantes (tabla `padres`).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class PadreRepository
{
    /**
     * Elimina los vínculos de un estudiante con las cuentas de familia indicadas
     * (por ejemplo, cuando cambia el correo del tutor).
     *
     * @param  iterable<int>  $usuarioIds
     */
    public function eliminarVinculos(int $estudianteId, $usuarioIds): void
    {
        Padre::where('estudiante_id', $estudianteId)
            ->whereIn('usuario_id', $usuarioIds)
            ->delete();
    }

    /** Todos los vínculos de la cuenta con estudiantes (hermanos incluidos), con el estudiante cargado. */
    public function vinculosDelUsuario(int $usuarioId): Collection
    {
        return Padre::where('usuario_id', $usuarioId)
            ->with('estudiante')
            ->get();
    }

    /** ¿La cuenta ya está vinculada con algún estudiante? */
    public function tieneVinculo(int $usuarioId): bool
    {
        return Padre::where('usuario_id', $usuarioId)->exists();
    }

    /** Crea el vínculo entre una cuenta de familia y un estudiante. */
    public function crearVinculo(array $datos): Padre
    {
        return Padre::create($datos);
    }

    /** Vincula la cuenta con el estudiante si todavía no lo estaba. */
    public function vincularSiNoExiste(int $usuarioId, int $estudianteId, string $relacion): Padre
    {
        return Padre::firstOrCreate(
            ['usuario_id' => $usuarioId, 'estudiante_id' => $estudianteId],
            ['relacion' => $relacion]
        );
    }
}
