<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: logros_obtenidos
 * -------------------------------------------------------------------------
 * Grupo 4 — Logros | Tabla 17 de 21
 *
 * Registra los logros que cada estudiante ha obtenido efectivamente.
 *
 * REGLA DE NEGOCIO RN-09 — Un logro por juego:
 * La restricción UNIQUE(estudiante_id, logro_id, juego_id) garantiza que
 * un mismo logro NO puede obtenerse dos veces en el mismo juego.
 * SÍ puede obtenerse una vez en cada juego diferente.
 *
 * Ejemplo:
 *   Logro "Racha de 10" en Juego "Suma de Frutas" → 1 vez MÁXIMO
 *   Logro "Racha de 10" en Juego "Resta Mágica"   → 1 vez MÁXIMO (ok)
 *   Total posible del mismo logro: tantos juegos como existan.
 *
 * juego_id = NULL: logros globales no vinculados a un juego específico.
 * El UNIQUE aplica igualmente: (estudiante_id, logro_id, NULL) es único.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 17 de 21
 * RF relacionados: RF-03 (gamificación)
 * RN relacionadas: RN-09 (unicidad de logros por juego)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logros_obtenidos', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();

            $table->foreignId('logro_id')
                  ->constrained('catalogo_logros')
                  ->restrictOnDelete();
                // RESTRICT: el logro debe seguir existiendo en el catálogo.

            $table->foreignId('sesion_id')
                  ->nullable()
                  ->constrained('sesiones_juego')
                  ->nullOnDelete();
                // Sesión en la que se obtuvo el logro. NULL si fue asíncrono.

            $table->foreignId('juego_id')
                  ->nullable()
                  ->constrained('juegos')
                  ->nullOnDelete();
                // RN-09: NULL = logro global. El UNIQUE se aplica sobre este campo.

            // ── Valor registrado al momento de obtención ──────────────
            $table->unsignedInteger('valor_registrado')->default(0);
                // El valor que activó la condición.
                // Ej: condicion_tipo='aciertos_consecutivos' → valor_registrado=10.
                // Permite saber por cuánto se obtuvo el logro.

            // ── Timestamp ────────────────────────────────────────────
            $table->timestamp('fecha_obtenido')->useCurrent();
                // Momento exacto de obtención. Se muestra en el panel.

            // ── Restricción de unicidad (RN-09) ───────────────────────
            // IMPORTANTE: en MySQL, NULL != NULL en índices únicos,
            // por lo que UNIQUE(est, logro, NULL) funciona correctamente.
            $table->unique(
                ['estudiante_id', 'logro_id', 'juego_id'],
                'uq_logro_est_logro_juego'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logros_obtenidos');
    }
};
