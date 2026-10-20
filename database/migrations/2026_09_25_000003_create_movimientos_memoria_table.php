<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: crear tabla movimientos_memoria
 * -------------------------------------------------------------------------
 * Registra cada intento de par (2 cartas volteadas) en una sesión del
 * juego "Encuentra el par".
 *
 * POR QUÉ ES UNA TABLA SEPARADA de movimientos_sesion:
 * movimientos_sesion tiene una FK obligatoria hacia contenido_nivel
 * (diseñada para ejercicios matemáticos con enunciado/respuesta).
 * El juego de memoria no tiene "ejercicios" sino "pares de cartas",
 * por lo que se usa una tabla propia con su propia integridad referencial
 * hacia tarjetas_nivel, sin forzar ni modificar el esquema existente.
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Sección 10.5
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_memoria', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_id')
                  ->constrained('sesiones_juego')
                  ->cascadeOnDelete();

            $table->foreignId('tarjeta_a_id')
                  ->constrained('tarjetas_nivel');

            $table->foreignId('tarjeta_b_id')
                  ->constrained('tarjetas_nivel');

            $table->boolean('es_acierto');

            $table->unsignedInteger('tiempo_respuesta_ms')->nullable();

            $table->timestamp('timestamp_mov')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_memoria');
    }
};
