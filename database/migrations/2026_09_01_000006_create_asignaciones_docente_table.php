<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: asignaciones_docente
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 6 de 21
 *
 * Registra la asignación de un docente a una institución y grado específico.
 * Permite gestionar traslados, reemplazos y cambios de turno a lo largo
 * del tiempo, manteniendo un histórico completo.
 *
 * UNA ASIGNACIÓN ACTIVA (activo=TRUE) determina:
 *   - Qué estudiantes puede ver el docente en su panel
 *   - En qué institución puede crear cuentas de estudiantes
 *   - El contexto para habilitar/deshabilitar juegos por grado
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 5 de 21
 * RF relacionados: RF-08, RF-14
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_docente', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->restrictOnDelete();
                // RESTRICT: no se puede eliminar un docente con asignaciones.

            $table->foreignId('institucion_id')
                  ->constrained('instituciones')
                  ->restrictOnDelete();
                // RESTRICT: la institución debe existir durante toda la asignación.

            // ── Contexto pedagógico ───────────────────────────────────
            $table->unsignedTinyInteger('grado');
                // Grado asignado: 1, 2 o 3 (primer ciclo EEB paraguaya).
                // Determina qué niveles de juego verán sus estudiantes.

            $table->enum('turno', ['mañana', 'tarde', 'noche'])->default('mañana');
                // Turno en que trabaja el docente en esta institución.

            $table->year('anio_lectivo');
                // Año escolar de la asignación (ej: 2026).
                // Permite histórico año a año sin pisar datos anteriores.

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // TRUE = asignación vigente (solo UNA activa por docente/inst/grado).
                // FALSE = asignación histórica (traslado, reemplazo, baja).

            $table->enum('motivo_baja', ['renuncia', 'traslado', 'jubilacion', 'otro'])
                  ->nullable();
                // Se completa cuando activo pasa a FALSE. Auditoría.

            // ── Vigencia ──────────────────────────────────────────────
            $table->date('fecha_inicio');
                // Inicio formal de la asignación en esta institución/grado.

            $table->date('fecha_fin')->nullable();
                // NULL = asignación vigente. Se completa al cerrarla.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_docente');
    }
};
