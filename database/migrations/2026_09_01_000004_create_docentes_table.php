<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: docentes
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 4 de 21
 *
 * Perfil extendido de los docentes. Extiende la tabla 'usuarios' mediante
 * una relación 1:1 usando usuario_id como FK única.
 *
 * PATRÓN DE DISEÑO — Herencia de tabla única extendida:
 * En lugar de poner todos los campos del docente en 'usuarios' (que
 * haría la tabla genérica y difícil de mantener), se usa una tabla
 * satélite 'docentes' que agrega solo la información específica del rol.
 * Esto sigue el principio de Separación de Responsabilidades (SRP).
 *
 * El id de esta tabla (docentes.id) es el que se referencia en:
 *   - asignaciones_docente.docente_id
 *   - matriculas.docente_id
 *   - config_docente_juego.docente_id
 *   - diagnosticos.docente_id
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 3 de 21
 * RF relacionados: RF-08 (panel docente), RF-10 (diagnóstico IA)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {

            // ── Clave primaria ────────────────────────────────────────
            $table->id();

            // ── Relación con usuarios (1:1) ───────────────────────────
            $table->foreignId('usuario_id')
                  ->unique()         // Garantiza la relación 1:1
                  ->constrained('usuarios')
                  ->restrictOnDelete();
                // RESTRICT: no se puede eliminar el usuario si tiene docente.
                // Usar desactivación (activo=FALSE) en lugar de eliminación.

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // Permite desactivar el perfil docente sin eliminar
                // el usuario ni las asignaciones históricas.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
