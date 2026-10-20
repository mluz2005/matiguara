<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: progreso_estudiante
 * -------------------------------------------------------------------------
 * Grupo 3 — Progreso | Tabla 13 de 21
 *
 * Acumula el historial de rendimiento de un estudiante en cada nivel
 * de juego, discriminado por grado escolar.
 *
 * ÍNDICE ÚNICO: (estudiante_id, juego_id, nivel_id, grado)
 * Garantiza un único registro por combinación. Las actualizaciones se
 * hacen con upsert (INSERT ... ON DUPLICATE KEY UPDATE en MySQL).
 * Laravel: updateOrCreate() o upsert()
 *
 * FILTRO POR GRADO ACTIVO (RF-17):
 * La columna 'grado' permite que:
 *   - El ESTUDIANTE vea solo su progreso del grado activo (matrícula)
 *   - El DOCENTE vea el historial completo de todos los grados
 * Esto evita que un alumno de 2° vea progreso de 1° (podría confundirlo),
 * mientras el docente puede analizar la evolución longitudinal.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 13 de 21
 * RF relacionados: RF-03, RF-08, RF-17
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progreso_estudiante', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();

            $table->foreignId('juego_id')
                  ->constrained('juegos')
                  ->restrictOnDelete();

            $table->foreignId('nivel_id')
                  ->constrained('niveles')
                  ->restrictOnDelete();

            // ── Grado (RF-17) ─────────────────────────────────────────
            $table->unsignedTinyInteger('grado');
                // Grado escolar en el que se generó este progreso.
                // Permite consultas filtradas: WHERE grado = matrícula_activa.grado

            // ── Métricas acumuladas ───────────────────────────────────
            $table->unsignedSmallInteger('mejor_puntaje')->default(0);
                // Mayor puntaje obtenido en este nivel. Se actualiza solo si mejora.

            $table->unsignedInteger('total_partidas')->default(0);
                // Número total de sesiones iniciadas en este nivel.

            $table->unsignedInteger('total_aciertos')->default(0);
                // Aciertos acumulados históricos en este nivel.
                // Usado por el diagnóstico IA para calcular porcentaje_aciertos.

            $table->boolean('nivel_desbloqueado')->default(false);
                // TRUE cuando el estudiante superó el nivel al menos una vez.
                // Habilita el acceso al nivel siguiente.

            // ── Último mensaje mostrado (sistema de no-repetición) ────
            $table->foreignId('ultimo_mensaje_id')
                  ->nullable()
                  ->constrained('mensajes_retro')
                  ->nullOnDelete();
                // SET NULL si el mensaje es eliminado del catálogo.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                // Solo updated_at: interesa saber cuándo fue el último progreso.

            // ── Índice único (clave de negocio) ───────────────────────
            $table->unique(
                ['estudiante_id', 'juego_id', 'nivel_id', 'grado'],
                'uq_progreso_est_jue_niv_gr'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progreso_estudiante');
    }
};
