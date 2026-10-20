<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: padres
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 5 de 21
 *
 * Vincula un usuario con perfil 'padre' a un estudiante específico.
 * El padre accede EXCLUSIVAMENTE al panel de progreso de su hijo/a (RN-04).
 *
 * ACCESO RESTRINGIDO (RN-04):
 * El perfil padre no puede ver información de otros estudiantes ni
 * acceder a funcionalidades de docente o administración. Solo ve:
 *   - Progreso y puntajes del estudiante vinculado (RF-09)
 *   - Logros obtenidos por el estudiante vinculado
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 4 de 21
 * RF relacionados: RF-09 (panel parental)
 * RN relacionadas: RN-04 (acceso restringido parental)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padres', function (Blueprint $table) {

            $table->id();

            // ── Relación con usuarios ─────────────────────────────────
            $table->foreignId('usuario_id')
                  ->constrained('usuarios')
                  ->cascadeOnDelete();
                // Si se elimina el usuario, se elimina el vínculo padre-hijo.

            // ── Relación con estudiante ───────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();
                // El padre ve únicamente este estudiante (RN-04).

            // ── Tipo de vínculo ───────────────────────────────────────
            $table->string('relacion', 20)->default('padre');
                // Vínculo familiar: 'padre', 'madre', 'tutor'.
                // Informa al docente sobre la relación sin afectar el acceso.

            $table->timestamp('created_at')->useCurrent();
                // Solo created_at: la vinculación no se actualiza, se recrea.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('padres');
    }
};
