<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: rachas
 * -------------------------------------------------------------------------
 * Grupo 4 — Logros | Tabla 18 de 21
 *
 * Almacena el estado actual y máximo de las rachas por estudiante/juego.
 * Hay dos tipos de rachas que se monitorean de forma independiente:
 *
 * RACHA DE ACIERTOS:
 *   Cuenta respuestas correctas CONSECUTIVAS dentro de una sesión.
 *   Se reinicia cuando el estudiante comete un error.
 *   Objetivo: recompensar precisión sostenida.
 *
 * RACHA DE DÍAS:
 *   Cuenta días CONSECUTIVOS en que el estudiante jugó al menos una sesión.
 *   Se reinicia si pasan más de N días sin jugar (RN-08 / dias_reinicio_racha).
 *   Objetivo: recompensar constancia y hábito de práctica.
 *
 * Los valores MÁXIMOS nunca decrementan: son el historial de mejor racha.
 * Los valores ACTUALES sí pueden reiniciarse.
 *
 * ÍNDICE ÚNICO: (estudiante_id, juego_id)
 * Un solo registro de racha por combinación. Se actualiza con cada sesión.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 18 de 21
 * RF relacionados: RF-03 (gamificación)
 * RN relacionadas: RN-08 (reinicio de racha)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rachas', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();

            $table->foreignId('juego_id')
                  ->constrained('juegos')
                  ->cascadeOnDelete();

            // ── Racha de aciertos consecutivos ────────────────────────
            $table->unsignedSmallInteger('racha_actual_aciertos')->default(0);
                // Contador activo de aciertos consecutivos en el juego.
                // Se reinicia al primer error. Máximo teórico: ilimitado.

            $table->unsignedSmallInteger('racha_maxima_aciertos')->default(0);
                // Mejor racha de aciertos jamás alcanzada. Solo sube, nunca baja.

            // ── Racha de días consecutivos ────────────────────────────
            $table->unsignedSmallInteger('racha_actual_dias')->default(0);
                // Días consecutivos con al menos una sesión en este juego.
                // Se reinicia si pasan N días sin jugar (RN-08).

            $table->unsignedSmallInteger('racha_maxima_dias')->default(0);
                // Mejor racha de días alcanzada. Referencia histórica.

            // ── Control del reinicio de racha de días (RN-08) ─────────
            $table->date('ultima_sesion_fecha')->nullable();
                // Fecha de la última sesión jugada en este juego.
                // Comparada contra la fecha actual para determinar si
                // la racha de días debe continuar o reiniciarse.
                // NULL = el estudiante nunca jugó este juego.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ── Índice único ──────────────────────────────────────────
            $table->unique(['estudiante_id', 'juego_id'], 'uq_racha_est_juego');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rachas');
    }
};
