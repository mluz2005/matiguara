<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: agregar tiempo_limite_seg a niveles
 * -------------------------------------------------------------------------
 * Campo para el temporizador de nivel completo, usado por "Encuentra el par".
 *
 * DIFERENCIA con tiempo_mostrar_ms (Flash Matemático):
 *   tiempo_mostrar_ms  → cuánto tiempo quedan visibles los OPERANDOS
 *   tiempo_limite_seg  → cuánto tiempo total tiene el estudiante para
 *                        COMPLETAR TODO EL NIVEL (encontrar todas las parejas)
 *
 * NULL = el juego no usa temporizador de nivel (Suma de Frutas, Flash Matemático)
 * Valor > 0 = segundos totales disponibles para completar el nivel
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Sección 10.3
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->unsignedInteger('tiempo_limite_seg')
                  ->nullable()
                  ->after('tiempo_mostrar_ms')
                  ->comment(
                      'Segundos totales para completar el nivel. ' .
                      'NULL = sin límite de tiempo de nivel. ' .
                      'Usado por juegos de memoria de pares.'
                  );
        });
    }

    public function down(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->dropColumn('tiempo_limite_seg');
        });
    }
};
