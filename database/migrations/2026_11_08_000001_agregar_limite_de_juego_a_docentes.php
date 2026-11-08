<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: límite de tiempo de juego y pausa entre sesiones (por docente)
 * -------------------------------------------------------------------------
 * Para cuidar la salud de los niños, todos los juegos de MatiGuarã cortan
 * después de un tiempo de juego y piden un descanso antes de volver a jugar.
 * Los dos valores los define cada docente desde su panel ("Configurar juegos"):
 *
 *   limite_sesion_min        → minutos de juego activo por sesión (defecto 8).
 *   pausa_entre_sesiones_min → minutos de descanso obligatorio antes de que el
 *                              mismo alumno pueda volver a jugar (defecto 30).
 *
 * El valor 0 significa "sin límite" (en el límite) o "sin pausa" (en la pausa).
 * Se aplican a los alumnos con matrícula activa de ese docente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('docentes')) return;

        Schema::table('docentes', function (Blueprint $table) {
            if (!Schema::hasColumn('docentes', 'limite_sesion_min')) {
                $table->unsignedSmallInteger('limite_sesion_min')->default(8);
            }
            if (!Schema::hasColumn('docentes', 'pausa_entre_sesiones_min')) {
                $table->unsignedSmallInteger('pausa_entre_sesiones_min')->default(30);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('docentes')) return;

        Schema::table('docentes', function (Blueprint $table) {
            foreach (['limite_sesion_min', 'pausa_entre_sesiones_min'] as $col) {
                if (Schema::hasColumn('docentes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
