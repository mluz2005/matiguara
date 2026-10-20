<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\InstitucionRepository;
use App\Repositories\JuegoRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SERVICIO: MatriculaService
 * =========================================================================
 * Cierre del año lectivo de un estudiante y ciclo de vida de sus matrículas.
 *
 *  - RF-18 / RN-10: al cerrar la matrícula de 3.° grado se calcula
 *    estudiantes.fecha_baja_programada = fecha de cierre + meses de
 *    conservación de la institución (config_institucion, 4 por defecto).
 *    La tarea programada (Kernel → EgresadoCleanupJob) desactiva la cuenta
 *    cuando esa fecha llega; el historial se conserva.
 *  - Pase de grado: al cerrar 1.° o 2.° se abre la matrícula del grado
 *    siguiente con la misma institución y el mismo docente.
 *  - RF-17: determina qué grado puede ver el estudiante en su progreso (el
 *    activo; los anteriores solo si la institución lo habilita).
 *
 * Capa: app/Services (lógica de negocio). Los datos los pide a los repositorios.
 * =========================================================================
 */
class MatriculaService
{
    public const GRADO_FINAL = 3;

    public function __construct(
        private DocenteRepository $docentes,
        private EstudianteRepository $estudiantes,
        private InstitucionRepository $instituciones,
        private JuegoRepository $juegos
    ) {
    }

    /** Meses que se conserva la cuenta de un egresado en la institución de la matrícula. */
    public function mesesDeConservacion(Matricula $matricula): int
    {
        $config = $this->instituciones->configOCrearPorDefecto($matricula->institucion);

        return (int) $config->meses_conservacion_egresados;
    }

    /**
     * Cierra la matrícula. Si es de 3.° grado programa la baja del egresado.
     *
     * @return Carbon|null Fecha de baja programada (solo para 3.° grado).
     */
    public function cerrarMatricula(Matricula $matricula, ?Carbon $fecha = null): ?Carbon
    {
        if (!$matricula->activo) {
            throw new \DomainException('La matrícula ya está cerrada.');
        }

        $fecha = ($fecha ?? now())->copy()->startOfDay();
        $baja  = null;

        DB::transaction(function () use ($matricula, $fecha, &$baja) {
            $this->docentes->cerrarMatricula($matricula, $fecha);

            if ($matricula->grado >= self::GRADO_FINAL) {
                $baja = $fecha->copy()->addMonthsNoOverflow($this->mesesDeConservacion($matricula));
                $this->estudiantes->actualizar(
                    $matricula->estudiante,
                    ['fecha_baja_programada' => $baja->toDateString()]
                );
            }
        });

        return $baja;
    }

    /**
     * Cierra la matrícula de 1.° o 2.° grado y abre la del grado siguiente
     * (misma institución y mismo docente) siempre que ya haya niveles cargados para ese
     * grado. El año lectivo de la nueva matrícula
     * es el siguiente al cerrado, o el año en curso si ya se está en él.
     */
    public function promover(Matricula $matricula, ?Carbon $fecha = null): Matricula
    {
        if ($matricula->grado >= self::GRADO_FINAL) {
            throw new \DomainException('El alumno de 3.er grado egresa: no tiene grado siguiente.');
        }

        // Sin contenido de juego para el grado siguiente el alumno no tendría qué jugar.
        if (!$this->juegos->hayNivelesDelGrado($matricula->grado + 1)) {
            throw new \DomainException(
                'Todavía no hay niveles de juego cargados para ' . ($matricula->grado + 1) . '.° grado.'
            );
        }

        $fecha = ($fecha ?? now())->copy()->startOfDay();

        return DB::transaction(function () use ($matricula, $fecha) {
            $this->cerrarMatricula($matricula, $fecha);

            return $this->docentes->crearMatricula([
                'estudiante_id'  => $matricula->estudiante_id,
                'institucion_id' => $matricula->institucion_id,
                'docente_id'     => $matricula->docente_id,
                'grado'          => $matricula->grado + 1,
                'anio_lectivo'   => max($matricula->anio_lectivo + 1, $fecha->year),
                'activo'         => true,
                'fecha_inicio'   => $fecha->toDateString(),
            ]);
        });
    }

    /**
     * RF-17 — Grado cuyo progreso se muestra al estudiante.
     *
     * El grado activo (o, si egresó, el último que cursó) se ve siempre; los
     * grados anteriores solo si la institución habilitó permitir_acceso_grados_ant.
     *
     * @param  int|null $pedido Grado solicitado por el estudiante (parámetro ?grado=)
     * @return array{grado:int,actual:int,anteriores:array<int,int>,activa:?Matricula}
     */
    public function gradoParaProgreso(Estudiante $estudiante, ?int $pedido = null): array
    {
        $matriculas = $this->estudiantes->matriculas($estudiante->id);
        $activa     = $matriculas->where('activo', true)->last();
        $referencia = $activa ?? $matriculas->last();
        $actual     = $referencia ? (int) $referencia->grado : 1;

        $anteriores = [];
        if ($referencia && $this->institucionPermiteGradosAnteriores($referencia)) {
            $anteriores = $matriculas->pluck('grado')->map(fn ($g) => (int) $g)
                ->unique()->reject(fn ($g) => $g === $actual)->sort()->values()->all();
        }

        $grado = ($pedido !== null && in_array($pedido, $anteriores, true)) ? $pedido : $actual;

        return ['grado' => $grado, 'actual' => $actual, 'anteriores' => $anteriores, 'activa' => $activa];
    }

    /** Grados que el docente puede revisar de un alumno suyo: todo el historial (RF-17). */
    public function gradosDelHistorial(Estudiante $estudiante): array
    {
        return $this->estudiantes->matriculas($estudiante->id)
            ->pluck('grado')->map(fn ($g) => (int) $g)->unique()->sort()->values()->all();
    }

    private function institucionPermiteGradosAnteriores(Matricula $matricula): bool
    {
        $config = $this->instituciones->configOCrearPorDefecto($matricula->institucion);

        return (bool) $config->permitir_acceso_grados_ant;
    }
}
