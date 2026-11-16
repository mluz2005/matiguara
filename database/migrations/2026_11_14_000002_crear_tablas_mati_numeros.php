<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: tablas de «Mati y los números» (juego de tamizaje)
 * -------------------------------------------------------------------------
 *  detalle_numeros     1:1 con sesiones_juego. Un «viaje» (sesión) del niño:
 *                      número de viaje y de ciclo, semilla, las preguntas que se
 *                      le mostraron en cada parte (para poder auditar), y el
 *                      resumen de la sesión (completa, confiable, % de toques al azar…).
 *  respuestas_numeros  una fila por pregunta respondida, con todo lo que pide el
 *                      requisito RF-06: ítem, parte, área, nivel, fija/adaptativa,
 *                      respuesta, acierto, tipo de error y tiempo.
 *
 * No se usan movimientos_sesion ni progreso_estudiante: es un tamizaje, no suma
 * puntos ni entra en el progreso del niño (RF-02). Las respuestas se borran en
 * cascada junto con la sesión (RF-21).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('detalle_numeros')) {
            Schema::create('detalle_numeros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sesion_id')->unique()->constrained('sesiones_juego')->cascadeOnDelete();

                $table->unsignedTinyInteger('viaje');                       // 1, 2 o 3
                $table->unsignedSmallInteger('ciclo')->default(1);          // 1, 2, 3…
                $table->unsignedInteger('semilla');
                $table->unsignedTinyInteger('grado');
                $table->longText('partes_json')->nullable();                // preguntas mostradas por parte (con nivel y respuesta correcta)

                $table->boolean('completa')->default(false);                // respondió TODAS las preguntas del viaje
                $table->boolean('confiable')->default(true);                // false si más de 35 % de toques al azar
                $table->unsignedTinyInteger('pct_azar')->default(0);        // % de respuestas en menos de 350 ms
                $table->unsignedInteger('tiempo_base_ms')->nullable();      // mediana de los toques del calentamiento
                $table->unsignedTinyInteger('nivel_final')->nullable();     // nivel adaptativo al terminar (1-3)
                $table->unsignedSmallInteger('duracion_s')->default(0);
                $table->unsignedSmallInteger('respondidas')->default(0);
                $table->unsignedSmallInteger('total_preguntas')->default(0);
                $table->boolean('valida')->default(true);                   // la docente puede marcarla como no válida (RF-20)
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('respuestas_numeros')) {
            Schema::create('respuestas_numeros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sesion_id')->constrained('sesiones_juego')->cascadeOnDelete();

                $table->unsignedTinyInteger('parte');
                $table->unsignedSmallInteger('orden');                      // posición dentro de la parte (desde 0)
                $table->string('item_clave', 60);
                $table->string('area', 30);
                $table->unsignedTinyInteger('nivel');                       // 1 inicial, 2 esperado, 3 ampliado
                $table->boolean('fija');                                    // pregunta fija (comparable) o adaptativa
                $table->boolean('cero')->default(false);                    // pregunta del concepto del cero
                $table->string('respuesta', 24);
                $table->boolean('es_correcto');
                $table->string('tipo_error', 24)->nullable();
                $table->unsignedInteger('tiempo_ms')->nullable();
                $table->boolean('azar')->default(false);                    // respuesta en menos de 350 ms
                $table->decimal('error_pct', 5, 1)->nullable();             // solo línea de números: error como % de la línea
                $table->timestamp('respondido_en')->nullable();

                $table->unique(['sesion_id', 'parte', 'orden']);
                $table->index(['sesion_id', 'area']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_numeros');
        Schema::dropIfExists('detalle_numeros');
    }
};
