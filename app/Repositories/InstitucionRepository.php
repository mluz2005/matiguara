<?php

namespace App\Repositories;

use App\Models\ConfigInstitucion;
use App\Models\Institucion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: InstitucionRepository
 * =========================================================================
 * Capa de acceso a datos de las instituciones y de su configuración
 * operativa (config_institucion). Lo usa el panel de administración.
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class InstitucionRepository
{
    /** Cantidad de instituciones activas. */
    public function contarActivas(): int
    {
        return Institucion::activas()->count();
    }

    /** Instituciones activas. */
    public function activas(): Collection
    {
        return Institucion::activas()->get();
    }

    /** Instituciones por nombre, con su configuración y asignaciones, paginadas. */
    public function paginadasConConfig(int $porPagina): LengthAwarePaginator
    {
        return Institucion::with(['config', 'asignaciones'])
            ->orderBy('nombre')
            ->paginate($porPagina);
    }

    /** Institución por id (404 si no existe). */
    public function buscar(int $id): Institucion
    {
        return Institucion::findOrFail($id);
    }

    /** Institución por id con su configuración cargada (404 si no existe). */
    public function buscarConConfig(int $id): Institucion
    {
        return Institucion::with('config')->findOrFail($id);
    }

    /** Alta de una institución. */
    public function crear(array $datos): Institucion
    {
        return Institucion::create($datos);
    }

    /** Actualiza los datos de una institución. */
    public function actualizar(Institucion $institucion, array $datos): void
    {
        $institucion->update($datos);
    }

    // ── Configuración operativa ──────────────────────────────────────────

    /** Crea la configuración con los valores por defecto (CU-27 / RF-16). */
    public function crearConfigPorDefecto(int $institucionId): ConfigInstitucion
    {
        return ConfigInstitucion::crearPorDefecto($institucionId);
    }

    /** Configuración de la institución; si todavía no tiene, la crea con los valores por defecto. */
    public function configOCrearPorDefecto(Institucion $institucion): ConfigInstitucion
    {
        return $institucion->config ?? $this->crearConfigPorDefecto($institucion->id);
    }

    /** Guarda los parámetros de la configuración de la institución. */
    public function actualizarConfig(int $institucionId, array $datos): void
    {
        ConfigInstitucion::where('institucion_id', $institucionId)->update($datos);
    }
}
