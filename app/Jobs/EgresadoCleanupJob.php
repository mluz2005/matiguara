<?php

namespace App\Jobs;

use App\Models\Estudiante;
use App\Repositories\EstudianteRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * JOB: EgresadoCleanupJob
 * -------------------------------------------------------------------------
 * Desactiva la cuenta de un estudiante egresado cuando llega su
 * fecha_baja_programada. Se despacha desde Kernel::schedule() diariamente.
 *
 * FLUJO COMPLETO (RF-18 / RN-10):
 * 1. MatriculaService::cerrarMatricula() escribe fecha_baja_programada
 *    en estudiantes (fecha de cierre + meses_conservacion_egresados de la
 *    institución) cuando el docente cierra la matrícula de un alumno de 3° grado.
 * 2. Kernel::schedule() ejecuta diariamente EgresadoCleanupJob::dispatch()
 *    para cada estudiante donde fecha_baja_programada <= hoy.
 * 3. Este job ejecuta activo = FALSE en la tabla estudiantes.
 * 4. Si falla, Laravel Queue reintenta automáticamente (max 3 veces).
 *
 * IMPLEMENTADO en: App\Console\Kernel → schedule()
 * DESPACHO: Kernel::schedule()->daily()
 *
 * Referencia: CU-26 — Gestionar ciclo de vida de cuentas de egresados
 * RF relacionados: RF-18
 * RN relacionadas: RN-10
 * -------------------------------------------------------------------------
 */
class EgresadoCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de reintentos si el job falla.
     * Laravel Queue reintenta con back-off exponencial: 1min, 5min, 15min.
     */
    public int $tries = 3;

    /**
     * Tiempo máximo de ejecución en segundos antes de considerarlo fallido.
     */
    public int $timeout = 60;

    /**
     * El estudiante cuya cuenta debe desactivarse.
     * SerializesModels lo convierte a ID para evitar objetos pesados en la cola.
     */
    public function __construct(
        public Estudiante $estudiante
    ) {}

    /**
     * Ejecuta la lógica del job.
     *
     * Desactiva la cuenta del estudiante egresado marcando activo = FALSE.
     * No elimina datos: el historial de sesiones y progreso se conserva
     * según la política de la institución (meses_conservacion_egresados).
     */
    public function handle(): void
    {
        // Verificar que el estudiante aún esté activo al momento de ejecutar.
        // (podría haberse desactivado manualmente entre el dispatch y la ejecución)
        if (!$this->estudiante->activo) {
            Log::info('EgresadoCleanupJob: estudiante ya inactivo, skip.', [
                'estudiante_id' => $this->estudiante->id,
            ]);
            return;
        }

        // Verificar que la fecha de baja efectivamente llegó.
        // (doble verificación de seguridad)
        if (!$this->estudiante->bajaVencida()) {
            Log::warning('EgresadoCleanupJob: fecha_baja_programada no vencida.', [
                'estudiante_id'        => $this->estudiante->id,
                'fecha_baja_programada' => $this->estudiante->fecha_baja_programada,
            ]);
            return;
        }

        // Desactivar la cuenta del egresado.
        app(EstudianteRepository::class)->actualizar($this->estudiante, ['activo' => false]);

        // Registrar el evento en el log de auditoría.
        Log::info('EgresadoCleanupJob: cuenta desactivada (RF-18).', [
            'estudiante_id'        => $this->estudiante->id,
            'fecha_baja_programada' => $this->estudiante->fecha_baja_programada->toDateString(),
            'fecha_ejecucion'       => now()->toDateTimeString(),
        ]);
    }

    /**
     * Manejo de errores: se ejecuta cuando el job falla todos los reintentos.
     * Genera una alerta en el log para que el administrador tome acción manual.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('EgresadoCleanupJob FALLIDO después de todos los reintentos.', [
            'estudiante_id' => $this->estudiante->id,
            'error'         => $exception->getMessage(),
        ]);
        // TODO (I4): enviar notificación por email al administrador del sistema.
    }
}
