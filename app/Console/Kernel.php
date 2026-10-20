<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
   protected function schedule(Schedule $schedule): void
{
    // Desactivar cuentas de egresados diariamente (RF-18, RN-10).
    // Un job por cada estudiante cuya baja programada ya venció (el job necesita
    // el estudiante: antes se creaba sin argumento y fallaba al ejecutarse).
    $schedule->call(function () {
                 app(\App\Repositories\EstudianteRepository::class)
                     ->conBajaVencida()
                     ->each(fn ($estudiante) => \App\Jobs\EgresadoCleanupJob::dispatch($estudiante));
             })
             ->name('egresados-cleanup')
             ->dailyAt('02:00')
             ->timezone('America/Asuncion')
             ->withoutOverlapping();
}

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
