<?php

namespace App\Repositories;

use App\Models\AsignacionDocente;
use App\Models\ConfigDocenteJuego;
use App\Models\Docente;
use App\Models\Matricula;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * REPOSITORIO: DocenteRepository
 * =========================================================================
 * Capa de acceso a datos de la CLASE del docente: sus matrículas, qué
 * alumnos le pertenecen y qué juegos tiene habilitados para su grado.
 *
 * Capa: app/Repositories (acceso a datos). Los controladores del panel
 * docente no consultan los modelos directamente: pasan por aquí.
 * =========================================================================
 */
class DocenteRepository
{
    /** Perfil de docente de una cuenta de usuario (null si no es docente). */
    public function porUsuario(int $usuarioId): ?Docente
    {
        return Docente::where('usuario_id', $usuarioId)->first();
    }

    /**
     * Matrículas activas del docente en el año lectivo en curso, con las relaciones
     * pedidas (por defecto, el estudiante). Incluye las ya abiertas para un año
     * posterior (alumnos que pasaron de grado al cerrar el año), no las de años anteriores.
     */
    public function matriculasActivasDelAnio(?int $docenteId, $anio, array $relaciones = ['estudiante']): Collection
    {
        return Matricula::with($relaciones)
            ->where('docente_id', $docenteId)
            ->where('anio_lectivo', '>=', $anio)
            ->where('activo', true)
            ->get();
    }

    /** ¿El estudiante tiene matrícula activa en la clase de este docente? */
    public function esEstudianteDelDocente(int $estudianteId, ?int $docenteId): bool
    {
        if (!$docenteId) return false;

        return Matricula::where('estudiante_id', $estudianteId)
            ->where('docente_id', $docenteId)
            ->where('activo', true)
            ->exists();
    }

    /** Matrícula activa del alumno en la clase del docente (null si no es suyo). */
    public function matriculaActivaDelAlumno(int $estudianteId, int $docenteId): ?Matricula
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('docente_id', $docenteId)
            ->where('activo', true)
            ->first();
    }

    /**
     * Matrícula activa MÁS RECIENTE del alumno en la clase del docente, con
     * estudiante e institución cargados (para la ficha imprimible).
     */
    public function ultimaMatriculaActivaConFicha(int $estudianteId, ?int $docenteId): ?Matricula
    {
        return Matricula::with(['estudiante', 'institucion'])
            ->where('docente_id', $docenteId)
            ->where('estudiante_id', $estudianteId)
            ->where('activo', true)
            ->latest('id')
            ->first();
    }

    /** Alta de una matrícula. */
    public function crearMatricula(array $datos): Matricula
    {
        return Matricula::create($datos);
    }

    /** Cierra la matrícula: queda inactiva y con fecha de fin (RF-18). */
    public function cerrarMatricula(Matricula $matricula, $fecha): void
    {
        $matricula->update([
            'activo'    => false,
            'fecha_fin' => $fecha->toDateString(),
        ]);
    }

    /** IDs de los estudiantes con matrícula activa en la clase del docente. */
    public function idsEstudiantesActivos(int $docenteId): Collection
    {
        return Matricula::where('docente_id', $docenteId)
            ->where('activo', true)
            ->pluck('estudiante_id');
    }

    // ── Configuración de juegos por docente y grado ─────────────────────

    /** Habilitación de cada juego para el docente y grado, indexada por juego_id. */
    public function habilitacionDeJuegos(?int $docenteId, $grado): Collection
    {
        return ConfigDocenteJuego::where('docente_id', $docenteId)
            ->where('grado', $grado)
            ->pluck('habilitado', 'juego_id');
    }

    /** Habilita o deshabilita un juego para el docente y grado (crea la fila si no existe). */
    public function guardarHabilitacion(int $docenteId, int $juegoId, $grado, bool $habilitado): void
    {
        ConfigDocenteJuego::updateOrCreate(
            ['docente_id' => $docenteId, 'juego_id' => $juegoId, 'grado' => $grado],
            ['habilitado' => $habilitado]
        );
    }

    // ── Administración de docentes ───────────────────────────────────────

    /** Cantidad de docentes activos. */
    public function contarActivos(): int
    {
        return Docente::activos()->count();
    }

    /** Docentes activos con su usuario y asignación activa (con institución). */
    public function activosConAsignacion(): Collection
    {
        return Docente::with(['usuario', 'asignacionActiva.institucion'])
            ->activos()
            ->get();
    }

    /** Docente por id (404 si no existe). */
    public function buscar(int $id): Docente
    {
        return Docente::findOrFail($id);
    }

    /** Crea el perfil de docente de una cuenta de usuario. */
    public function crearPerfil(int $usuarioId): Docente
    {
        return Docente::create(['usuario_id' => $usuarioId, 'activo' => true]);
    }

    /** Crea una asignación de docente a institución y grado. */
    public function crearAsignacion(array $datos): AsignacionDocente
    {
        return AsignacionDocente::create($datos);
    }

    /**
     * Da de baja al docente sin borrar su historial: desactiva el perfil y su
     * cuenta de usuario y cierra sus asignaciones activas. Todo o nada (transacción).
     *
     * El motivo de baja es 'otro': la columna asignaciones_docente.motivo_baja solo
     * admite renuncia, traslado, jubilacion y otro (antes se guardaba 'baja', un valor
     * fuera del enum que hacía fallar la operación).
     */
    public function desactivar(Docente $docente): void
    {
        DB::transaction(function () use ($docente) {
            $docente->update(['activo' => false]);
            $docente->usuario->update(['activo' => false]);

            $docente->asignaciones()->where('activo', true)->update([
                'activo'      => false,
                'motivo_baja' => 'otro',
                'fecha_fin'   => now()->toDateString(),
            ]);
        });
    }
}
