<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: crear tabla tarjetas_nivel
 * -------------------------------------------------------------------------
 * Pool de cartas disponibles para cada nivel del juego "Encuentra el par".
 *
 * FUNCIONAMIENTO:
 * Cada nivel tiene un POOL de tarjetas posibles (ej: 8 objetos disponibles
 * para un nivel de 5 pares). Al iniciar una sesión, el sistema selecciona
 * aleatoriamente N tarjetas del pool (N = pares del nivel), evitando que
 * el estudiante memorice la posición de una partida anterior.
 *
 * TIPOS DE CONTENIDO:
 *   'objeto' → material escolar (mochila, lápiz, regla...)
 *   'numero' → numeral emparejado con su representación en cantidad
 *              (la MISMA fila se usa para generar AMBAS cartas del par:
 *               una carta muestra el numeral, la otra la cantidad)
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Sección 10.4
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjetas_nivel', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nivel_id')
                  ->constrained('niveles')
                  ->cascadeOnDelete();

            $table->enum('tipo_contenido', ['objeto', 'numero'])
                  ->comment('objeto = material escolar | numero = numeral-cantidad');

            // Valor identificador: 'lapiz', 'mochila', '5', etc.
            $table->string('valor', 20);

            $table->string('nombre_es', 100);
            $table->string('nombre_gn', 100);

            // Emoji como placeholder visual (hasta tener assets SVG definitivos)
            $table->string('emoji', 10)->nullable();
            $table->string('imagen_url')->nullable();

            // Audio (Semana de grabación guaraní)
            $table->string('audio_es')->nullable();
            $table->string('audio_gn')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjetas_nivel');
    }
};
