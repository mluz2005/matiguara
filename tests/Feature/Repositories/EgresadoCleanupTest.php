<?php

namespace Tests\Feature\Repositories;

use App\Jobs\EgresadoCleanupJob;
use App\Models\Estudiante;
use App\Repositories\EstudianteRepository;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Baja automática de egresados (RF-18): repositorio, job y tarea programada.
 */
class EgresadoCleanupTest extends RepositorioTestCase
{
    private function alumno(string $codigo, array $cambios): Estudiante
    {
        $e = Estudiante::where('codigo_acceso', $codigo)->firstOrFail();
        $e->update($cambios);
        return $e->fresh();
    }

    public function test_con_baja_vencida_solo_trae_activos_con_fecha_cumplida()
    {
        $vencido   = $this->alumno('SAN-1A-001', ['fecha_baja_programada' => now()->subDay()->toDateString()]);
        $futuro    = $this->alumno('SAN-1A-002', ['fecha_baja_programada' => now()->addDays(10)->toDateString()]);
        $inactivo  = $this->alumno('SAN-1A-003', ['fecha_baja_programada' => now()->subDays(5)->toDateString(), 'activo' => false]);
        // SAN-2B-001 y SAN-2B-002: sin fecha programada

        $ids = app(EstudianteRepository::class)->conBajaVencida()->pluck('id')->all();

        $this->assertSame([$vencido->id], $ids);
    }

    public function test_el_job_desactiva_la_cuenta_cuando_la_baja_ya_vencio()
    {
        $e = $this->alumno('SAN-1A-001', ['fecha_baja_programada' => now()->subDay()->toDateString()]);

        (new EgresadoCleanupJob($e))->handle();

        $this->assertFalse((bool) $e->fresh()->activo);
    }

    public function test_el_job_no_toca_cuentas_con_baja_futura_o_ya_inactivas()
    {
        $futuro = $this->alumno('SAN-1A-002', ['fecha_baja_programada' => now()->addDays(3)->toDateString()]);
        (new EgresadoCleanupJob($futuro))->handle();
        $this->assertTrue((bool) $futuro->fresh()->activo);

        $inactivo = $this->alumno('SAN-1A-003', ['fecha_baja_programada' => now()->subDay()->toDateString(), 'activo' => false]);
        (new EgresadoCleanupJob($inactivo))->handle();
        $this->assertFalse((bool) $inactivo->fresh()->activo);
    }

    public function test_la_tarea_programada_desactiva_a_los_egresados_vencidos_y_solo_a_ellos()
    {
        $vencido = $this->alumno('SAN-1A-001', ['fecha_baja_programada' => now()->subDay()->toDateString()]);
        $futuro  = $this->alumno('SAN-1A-002', ['fecha_baja_programada' => now()->addDays(3)->toDateString()]);
        $sin     = $this->alumno('SAN-1A-003', ['fecha_baja_programada' => null]);

        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($ev) => $ev->description === 'egresados-cleanup');
        $this->assertNotNull($evento, 'La tarea programada existe.');
        $this->assertSame('0 2 * * *', $evento->expression);

        $evento->run($this->app);   // la cola es 'sync': el job corre en el acto

        $this->assertFalse((bool) $vencido->fresh()->activo);
        $this->assertTrue((bool) $futuro->fresh()->activo);
        $this->assertTrue((bool) $sin->fresh()->activo);
    }
}
