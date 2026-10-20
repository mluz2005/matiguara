<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: tipos_mecanica
 * -------------------------------------------------------------------------
 * Grupo 2 — Juegos | Tabla 8 de 21
 *
 * Catálogo de mecánicas de juego disponibles. Cada mecánica define la
 * operación matemática base que caracteriza a los juegos que la usan.
 *
 * Esta tabla es el primer nivel de la jerarquía de contenido:
 *   tipos_mecanica → juegos → niveles → contenido_nivel
 *
 * El algoritmo adaptativo (RF-04) selecciona ejercicios coherentes con
 * la mecánica del juego activo para mantener consistencia pedagógica.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 8 de 21
 * RF relacionados: RF-02 (banco bilingüe), RF-04 (algoritmo adaptativo)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_mecanica', function (Blueprint $table) {

            $table->id();

            $table->string('nombre', 100);
                // Nombre descriptivo: "Suma simple", "Resta con llevada", etc.

            $table->enum('tipo', ['suma', 'resta', 'multiplicacion', 'division', 'mixto']);
                // Operación matemática principal del grupo de juegos.
                // 'mixto' = combina varias operaciones en un mismo juego.

            $table->text('descripcion')->nullable();
                // Descripción pedagógica: cuándo usar esta mecánica, qué
                // habilidades cognitivas ejercita, nivel de dificultad base.

            $table->boolean('activo')->default(true);
                // Permite desactivar una mecánica sin eliminar sus juegos.
        });
        // Nota: sin timestamps porque este es un catálogo de configuración
        // que no cambia frecuentemente y no requiere auditoría de cambios.
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_mecanica');
    }
};
