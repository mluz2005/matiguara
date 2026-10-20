<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: movimientos_sesion
 * -------------------------------------------------------------------------
 * Grupo 3 — Progreso | Tabla 15 de 21
 *
 * GRANULARIDAD MÁXIMA: cada fila es una respuesta individual del estudiante.
 * Es la tabla más importante para el análisis pedagógico y el diagnóstico IA.
 *
 * FLUJO DE DATOS PARA EL DIAGNÓSTICO IA (RF-10, RN-01):
 * 1. El docente solicita un diagnóstico de un estudiante
 * 2. DiagnosticoIAService consulta esta tabla filtrando por:
 *    - sesiones donde sesion.estudiante_id → estudiante.id_anonimizado
 *    - Agrupa errores por tipo_error para detectar patrones
 *    - Calcula tiempo_respuesta_ms promedio para detectar dificultad
 * 3. Los datos enviados a la API son ANONIMIZADOS: solo id_anonimizado
 *
 * TIPOS DE ERROR:
 *   'conceptual'  → el estudiante no comprende la operación
 *   'calculo'     → comprende pero se equivoca al calcular
 *   'lectura'     → dificultad para leer el enunciado (bilingüe)
 *   'atencion'    → respuesta muy rápida o errática (distracción)
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 15 de 21
 * RF relacionados: RF-07, RF-10
 * RN relacionadas: RN-01
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_sesion', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('sesion_id')
                  ->constrained('sesiones_juego')
                  ->cascadeOnDelete();
                // Movimiento pertenece a una sesión. Si la sesión se elimina,
                // se eliminan sus movimientos (datos del egresado).

            $table->foreignId('contenido_id')
                  ->constrained('contenido_nivel')
                  ->restrictOnDelete();
                // No se puede eliminar un ejercicio si tiene respuestas registradas.

            // ── Detalle de la respuesta ───────────────────────────────
            $table->unsignedTinyInteger('numero_intento')->default(1);
                // 1 = primer intento, 2 = segundo intento, etc.
                // Permite detectar cuántos reintentos necesita el estudiante.

            $table->string('respuesta_dada', 20);
                // Respuesta ingresada por el estudiante (texto o número como string).
                // Comparada contra contenido_nivel.respuesta_correcta.

            $table->boolean('es_correcto');
                // TRUE si respuesta_dada == contenido_nivel.respuesta_correcta.

            // ── Métricas de tiempo ────────────────────────────────────
            $table->unsignedInteger('tiempo_respuesta_ms')->nullable();
                // Tiempo entre que se mostró el ejercicio y el estudiante respondió.
                // En milisegundos. NULL si no se pudo capturar (problema de red).
                // Indicador de dificultad: respuestas muy lentas → ejercicio difícil.

            // ── Clasificación del error ───────────────────────────────
            $table->string('tipo_error', 30)->nullable();
                // Solo se completa cuando es_correcto = FALSE.
                // Valores: 'conceptual', 'calculo', 'lectura', 'atencion'.
                // Clasificado automáticamente por la lógica del sistema o
                // inferido por el diagnóstico IA al analizar patrones.

            // ── Timestamp del movimiento ──────────────────────────────
            $table->timestamp('timestamp_mov')->useCurrent();
                // Momento exacto de la respuesta. Permite reconstruir la
                // secuencia temporal de la sesión para análisis.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_sesion');
    }
};
