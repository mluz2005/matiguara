<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: catalogo_logros
 * -------------------------------------------------------------------------
 * Grupo 4 — Logros | Tabla 16 de 21
 *
 * Catálogo maestro de logros/insignias disponibles en el sistema.
 * Cada logro define las condiciones para obtenerlo.
 *
 * SISTEMA DE EVALUACIÓN DE CONDICIONES:
 * El LogroService evalúa estas condiciones después de cada sesión:
 *   condicion_tipo='aciertos_consecutivos', condicion_valor=10
 *     → si racha_actual_aciertos >= 10, se otorga el logro
 *   condicion_tipo='nivel_superado', condicion_valor=3
 *     → si el estudiante superó al menos 3 niveles distintos, se otorga
 *
 * RAREZA:
 *   'comun'     → fácil de obtener, motivación inicial
 *   'raro'      → requiere constancia
 *   'epico'     → desafío significativo
 *   'legendario'→ hito especial (ej: completar todos los niveles del sistema)
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 16 de 21
 * RF relacionados: RF-03 (gamificación)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_logros', function (Blueprint $table) {

            $table->id();

            // ── Nombre bilingüe ───────────────────────────────────────
            $table->string('nombre_es', 100);
                // Nombre del logro en castellano: "Racha Imparable", "Maestro de la Suma"

            $table->string('nombre_gn', 100);
                // Nombre del logro en guaraní. Siempre presente (RN-06).

            $table->text('descripcion_es')->nullable();
                // Descripción de cómo obtener el logro. Lenguaje simple.

            // ── Categorización ────────────────────────────────────────
            $table->enum('tipo', ['racha', 'puntaje', 'partidas', 'nivel', 'especial']);
                // Categoría principal del logro. Usada para filtros en el panel.

            // ── Condición de obtención ────────────────────────────────
            $table->string('condicion_tipo', 50);
                // Tipo de condición: 'aciertos_consecutivos', 'nivel_superado',
                // 'partidas_jugadas', 'puntaje_total', 'dias_consecutivos'.

            $table->unsignedInteger('condicion_valor');
                // Umbral numérico para la condición.
                // Ej: condicion_tipo='aciertos_consecutivos', condicion_valor=10
                //     → necesita 10 aciertos seguidos.

            // ── Recompensa y rareza ───────────────────────────────────
            $table->unsignedSmallInteger('puntos_logro')->default(0);
                // Puntos otorgados al obtener el logro. Para ranking futuro.

            $table->enum('rareza', ['comun', 'raro', 'epico', 'legendario'])
                  ->default('comun');

            $table->string('icono_url', 255)->nullable();
                // URL o ruta relativa al ícono SVG/PNG del logro.
                // Se muestra en el panel del estudiante y del padre.

            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_logros');
    }
};
