<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: detalle_laberinto  (datos propios de «El camino a la escuela»)
 * -------------------------------------------------------------------------
 * Una fila por partida del laberinto (1:1 con sesiones_juego). Guarda lo que
 * las tablas genéricas no pueden: la semilla del laberinto (para rearmarlo
 * igual si el niño actualiza la página), las estrellas, las ayudas usadas y
 * las chipas. Los puntos, aciertos, errores y cada respuesta siguen yendo a
 * sesiones_juego y movimientos_sesion como en los demás juegos.
 *
 * La docente ve estos datos en el progreso del alumno (RF-L22).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('detalle_laberinto')) return;

        Schema::create('detalle_laberinto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_id')
                  ->unique()
                  ->constrained('sesiones_juego')
                  ->cascadeOnDelete();

            $table->unsignedInteger('semilla');                          // rearma el mismo laberinto
            $table->unsignedTinyInteger('obstaculos_total')->default(0);
            $table->unsignedTinyInteger('estrellas')->default(0);        // 0 mientras juega; 1-3 al llegar
            $table->unsignedTinyInteger('pistas_usadas')->default(0);    // ejercicios en los que vio la pista
            $table->boolean('uso_flecha')->default(false);               // pidió la flecha hacia la escuela
            $table->unsignedTinyInteger('soluciones_mostradas')->default(0); // obstáculos donde se mostró la solución
            $table->unsignedTinyInteger('chipas_recogidas')->default(0);
            $table->unsignedTinyInteger('chipas_total')->default(0);
            $table->unsignedSmallInteger('duracion_s')->default(0);
            $table->boolean('llego')->default(false);                    // llegó a la escuela
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_laberinto');
    }
};
