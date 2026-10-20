<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: matriculas
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 7 de 21
 *
 * Registra la matrícula anual de cada estudiante, vinculándolo a una
 * institución, un docente y un grado específico para un año lectivo.
 *
 * LA MATRÍCULA ACTIVA es el contexto del estudiante en el sistema:
 *   - Define el grado para filtrar niveles de juego (RF-17)
 *   - Define el docente que puede ver su progreso (RF-08)
 *   - Define la institución para aplicar config_institucion (RF-16)
 *
 * EVENTO DE EGRESO (RF-18):
 * Cuando se cierra la matrícula de grado=3 (fecha_fin se completa),
 * MatriculaService::cerrarMatricula() calcula la fecha_baja_programada
 * en la tabla 'estudiantes' y la dispara hacia EgresadoCleanupJob.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 7 de 21
 * RF relacionados: RF-17, RF-18
 * RN relacionadas: RN-10
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->restrictOnDelete();
                // RESTRICT: protege el historial del estudiante.

            $table->foreignId('institucion_id')
                  ->constrained('instituciones')
                  ->restrictOnDelete();

            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->restrictOnDelete();
                // El docente responsable del seguimiento del estudiante.

            // ── Contexto académico ────────────────────────────────────
            $table->unsignedTinyInteger('grado');
                // 1, 2 o 3. Crítico para filtrar contenido por grado (RF-17).

            $table->year('anio_lectivo');
                // Año lectivo: evita confundir matrículas de distintos años.

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // TRUE = matrícula vigente (estudiante en curso).
                // FALSE = año lectivo cerrado (histórico).

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
                // NULL mientras el período esté en curso.
                // Al completarla con grado=3, se activa el ciclo de vida RF-18.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
