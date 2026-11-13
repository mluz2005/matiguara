<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: segundos extra de Flash Matemático (por institución)
 * -------------------------------------------------------------------------
 * Los tiempos de exposición de los números de Flash Matemático están en
 * niveles.tiempo_mostrar_ms. Para poder afinarlos con el uso, sin tocar código
 * ni esa tabla, la administración define un valor que se SUMA a todos los
 * niveles de Flash: cada unidad = 1 segundo (puede ser negativo para restar).
 *
 *   segundos_extra_flash → entero de -5 a 20 (defecto 0 = tiempos actuales).
 *
 * Solo afecta a Flash Matemático. Lo cambia únicamente el administrador, en
 * "Instituciones → Configuración".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('config_institucion')) return;

        Schema::table('config_institucion', function (Blueprint $table) {
            if (!Schema::hasColumn('config_institucion', 'segundos_extra_flash')) {
                $table->tinyInteger('segundos_extra_flash')->default(0);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_institucion')) return;

        Schema::table('config_institucion', function (Blueprint $table) {
            if (Schema::hasColumn('config_institucion', 'segundos_extra_flash')) {
                $table->dropColumn('segundos_extra_flash');
            }
        });
    }
};
