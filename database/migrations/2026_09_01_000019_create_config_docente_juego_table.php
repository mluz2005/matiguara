<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: config_docente_juego
 * -------------------------------------------------------------------------
 * Grupo 5 — Configuración y Diagnósticos | Tabla 19 de 21
 *
 * Permite al docente habilitar o deshabilitar juegos específicos para
 * sus estudiantes de un determinado grado (CU-08).
 *
 * POR DEFECTO todos los juegos activos están habilitados para todos los
 * grados. El docente puede desactivar juegos que no correspondan al
 * contenido curricular que está trabajando en ese momento.
 *
 * ÍNDICE ÚNICO: (docente_id, juego_id, grado)
 * Un solo registro de configuración por combinación.
 * Si no existe registro, se asume habilitado=TRUE (comportamiento por defecto).
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 19 de 21
 * CU relacionados: CU-08 (Habilitar juegos por grado)
 * RF relacionados: RF-14 (gestión de cuentas — docente gestiona contenido)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_docente_juego', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->cascadeOnDelete();
                // Si se elimina el docente, se eliminan sus configuraciones.

            $table->foreignId('juego_id')
                  ->constrained('juegos')
                  ->cascadeOnDelete();
                // Si se desactiva/elimina un juego, sus configs desaparecen.

            // ── Grado al que aplica ───────────────────────────────────
            $table->unsignedTinyInteger('grado');
                // La configuración aplica solo para el grado especificado.
                // El mismo docente puede habilitar un juego en 1° pero no en 2°.

            // ── Estado de habilitación ────────────────────────────────
            $table->boolean('habilitado')->default(true);
                // TRUE  = juego visible y jugable para los estudiantes del grado.
                // FALSE = juego oculto (el estudiante no lo ve en su pantalla).

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();

            // ── Índice único ──────────────────────────────────────────
            $table->unique(
                ['docente_id', 'juego_id', 'grado'],
                'uq_config_doc_jue_gr'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_docente_juego');
    }
};
