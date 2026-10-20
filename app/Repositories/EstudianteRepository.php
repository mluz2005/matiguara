<?php

namespace App\Repositories;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Models\ProgresoEstudiante;
use App\Models\Racha;
use App\Models\SesionJuego;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: EstudianteRepository
 * =========================================================================
 * Capa de acceso a datos del avance del estudiante: progreso por nivel
 * (progreso_estudiante) y rachas (rachas).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class EstudianteRepository
{
    // ── Progreso por nivel ───────────────────────────────────────────────

    /** Progreso del estudiante en un nivel (o null si aún no existe). */
    public function progreso(int $estudianteId, int $juegoId, int $nivelId, $grado): ?ProgresoEstudiante
    {
        return ProgresoEstudiante::where([
            'estudiante_id' => $estudianteId,
            'juego_id'      => $juegoId,
            'nivel_id'      => $nivelId,
            'grado'         => $grado,
        ])->first();
    }

    /**
     * Crea el registro de progreso del nivel si no existía (sin sumar partidas;
     * la partida se cuenta una sola vez, al cerrar la sesión).
     */
    public function asegurarProgreso(int $estudianteId, int $juegoId, int $nivelId, $grado): ProgresoEstudiante
    {
        return ProgresoEstudiante::updateOrCreate([
            'estudiante_id' => $estudianteId,
            'juego_id'      => $juegoId,
            'nivel_id'      => $nivelId,
            'grado'         => $grado,
        ], []);
    }

    /** Deja el nivel indicado desbloqueado para el estudiante (UNIQUE por estudiante, juego, nivel y grado). */
    public function desbloquearNivel(int $estudianteId, int $juegoId, Nivel $nivel): ProgresoEstudiante
    {
        return ProgresoEstudiante::updateOrCreate(
            [
                'estudiante_id' => $estudianteId,
                'juego_id'      => $juegoId,
                'nivel_id'      => $nivel->id,
                'grado'         => $nivel->grado,
            ],
            [
                'nivel_desbloqueado' => true,
            ]
        );
    }

    /**
     * Acumula en el progreso del nivel el resultado de una sesión cerrada:
     * partidas, aciertos, mejor puntaje y desbloqueo si superó el nivel.
     * Si no existía el registro de progreso, lo crea.
     */
    public function registrarPartida(SesionJuego $sesion, Nivel $nivel, bool $nivelSuperado): void
    {
        $progreso = $this->progreso(
            $sesion->estudiante_id,
            $sesion->juego_id,
            $sesion->nivel_id,
            $nivel->grado
        );

        if ($progreso) {
            $progreso->increment('total_partidas');
            $progreso->increment('total_aciertos', $sesion->aciertos);

            if ($sesion->puntaje_obtenido > $progreso->mejor_puntaje) {
                $progreso->update(['mejor_puntaje' => $sesion->puntaje_obtenido]);
            }

            if ($nivelSuperado && !$progreso->nivel_desbloqueado) {
                $progreso->update(['nivel_desbloqueado' => true]);
            }

            return;
        }

        ProgresoEstudiante::create([
            'estudiante_id'      => $sesion->estudiante_id,
            'juego_id'           => $sesion->juego_id,
            'nivel_id'           => $sesion->nivel_id,
            'grado'              => $nivel->grado,
            'mejor_puntaje'      => $sesion->puntaje_obtenido,
            'total_partidas'     => 1,
            'total_aciertos'     => $sesion->aciertos,
            'nivel_desbloqueado' => $nivelSuperado,
        ]);
    }

    /** Cantidad de niveles desbloqueados por el estudiante (todos los juegos). */
    public function contarNivelesDesbloqueados(Estudiante $estudiante): int
    {
        return $estudiante->progresos()
            ->where('nivel_desbloqueado', true)
            ->count();
    }

    // ── Rachas ───────────────────────────────────────────────────────────

    /** Racha del estudiante en el juego; la crea en cero si no existía. */
    public function obtenerOCrearRacha(int $estudianteId, int $juegoId): Racha
    {
        return Racha::firstOrCreate(
            ['estudiante_id' => $estudianteId, 'juego_id' => $juegoId],
            [
                'racha_actual_aciertos' => 0,
                'racha_maxima_aciertos' => 0,
                'racha_actual_dias'     => 0,
                'racha_maxima_dias'     => 0,
                'ultima_sesion_fecha'   => null,
            ]
        );
    }

    /** Guarda los cambios de una racha. */
    public function guardarRacha(Racha $racha): Racha
    {
        $racha->save();

        return $racha;
    }

    /** Mayor racha de días actual entre todos los juegos del estudiante (0 si no tiene). */
    public function rachaActualMaximaEnDias(Estudiante $estudiante): int
    {
        return (int) ($estudiante->rachas()->max('racha_actual_dias') ?? 0);
    }

    /** Racha del estudiante en un juego (null si todavía no tiene). */
    public function rachaDelJuego(int $estudianteId, int $juegoId): ?Racha
    {
        return Racha::where('estudiante_id', $estudianteId)
            ->where('juego_id', $juegoId)
            ->first();
    }

    /** Rachas del estudiante en los juegos indicados, indexadas por juego_id. */
    public function rachasPorJuego(int $estudianteId, $juegoIds): Collection
    {
        return Racha::where('estudiante_id', $estudianteId)
            ->whereIn('juego_id', $juegoIds)
            ->get()
            ->keyBy('juego_id');
    }

    // ── Progreso: consultas de lectura y mensajes ────────────────────────

    /** IDs de los niveles marcados como desbloqueados en el progreso del estudiante. */
    public function idsNivelesDesbloqueados(int $estudianteId): Collection
    {
        return ProgresoEstudiante::where('estudiante_id', $estudianteId)
            ->where('nivel_desbloqueado', true)
            ->pluck('nivel_id');
    }

    /** Progreso del estudiante en un grado, con juego y nivel, ordenado por juego y nivel. */
    public function progresosDelGrado(int $estudianteId, $grado): Collection
    {
        return ProgresoEstudiante::where('estudiante_id', $estudianteId)
            ->where('grado', $grado)
            ->with(['juego', 'nivel'])
            ->orderBy('juego_id')
            ->orderBy('nivel_id')
            ->get();
    }

    /** Progreso del nivel; si no existía lo crea en cero. */
    public function obtenerOCrearProgreso(int $estudianteId, int $juegoId, int $nivelId, $grado): ProgresoEstudiante
    {
        return ProgresoEstudiante::firstOrCreate(
            [
                'estudiante_id' => $estudianteId,
                'juego_id'      => $juegoId,
                'nivel_id'      => $nivelId,
                'grado'         => $grado,
            ],
            [
                'mejor_puntaje'      => 0,
                'total_partidas'     => 0,
                'total_aciertos'     => 0,
                'nivel_desbloqueado' => false,
                'ultimo_mensaje_id'  => null,
            ]
        );
    }

    /** Registra cuál fue el último mensaje de retroalimentación mostrado en ese progreso. */
    public function guardarUltimoMensaje(ProgresoEstudiante $progreso, int $mensajeId): void
    {
        $progreso->update(['ultimo_mensaje_id' => $mensajeId]);
    }

    // ── Búsqueda ─────────────────────────────────────────────────────────

    /** Estudiante por su código de acceso (null si no existe). */
    public function buscarPorCodigo(string $codigoAcceso): ?Estudiante
    {
        return Estudiante::where('codigo_acceso', $codigoAcceso)->first();
    }

    // ── Alta, edición y consulta de cuentas (panel del docente) ──────────

    /** Estudiante por id (404 si no existe). */
    public function buscarOFallar(int $id): Estudiante
    {
        return Estudiante::findOrFail($id);
    }

    /** Crea una cuenta de estudiante. */
    public function crear(array $datos): Estudiante
    {
        return Estudiante::create($datos);
    }

    /** Actualiza campos de un estudiante. */
    public function actualizar(Estudiante $estudiante, array $cambios): void
    {
        $estudiante->update($cambios);
    }

    /** Códigos de acceso de todos los estudiantes (para calcular el siguiente). */
    public function codigosDeAcceso(): Collection
    {
        return Estudiante::pluck('codigo_acceso');
    }

    /** Cantidad total de estudiantes registrados. */
    public function contar(): int
    {
        return Estudiante::count();
    }

    /** ¿Ya existe un estudiante con ese código de acceso? */
    public function existeCodigo(string $codigoAcceso): bool
    {
        return Estudiante::where('codigo_acceso', $codigoAcceso)->exists();
    }

    // ── Cuentas de estudiantes: consultas globales y acceso de familias ──

    /** Cantidad de estudiantes activos. */
    public function contarActivos(): int
    {
        return Estudiante::activos()->count();
    }

    /** Matrículas del estudiante (activas y cerradas), de la más antigua a la más reciente. */
    public function matriculas(int $estudianteId): Collection
    {
        return Matricula::with('institucion.config')
            ->where('estudiante_id', $estudianteId)
            ->orderBy('anio_lectivo')
            ->orderBy('id')
            ->get();
    }

    /** Estudiantes activos cuya fecha de baja programada ya venció (egresados a desactivar, RF-18). */
    public function conBajaVencida(): Collection
    {
        return Estudiante::query()->bajaVencida()->get();
    }

    /** Estudiante ACTIVO por código de acceso (null si no existe o está inactivo). */
    public function buscarActivoPorCodigo(string $codigoAcceso): ?Estudiante
    {
        return Estudiante::where('codigo_acceso', $codigoAcceso)
            ->where('activo', true)
            ->first();
    }

    /** Estudiante con ese código de acceso Y ese correo de tutor (correo comparado en minúsculas). */
    public function buscarPorCodigoYCorreoTutor(string $codigoAcceso, string $correo): ?Estudiante
    {
        return Estudiante::where('codigo_acceso', $codigoAcceso)
            ->whereRaw('LOWER(email_tutor) = ?', [$correo])
            ->first();
    }

    /** Estudiantes ACTIVOS cuyo correo de tutor coincide (hermanos de una misma familia). */
    public function activosPorCorreoTutor(string $correo): Collection
    {
        return Estudiante::where('activo', true)
            ->whereRaw('LOWER(email_tutor) = ?', [$correo])
            ->get();
    }
}
