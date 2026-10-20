<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: juegos
 * -------------------------------------------------------------------------
 * Grupo 2 — Juegos | Tabla 9 de 21
 *
 * Juegos educativos disponibles en la plataforma, todos completamente
 * bilingües (castellano + guaraní) conforme al mandato de RF-02 y RF-05.
 *
 * BILINGÜISMO (RF-02, RF-05, RN-06):
 * Cada juego tiene nombre e instrucciones en ambos idiomas. El sistema
 * muestra siempre ambas versiones de forma simultánea, nunca solo una.
 * La elección de idioma principal NO está en manos del estudiante.
 *
 * CONFIGURACIÓN DE RACHA (RN-08):
 * El campo dias_reinicio_racha permite configurar por juego cuántos días
 * de inactividad reinician la racha. Si es NULL, se usa el valor por
 * defecto de la institución (config_institucion.dias_reinicio_racha_default).
 * Cadena de consulta: juego → institución → valor por defecto (3 días).
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 9 de 21
 * RF relacionados: RF-02, RF-03, RF-05
 * RN relacionadas: RN-06, RN-08
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juegos', function (Blueprint $table) {

            $table->id();

            // ── Relación con mecánica ─────────────────────────────────
            $table->foreignId('tipo_mecanica_id')
                  ->constrained('tipos_mecanica')
                  ->restrictOnDelete();
                // No se puede eliminar una mecánica si tiene juegos activos.

            // ── Contenido bilingüe (RN-06) ────────────────────────────
            $table->string('nombre_es', 150);
                // Nombre en castellano: "Suma de Frutas", "Resta Mágica", etc.

            $table->string('nombre_gn', 150);
                // Nombre en guaraní: requiere validación por docente bilingüe.

            $table->text('instrucciones_es');
                // Instrucciones completas en castellano. Lenguaje simple (6-9 años).

            $table->text('instrucciones_gn');
                // Instrucciones completas en guaraní. Validadas por bilingüe.

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);

            // ── Configuración de racha (RN-08) ────────────────────────
            $table->unsignedTinyInteger('dias_reinicio_racha')->nullable();
                // NULL = usar config_institucion.dias_reinicio_racha_default.
                // Valor explícito = sobrescribe el default institucional.
                // Rango válido: 1-30 días.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juegos');
    }
};
