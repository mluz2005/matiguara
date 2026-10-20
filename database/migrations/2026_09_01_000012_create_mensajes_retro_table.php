<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: mensajes_retro
 * -------------------------------------------------------------------------
 * Grupo 3 — Progreso | Tabla 12 de 21
 *
 * Catálogo de mensajes de retroalimentación positiva para mostrar al
 * estudiante después de cada respuesta. Todos bilingües (RF-05, RN-06).
 *
 * PEDAGOGÍA DE LA RETROALIMENTACIÓN:
 * Los mensajes son siempre positivos y motivadores, incluso ante errores.
 * La investigación en educación infantil muestra que el refuerzo positivo
 * es más efectivo que la crítica para mantener la motivación (RF-03).
 *
 * SISTEMA DE NO-REPETICIÓN:
 * La tabla progreso_estudiante.ultimo_mensaje_id registra el último
 * mensaje mostrado. El RetroalimentacionService selecciona aleatoriamente
 * entre todos los mensajes activos del contexto, excluyendo el último,
 * para evitar que el mismo mensaje aparezca dos veces seguidas.
 *
 * LÍMITE DE 160 CARACTERES:
 * Los mensajes son breves por diseño: los niños de 6-9 años necesitan
 * retroalimentación rápida y clara. 160 chars = 2-3 oraciones cortas.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 12 de 21
 * RF relacionados: RF-05 (mensajes bilingüe simultáneos)
 * RN relacionadas: RN-06 (bilingüismo simultáneo obligatorio)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_retro', function (Blueprint $table) {

            $table->id();

            // ── Clasificación del mensaje ─────────────────────────────
            $table->enum('tipo', [
                'acierto',        // Respuesta correcta
                'error',          // Respuesta incorrecta (siempre positivo)
                'racha',          // El estudiante mantiene una racha activa
                'nivel_superado', // ¡Superó el nivel!
                'inicio_sesion',  // Mensaje de bienvenida al iniciar
            ]);

            $table->enum('contexto', [
                'general',        // Contexto por defecto
                'primer_intento', // Primera vez que intenta el ejercicio
                'reintento',      // Intenta por segunda vez o más
                'maximo_racha',   // Alcanzó su racha máxima histórica
            ])->default('general');
                // El contexto refina la selección: un error en el primer intento
                // recibe un mensaje diferente que un error en el quinto intento.

            // ── Contenido bilingüe (RN-06) ────────────────────────────
            $table->string('texto_es', 160);
                // Máximo 160 caracteres. Lenguaje simple, motivador.
                // Ej: "¡Muy bien! Eso estuvo excelente. Seguí así."

            $table->string('texto_gn', 160);
                // Traducción en guaraní. Misma longitud máxima.
                // Ej: "Iporãve! Upéicha avei. Ejapyhy ag̃ague."

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // Permite desactivar mensajes sin eliminarlos.
                // Garantiza que cada contexto siempre tenga al menos 2 activos.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_retro');
    }
};
