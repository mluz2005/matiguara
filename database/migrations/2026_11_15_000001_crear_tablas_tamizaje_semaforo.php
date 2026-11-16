<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: semáforo, ficha y avisos de «Mati y los números» (Etapa 2)
 * -------------------------------------------------------------------------
 *  tamizaje_estados  el último semáforo calculado de cada niño (una fila por niño).
 *                    Sirve para comparar y avisar SOLO cuando el color empeora.
 *  tamizaje_avisos   los avisos de la campanita de la docente («prestá más atención a…»).
 *  tamizaje_fichas   observaciones opcionales de la docente (casillas + nota corta).
 *
 *  detalle_numeros        + motivo y fecha cuando la docente marca un viaje como no válido (RF-20).
 *  config_institucion     + cortes del semáforo (provisorios, ajustables por la administración).
 *
 * Nada de esto es un diagnóstico: son señales para que la docente mire con más atención.
 * Todo se borra en cascada si se borra al niño (RF-21).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tamizaje_estados')) {
            Schema::create('tamizaje_estados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('estudiante_id')->unique()->constrained('estudiantes')->cascadeOnDelete();
                $table->string('semaforo', 10)->nullable();                  // verde | amarillo | rojo | null = sin datos suficientes
                $table->unsignedSmallInteger('ciclos_completos')->default(0);
                $table->unsignedTinyInteger('ciclos_considerados')->default(0);
                $table->longText('resultado_json')->nullable();              // áreas, porcentajes y señales del último cálculo
                $table->timestamp('calculado_en')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tamizaje_avisos')) {
            Schema::create('tamizaje_avisos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
                $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
                $table->string('semaforo', 10);                              // amarillo | rojo (el color al que pasó)
                $table->unsignedSmallInteger('ciclos')->default(0);
                $table->timestamp('leido_en')->nullable();
                $table->timestamps();

                $table->index(['docente_id', 'leido_en']);
            });
        }

        if (!Schema::hasTable('tamizaje_fichas')) {
            Schema::create('tamizaje_fichas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('estudiante_id')->unique()->constrained('estudiantes')->cascadeOnDelete();
                $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
                $table->text('observaciones')->nullable();                   // lista JSON de casillas marcadas
                $table->string('nota', 300)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('detalle_numeros')) {
            Schema::table('detalle_numeros', function (Blueprint $table) {
                if (!Schema::hasColumn('detalle_numeros', 'motivo_no_valida')) {
                    $table->string('motivo_no_valida', 80)->nullable();
                }
                if (!Schema::hasColumn('detalle_numeros', 'marcada_no_valida_en')) {
                    $table->timestamp('marcada_no_valida_en')->nullable();
                }
            });
        }

        if (Schema::hasTable('config_institucion')) {
            Schema::table('config_institucion', function (Blueprint $table) {
                if (!Schema::hasColumn('config_institucion', 'tamizaje_corte_verde')) {
                    $table->unsignedTinyInteger('tamizaje_corte_verde')->default(75);
                }
                if (!Schema::hasColumn('config_institucion', 'tamizaje_corte_amarillo')) {
                    $table->unsignedTinyInteger('tamizaje_corte_amarillo')->default(60);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('config_institucion')) {
            Schema::table('config_institucion', function (Blueprint $table) {
                foreach (['tamizaje_corte_verde', 'tamizaje_corte_amarillo'] as $col) {
                    if (Schema::hasColumn('config_institucion', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('detalle_numeros')) {
            Schema::table('detalle_numeros', function (Blueprint $table) {
                foreach (['motivo_no_valida', 'marcada_no_valida_en'] as $col) {
                    if (Schema::hasColumn('detalle_numeros', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('tamizaje_fichas');
        Schema::dropIfExists('tamizaje_avisos');
        Schema::dropIfExists('tamizaje_estados');
    }
};
