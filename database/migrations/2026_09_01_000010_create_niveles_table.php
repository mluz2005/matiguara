<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: niveles
 * -------------------------------------------------------------------------
 * Grupo 2 — Juegos | Tabla 10 de 21
 *
 * Define los niveles de dificultad progresiva dentro de cada juego,
 * organizados por grado escolar (1°, 2° o 3°).
 *
 * SISTEMA ADAPTATIVO (RF-04, RF-13):
 * El algoritmo 3/2 opera SOBRE los niveles:
 *   - 3 aciertos consecutivos → sube al siguiente nivel (subirNivel)
 *   - 2 errores consecutivos  → baja al nivel anterior (bajarNivel)
 * La dificultad (1-5) guía la selección de ejercicios del nivel.
 *
 * DESBLOQUEO PROGRESIVO:
 * Solo el nivel 1 de cada grado es accesible desde el inicio
 * (desbloqueado_por_defecto=TRUE). Los demás se desbloquean cuando
 * el estudiante supera el nivel anterior (nivel_superado=TRUE en sesión).
 *
 * ÍNDICE ÚNICO: (juego_id, grado, numero_nivel) garantiza que no
 * existan dos niveles con el mismo número para el mismo juego y grado.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 10 de 21
 * RF relacionados: RF-04, RF-13
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles', function (Blueprint $table) {

            $table->id();

            // ── Relación con juego ────────────────────────────────────
            $table->foreignId('juego_id')
                  ->constrained('juegos')
                  ->cascadeOnDelete();
                // Si se elimina un juego, se eliminan todos sus niveles.

            // ── Contexto pedagógico ───────────────────────────────────
            $table->unsignedTinyInteger('grado');
                // Grado escolar del nivel: 1, 2 o 3.
                // Un juego tiene niveles distintos para cada grado.

            $table->unsignedTinyInteger('numero_nivel');
                // Número secuencial dentro del juego y grado.
                // Ej: Juego "Suma" → Grado 1 → Niveles 1, 2, 3...

            $table->unsignedTinyInteger('dificultad');
                // Escala: 1=muy fácil, 2=fácil, 3=medio, 4=difícil, 5=muy difícil.
                // Guía al algoritmo adaptativo al elegir ejercicios (RF-04).
                // CHECK: entre 1 y 5 (aplicado en el Model con validación).

            // ── Reglas del nivel ──────────────────────────────────────
            $table->unsignedTinyInteger('min_aciertos_pasar')->default(3);
                // Aciertos mínimos necesarios para superar este nivel.
                // El algoritmo 3/2 usa este umbral (RF-04).

            $table->unsignedTinyInteger('total_ejercicios')->default(5);
                // Cantidad de ejercicios por sesión en este nivel.
                // Límite cognitivo adecuado para niños de 6-9 años.

            $table->boolean('desbloqueado_por_defecto')->default(false);
                // TRUE solo para el primer nivel (numero_nivel=1) de cada grado.
                // Los demás requieren superar el nivel anterior.

            // ── Índice único: no puede haber dos niveles iguales ──────
            $table->unique(['juego_id', 'grado', 'numero_nivel'], 'uq_nivel_juego_grado');
        });
        // Sin timestamps: los niveles son contenido educativo estático
        // que no se modifica frecuentemente.
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles');
    }
};
