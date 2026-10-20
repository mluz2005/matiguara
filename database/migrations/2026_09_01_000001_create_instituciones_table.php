<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: instituciones
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 1 de 21
 *
 * Almacena las instituciones educativas registradas en el sistema.
 * Cada institución puede tener múltiples docentes asignados, estudiantes
 * matriculados y una configuración operativa propia (config_institucion).
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 1 de 21
 * RF relacionados: RF-14 (gestión de cuentas), RF-16 (configuración institucional)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla instituciones.
     */
    public function up(): void
    {
        Schema::create('instituciones', function (Blueprint $table) {

            // ── Clave primaria ────────────────────────────────────────
            $table->id(); // BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY

            // ── Datos identificadores ─────────────────────────────────
            $table->string('nombre', 200);
                // Nombre completo de la institución educativa.
                // Ejemplo: "Escuela Básica N° 123 San Roque González"

            $table->string('codigo', 20)->unique();
                // Código único asignado por el sistema o la UNAE.
                // Formato sugerido: SCH-ENC-001 (evita duplicados).

            $table->string('ciudad', 100);
                // Ciudad o localidad donde está ubicada la institución.

            $table->string('tipo', 50);
                // Tipo de institución: 'EEB', 'Colegio', 'Instituto', etc.
                // Determina el ciclo educativo que atiende.

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // TRUE mientras la institución esté operativa.
                // FALSE = desactivada (nunca se elimina físicamente: RN-07).

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
                // created_at: fecha de alta de la institución.
                // updated_at: fecha de última modificación del registro.
        });
    }

    /**
     * Revierte la migración: elimina la tabla instituciones.
     * Se ejecuta con: php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('instituciones');
    }
};
