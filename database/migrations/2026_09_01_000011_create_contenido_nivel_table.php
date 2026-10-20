<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: contenido_nivel
 * -------------------------------------------------------------------------
 * Grupo 2 — Juegos | Tabla 11 de 21
 *
 * Ejercicios individuales que componen cada nivel. Es el contenido
 * pedagógico más granular del sistema: cada fila es un ejercicio.
 *
 * ESTRUCTURA DE UN EJERCICIO:
 *   valor_a [operador] valor_b = respuesta_correcta
 *   Ej: 7 + 5 = 12
 *
 * BILINGÜISMO COMPLETO (RF-02, RF-05, RN-06):
 *   - enunciado_es: "¿Cuánto es siete más cinco?"
 *   - enunciado_gn: "¿Mboy'e pa siete más cinco?"
 *
 * AUDIO (RF-06):
 *   - audio_es / audio_gn: rutas relativas a archivos .mp3
 *   - Permiten la lectura en voz alta para estudiantes lectores emergentes
 *
 * OPCIONES MÚLTIPLES:
 *   - opciones_json: array JSON con opciones de respuesta para mostrar botones
 *   - Ej: [12, 11, 13, 10] — el sistema muestra 4 opciones y el estudiante elige
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 11 de 21
 * RF relacionados: RF-02, RF-05, RF-06
 * RN relacionadas: RN-06
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenido_nivel', function (Blueprint $table) {

            $table->id();

            // ── Relación con nivel ────────────────────────────────────
            $table->foreignId('nivel_id')
                  ->constrained('niveles')
                  ->cascadeOnDelete();
                // Si se elimina un nivel, se eliminan todos sus ejercicios.

            // ── Tipo de ejercicio ─────────────────────────────────────
            $table->string('tipo_operacion', 30);
                // Detalla la operación específica: 'suma_simple', 'resta_con_llevada',
                // 'comparacion_numeros', 'descomposicion_posicional', etc.
                // Usado por el diagnóstico IA para clasificar errores.

            // ── Enunciado bilingüe (RN-06) ────────────────────────────
            $table->text('enunciado_es');
                // Enunciado completo en castellano. Lenguaje simple y claro.

            $table->text('enunciado_gn');
                // Enunciado en guaraní. Validado por docente bilingüe.

            // ── Operandos matemáticos ─────────────────────────────────
            $table->unsignedSmallInteger('valor_a')->nullable();
                // Primer número del ejercicio. NULL para ejercicios narrativos.

            $table->string('operador', 3)->nullable();
                // Operador matemático: '+', '-', '×', '÷'.
                // NULL para ejercicios de comparación o descomposición.

            $table->unsignedSmallInteger('valor_b')->nullable();
                // Segundo número del ejercicio.

            $table->string('respuesta_correcta', 20);
                // Respuesta esperada (siempre como string para flexibilidad).
                // Puede ser un número o una expresión: "12", "mayor", "7+5".

            // ── Opciones de respuesta ─────────────────────────────────
            $table->json('opciones_json')->nullable();
                // Array JSON con opciones de respuesta múltiple.
                // Ej: [12, 11, 13, 10]
                // NULL si el ejercicio es de respuesta abierta (campo de texto).

            // ── Presentación ──────────────────────────────────────────
            $table->unsignedTinyInteger('orden')->default(1);
                // Orden de presentación dentro del nivel.
                // El sistema selecciona ejercicios en este orden.

            // ── Audio (RF-06) ─────────────────────────────────────────
            $table->string('audio_es', 255)->nullable();
                // Ruta relativa al archivo de audio en castellano.
                // Ej: 'audio/es/nivel1/ejercicio1.mp3'

            $table->string('audio_gn', 255)->nullable();
                // Ruta relativa al archivo de audio en guaraní.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenido_nivel');
    }
};
