<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: agregar tiempo_mostrar_ms a niveles
 * -------------------------------------------------------------------------
 * Agrega el campo que habilita el modo "Flash Matemático":
 * los números del ejercicio se muestran X milisegundos y luego desaparecen.
 *
 * DISEÑO PEDAGÓGICO:
 * Este campo implementa la mecánica de MEMORIA DE TRABAJO MATEMÁTICA.
 * El estudiante debe retener los operandos en su memoria de corto plazo
 * mientras calcula el resultado. Aumenta la demanda cognitiva sin
 * cambiar el contenido del ejercicio.
 *
 * CADENA DE DIFICULTAD CONFIRMADA EN EL TFG:
 *   NULL         = números siempre visibles (Suma de Frutas — todo nivel)
 *   8000 ms      = nivel 1 Flash Matemático (8 segundos)
 *   6000 ms      = nivel 2 Flash Matemático (6 segundos)
 *   5000 ms      = nivel 3 Flash Matemático (5 segundos)
 *   4000 ms      = nivel 4 Flash Matemático (4 segundos)
 *   3000 ms      = nivel 5 Flash Matemático (3 segundos — máximo desafío)
 *
 * IMPORTANTE: NULL no afecta en absoluto el juego "Suma de Frutas" ni
 * ningún juego existente — solo los niveles con valor explícito activan
 * el countdown visual en la vista de ejercicio.
 *
 * Referencia: Decisión de diseño — sesión de desarrollo sep 2026
 * RF relacionados: RF-04 (algoritmo adaptativo), RF-13 (progresión)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->unsignedInteger('tiempo_mostrar_ms')
                  ->nullable()
                  ->after('desbloqueado_por_defecto')
                  ->comment(
                      'Milisegundos que los operandos permanecen visibles. ' .
                      'NULL = siempre visibles. ' .
                      'Valor > 0 = Flash Matemático (desaparecen al vencer el tiempo).'
                  );
        });
    }

    public function down(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->dropColumn('tiempo_mostrar_ms');
        });
    }
};
