<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: config_institucion
 * -------------------------------------------------------------------------
 * Grupo 5 — Configuración y Diagnósticos | Tabla 21 de 21 ★ NUEVA v1.2
 *
 * Configuración operativa singleton por institución (RF-16).
 *
 * PATRÓN SINGLETON POR INSTITUCIÓN:
 * La restricción UNIQUE(institucion_id) garantiza que exista a lo sumo
 * UN registro de configuración por institución. Esto simplifica las
 * consultas: no se necesita filtrar por versión ni por fecha.
 *
 * CREACIÓN AUTOMÁTICA (CU-19 / CU-27):
 * Cuando se registra una nueva institución, InstitucionController (o
 * MatriculaService) crea automáticamente su config_institucion con
 * los valores por defecto. La Administradora puede modificarlos luego
 * mediante CU-25 (Configurar parámetros institucionales).
 *
 * PARÁMETROS Y SUS EFECTOS:
 *
 *   dias_reinicio_racha_default (RN-08):
 *     → Días de inactividad que reinician la racha diaria.
 *     → Aplica cuando juegos.dias_reinicio_racha IS NULL.
 *     → Default: 3 días.
 *
 *   permitir_acceso_grados_ant (RF-17):
 *     → Si TRUE, el estudiante puede ver su progreso de grados anteriores.
 *     → Si FALSE (default), solo ve el grado activo.
 *     → El docente SIEMPRE ve todos los grados, independientemente.
 *
 *   meses_conservacion_egresados (RN-10):
 *     → Meses que se conserva la cuenta de un egresado de 3° grado.
 *     → Después de ese período, EgresadoCleanupJob desactiva la cuenta.
 *     → Default: 4 meses.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 21 de 21
 * CU relacionados: CU-25 (Configurar parámetros institucionales)
 * RF relacionados: RF-16, RF-17, RF-18
 * RN relacionadas: RN-08, RN-10
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_institucion', function (Blueprint $table) {

            $table->id();

            // ── Relación singleton con institución ────────────────────
            $table->foreignId('institucion_id')
                  ->unique()   // ← garantiza el patrón singleton
                  ->constrained('instituciones')
                  ->restrictOnDelete();
                // RESTRICT: no se puede eliminar la institución si tiene config.
                // Primero desactivar la institución, luego gestionar su config.

            // ── Parámetro 1: Reinicio de racha (RN-08) ───────────────
            $table->unsignedTinyInteger('dias_reinicio_racha_default')->default(3);
                // Rango válido: 1-30 días.
                // 3 días = default conservador (fines de semana sin penalizar).
                // Validar en el FormRequest: between:1,30

            // ── Parámetro 2: Visibilidad de grados (RF-17) ────────────
            $table->boolean('permitir_acceso_grados_ant')->default(false);
                // FALSE = el estudiante solo ve el grado de su matrícula activa.
                // TRUE  = puede comparar su progreso entre grados.
                // Recomendación: TRUE solo si el docente lo solicita.

            // ── Parámetro 3: Conservación de egresados (RN-10) ────────
            $table->unsignedTinyInteger('meses_conservacion_egresados')->default(4);
                // Rango válido: 1-12 meses.
                // 4 meses = permite recuperar la cuenta hasta fin del año lectivo.
                // Después de este período, EgresadoCleanupJob desactiva la cuenta.
                // Validar en el FormRequest: between:1,12

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
                // created_at: cuando se creó la config (al registrar la institución).
                // updated_at: última vez que la Administradora modificó algún parámetro.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_institucion');
    }
};
