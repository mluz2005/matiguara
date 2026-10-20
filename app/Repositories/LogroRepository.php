<?php

namespace App\Repositories;

use App\Models\CatalogoLogro;
use App\Models\LogroObtenido;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: LogroRepository
 * =========================================================================
 * Capa de acceso a datos de logros y medallas: catálogo (catalogo_logros) y
 * logros obtenidos por los estudiantes (logros_obtenidos).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class LogroRepository
{
    /** IDs de los logros que el estudiante ya tiene. */
    public function idsObtenidos(int $estudianteId): array
    {
        return LogroObtenido::where('estudiante_id', $estudianteId)
            ->pluck('logro_id')
            ->toArray();
    }

    /** Logros ACTIVOS del catálogo que no están en $idsYaObtenidos. */
    public function pendientes(array $idsYaObtenidos): Collection
    {
        return CatalogoLogro::where('activo', true)
            ->whereNotIn('id', $idsYaObtenidos)
            ->get();
    }

    /** Cantidad de logros activos en el catálogo. */
    public function contarActivos(): int
    {
        return CatalogoLogro::where('activo', true)->count();
    }

    /** Registra que el estudiante obtuvo un logro en la sesión indicada. */
    public function otorgar(int $estudianteId, int $logroId, int $sesionId): LogroObtenido
    {
        return LogroObtenido::create([
            'estudiante_id'  => $estudianteId,
            'logro_id'       => $logroId,
            'sesion_id'      => $sesionId,
            'fecha_obtenido' => now(),
        ]);
    }

    /** Logros del estudiante (con su catálogo cargado), del más reciente al más antiguo. */
    public function obtenidosDe(int $estudianteId): Collection
    {
        return LogroObtenido::where('estudiante_id', $estudianteId)
            ->with('logro')
            ->orderBy('fecha_obtenido', 'desc')
            ->get();
    }

    /** Subconjunto de los logros del estudiante correspondiente a los logro_id indicados. */
    public function obtenidosPorIds(int $estudianteId, array $logroIds): Collection
    {
        return LogroObtenido::whereIn('logro_id', $logroIds)
            ->where('estudiante_id', $estudianteId)
            ->with('logro')
            ->get();
    }

    /** Los logros más recientes del estudiante (con su ficha del catálogo); sin límite si $limite es null. */
    public function recientesDe(int $estudianteId, ?int $limite = null): Collection
    {
        $consulta = LogroObtenido::where('estudiante_id', $estudianteId)
            ->with('logro')
            ->orderBy('fecha_obtenido', 'desc');

        if ($limite !== null) {
            $consulta->take($limite);
        }

        return $consulta->get();
    }
}
