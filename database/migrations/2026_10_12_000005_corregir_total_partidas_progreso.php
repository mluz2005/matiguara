<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): corrige el contador de partidas — 2026-10-12
 * ============================================================================
 * Problema: en Suma de Frutas y Flash Matemático, `total_partidas` de la tabla
 * `progreso_estudiante` se sumaba DOS veces por cada partida terminada (una al
 * empezarla y otra al cerrarla). Por eso el panel del docente y el progreso del
 * niño mostraban el doble de partidas, y las barras de progreso mostraban
 * aproximadamente la mitad del porcentaje real.
 *
 * El código ya se corrigió (ahora cuenta una sola vez, al terminar la partida).
 * Esta migración repara los números que ya estaban guardados: vuelve a calcular
 * `total_partidas` contando las partidas COMPLETADAS de cada estudiante en cada
 * nivel (tabla `sesiones_juego`), que es la fuente real.
 *
 * Es segura de ejecutar más de una vez (siempre da el mismo resultado).
 * down() no hace nada: los valores anteriores eran incorrectos.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('progreso_estudiante') || !Schema::hasTable('sesiones_juego')) {
            return; // nombres de tabla distintos en este entorno: no se toca nada
        }

        DB::statement(
            "UPDATE progreso_estudiante
                SET total_partidas = (
                    SELECT COUNT(*) FROM sesiones_juego
                     WHERE sesiones_juego.estudiante_id = progreso_estudiante.estudiante_id
                       AND sesiones_juego.nivel_id      = progreso_estudiante.nivel_id
                       AND sesiones_juego.estado        = 'completada'
                )"
        );
    }

    public function down(): void
    {
        // Sin acción: no se restauran valores que estaban duplicados.
    }
};
