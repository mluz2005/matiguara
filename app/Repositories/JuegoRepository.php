<?php

namespace App\Repositories;

use App\Models\ConfigDocenteJuego;
use App\Models\Juego;
use App\Models\Nivel;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: JuegoRepository
 * =========================================================================
 * Capa de acceso a datos del catálogo de juegos (juegos) y de su
 * habilitación por docente (config_docente_juego).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class JuegoRepository
{
    /** IDs de los juegos que el docente deshabilitó para el grado (RF-24). */
    public function idsDeshabilitadosPorDocente(int $docenteId, $grado): array
    {
        return ConfigDocenteJuego::where('docente_id', $docenteId)
            ->where('grado', $grado)
            ->where('habilitado', false)
            ->pluck('juego_id')
            ->toArray();
    }

    /** Juegos activos que no están en $idsExcluidos, con su tipo de mecánica. */
    public function activosExcepto(array $idsExcluidos): Collection
    {
        return Juego::activos()
            ->whereNotIn('id', $idsExcluidos)
            ->with('tipoMecanica')
            ->get();
    }

    /** Juegos activos con sus niveles del grado indicado (ordenados por número de nivel). */
    public function activosConNivelesDelGrado($grado): Collection
    {
        return Juego::activos()
            ->with(['niveles' => fn ($q) => $q->where('grado', $grado)->orderBy('numero_nivel')])
            ->get();
    }

    /** ¿Hay al menos un nivel cargado para el grado en algún juego activo? */
    public function hayNivelesDelGrado(int $grado): bool
    {
        return Nivel::where('grado', $grado)
            ->whereHas('juego', fn ($q) => $q->where('activo', true))
            ->exists();
    }

    /** Todos los juegos activos. */
    public function activos(): Collection
    {
        return Juego::activos()->get();
    }

    /** Juegos activos con su tipo de mecánica cargado. */
    public function activosConMecanica(): Collection
    {
        return Juego::activos()->with('tipoMecanica')->get();
    }

    /** IDs de los juegos activos. */
    public function idsActivos(): Collection
    {
        return Juego::activos()->pluck('id');
    }

    /** Niveles de un juego para un grado, ordenados según la relación del modelo. */
    public function nivelesDelGrado(Juego $juego, int $grado): Collection
    {
        return $juego->nivelesPorGrado($grado)->get();
    }

    /** Catálogo completo de juegos (activos o no) con su tipo de mecánica y la cantidad de niveles (niveles_count). */
    public function todosConMecanica(): Collection
    {
        return Juego::with('tipoMecanica')->withCount('niveles')->get();
    }
}
