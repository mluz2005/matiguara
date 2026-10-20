<?php

namespace App\Repositories;

use App\Models\Diagnostico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: DiagnosticoRepository
 * =========================================================================
 * Capa de acceso a datos de los diagnósticos pedagógicos (diagnosticos).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class DiagnosticoRepository
{
    /** Guarda un diagnóstico (RF-15). */
    public function crear(array $datos): Diagnostico
    {
        return Diagnostico::create($datos);
    }

    /** Diagnóstico por id (404 si no existe). */
    public function buscar(int $id): Diagnostico
    {
        return Diagnostico::findOrFail($id);
    }

    /** Todos los diagnósticos del estudiante, el más reciente primero. */
    public function delEstudiante(int $estudianteId): Collection
    {
        return Diagnostico::where('estudiante_id', $estudianteId)
            ->orderBy('fecha_generacion', 'desc')
            ->get();
    }

    /** Diagnósticos del estudiante paginados, el más reciente primero. */
    public function delEstudiantePaginado(int $estudianteId, int $porPagina): LengthAwarePaginator
    {
        return Diagnostico::where('estudiante_id', $estudianteId)
            ->orderBy('fecha_generacion', 'desc')
            ->paginate($porPagina);
    }

    /** Los últimos $limite diagnósticos del estudiante, sin contar el indicado. */
    public function anterioresA(int $estudianteId, int $excluirId, int $limite): Collection
    {
        return Diagnostico::where('estudiante_id', $estudianteId)
            ->where('id', '!=', $excluirId)
            ->orderBy('fecha_generacion', 'desc')
            ->take($limite)
            ->get();
    }

    /** Los últimos $limite diagnósticos EXITOSOS del estudiante. */
    public function exitososDelEstudiante(int $estudianteId, int $limite): Collection
    {
        return Diagnostico::where('estudiante_id', $estudianteId)
            ->where('estado', 'exitoso')
            ->orderBy('fecha_generacion', 'desc')
            ->take($limite)
            ->get();
    }

    /** Último diagnóstico exitoso del estudiante (null si no tiene). */
    public function ultimoExitoso(int $estudianteId): ?Diagnostico
    {
        return Diagnostico::where('estudiante_id', $estudianteId)
            ->where('estado', 'exitoso')
            ->latest('fecha_generacion')
            ->first();
    }

    /** Cantidad de diagnósticos exitosos en todo el sistema. */
    public function contarExitosos(): int
    {
        return Diagnostico::where('estado', 'exitoso')->count();
    }
}
