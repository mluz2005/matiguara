<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: índices de rendimiento adicionales
 * -------------------------------------------------------------------------
 * Esta migración agrega índices de consulta frecuente que no son parte
 * de las restricciones de integridad, sino de optimización de performance.
 *
 * Se definen en una migración separada para:
 *   a) Mantener las migraciones de tablas más legibles
 *   b) Poder agregar/quitar índices sin recrear tablas
 *   c) Documentar explícitamente por qué existe cada índice
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos
 * RNF relacionados: RNF-01 (tiempo de respuesta < 1s)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ESTUDIANTES ───────────────────────────────────────────────
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->index(['activo', 'fecha_baja_programada'], 'idx_est_activo_baja');
            // EgresadoCleanupJob consulta esto DIARIAMENTE:
            // WHERE activo = TRUE AND fecha_baja_programada <= CURDATE()
        });

        // ── MATRÍCULAS ────────────────────────────────────────────────
        Schema::table('matriculas', function (Blueprint $table) {
            $table->index(['anio_lectivo', 'grado'], 'idx_mat_anio_grado');
            // Filtrar matrículas del año actual para un grado específico.
            // Usado en PanelDocenteController para listar estudiantes.
        });

        // ── SESIONES DE JUEGO ─────────────────────────────────────────
        Schema::table('sesiones_juego', function (Blueprint $table) {
            $table->index(['estudiante_id', 'fecha_inicio'], 'idx_ses_est_fecha');
            // Listado de sesiones ordenadas por fecha (panel docente, RF-08).

            $table->index(['estado'], 'idx_ses_estado');
            // Recuperar sesiones 'en_curso' para detectar sesiones huérfanas.
        });

        // ── MOVIMIENTOS DE SESIÓN ─────────────────────────────────────
        Schema::table('movimientos_sesion', function (Blueprint $table) {
            $table->index(['sesion_id', 'timestamp_mov'], 'idx_mov_sesion_ts');
            // Reconstruir la secuencia temporal de respuestas de una sesión.
            // Usado por DiagnosticoIAService al preparar el prompt.
        });

        // ── PROGRESO ESTUDIANTE ───────────────────────────────────────
        Schema::table('progreso_estudiante', function (Blueprint $table) {
            $table->index(['estudiante_id', 'grado'], 'idx_prog_est_grado');
            // Filtrar progreso del grado activo del estudiante (RF-17).
            // Consulta muy frecuente: se ejecuta en cada carga del panel.
        });

        // ── LOGROS OBTENIDOS ──────────────────────────────────────────
        Schema::table('logros_obtenidos', function (Blueprint $table) {
            $table->index(['estudiante_id', 'fecha_obtenido'], 'idx_logros_est_fecha');
            // Listar logros recientes del estudiante (panel estudiante, RF-03).
        });

        // ── DIAGNÓSTICOS ──────────────────────────────────────────────
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->index(['estudiante_id', 'fecha_generacion'], 'idx_diag_est_fecha');
            // Listar diagnósticos de un estudiante ordenados cronológicamente.
            // Usado por CU-13 (Ver historial de diagnósticos).
        });
    }

    public function down(): void
    {
        // Eliminar índices en orden inverso para evitar errores de dependencia
        Schema::table('diagnosticos', fn($t) => $t->dropIndex('idx_diag_est_fecha'));
        Schema::table('logros_obtenidos', fn($t) => $t->dropIndex('idx_logros_est_fecha'));
        Schema::table('progreso_estudiante', fn($t) => $t->dropIndex('idx_prog_est_grado'));
        Schema::table('movimientos_sesion', fn($t) => $t->dropIndex('idx_mov_sesion_ts'));
        Schema::table('sesiones_juego', function($t) {
            $t->dropIndex('idx_ses_est_fecha');
            $t->dropIndex('idx_ses_estado');
        });
        Schema::table('matriculas', fn($t) => $t->dropIndex('idx_mat_anio_grado'));
        Schema::table('estudiantes', fn($t) => $t->dropIndex('idx_est_activo_baja'));
    }
};
