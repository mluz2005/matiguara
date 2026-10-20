<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: sesiones_juego
 * -------------------------------------------------------------------------
 * Grupo 3 — Progreso | Tabla 14 de 21
 *
 * Registra cada partida individual del estudiante. Una sesión empieza
 * cuando el estudiante inicia un nivel y termina cuando lo completa,
 * abandona o supera el tiempo máximo de inactividad.
 *
 * ESTADOS DE LA SESIÓN (ver DE-01 — Diagrama de Estados):
 *   en_curso   → sesión iniciada, estudiante respondiendo
 *   completada → el estudiante terminó todos los ejercicios del nivel
 *   abandonada → el estudiante salió sin terminar (timeout o navegación)
 *
 * ANONIMIZACIÓN (RF-07, RN-01):
 * Las sesiones se registran vinculadas al estudiante_id interno.
 * Cuando el diagnóstico IA consulta estas sesiones, usa el
 * id_anonimizado del estudiante, nunca el estudiante_id real.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 14 de 21
 * RF relacionados: RF-07 (registro anonimizado), RF-08 (panel docente)
 * RN relacionadas: RN-01 (anonimización obligatoria)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_juego', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();
                // Si se elimina un estudiante egresado, se eliminan sus sesiones.

            $table->foreignId('juego_id')
                  ->constrained('juegos')
                  ->restrictOnDelete();

            $table->foreignId('nivel_id')
                  ->constrained('niveles')
                  ->restrictOnDelete();

            // ── Temporalidad de la sesión ─────────────────────────────
            $table->timestamp('fecha_inicio')->useCurrent();
                // Momento exacto en que el estudiante pulsó "Jugar".

            $table->timestamp('fecha_fin')->nullable();
                // NULL mientras la sesión esté en_curso.
                // Se completa al cambiar estado a 'completada' o 'abandonada'.

            // ── Resultados ────────────────────────────────────────────
            $table->unsignedSmallInteger('puntaje_obtenido')->default(0);
                // Puntaje acumulado durante la sesión.
                // Calculado por el motor de gamificación (RF-03).

            $table->unsignedTinyInteger('aciertos')->default(0);
                // Cantidad de respuestas correctas en esta sesión.

            $table->unsignedTinyInteger('errores')->default(0);
                // Cantidad de respuestas incorrectas en esta sesión.

            $table->boolean('nivel_superado')->default(false);
                // TRUE si el estudiante alcanzó min_aciertos_pasar del nivel.
                // Activa el desbloqueo del nivel siguiente.

            // ── Estado (ver DE-01) ────────────────────────────────────
            $table->enum('estado', ['en_curso', 'completada', 'abandonada'])
                  ->default('en_curso');

            // ── Contexto del dispositivo ──────────────────────────────
            $table->string('device_tipo', 20)->nullable();
                // 'mobile', 'tablet', 'desktop'.
                // Informa sobre el entorno de uso del estudiante.
                // Útil para análisis de usabilidad (SUS, RF-03).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_juego');
    }
};
