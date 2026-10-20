<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MIGRACIÓN (solo DATOS): más tiempo para memorizar en Flash Matemático — 2026-10-12
 * ============================================================================
 * Motivo: en los niveles con sumas y restas combinadas (6 en adelante, y
 * sobre todo el 6 y el 7) el tiempo era corto para niños de hasta 9 años, y
 * la idea es evitar la frustración.
 *
 * Regla aplicada: desde el nivel 6 se suman 3 segundos, y 4 en el nivel 7
 * (el primero con tres números). Los niveles 1 a 5 no cambian.
 *
 * SEGUNDOS QUE SE VEN LOS NÚMEROS, por nivel 1..10:
 *                       antes                      ahora
 *     Grado 1:   8  7  6  8  7  7  8  6  5  4      8  7  6  8  7 10 12  9  8  7
 *     Grado 2:  10  9  8 10  9  9 10  8  7  6     10  9  8 10  9 12 14 11 10  9
 *     Grado 3:  11 10  9 11 10 10 11  9  8  7     11 10  9 11 10 13 15 12 11 10
 *
 * Si hace falta ajustar de nuevo, se cambian los números de las tablas de abajo
 * y se vuelve a ejecutar (o se edita directamente `tiempo_mostrar_ms`).
 *
 * Solo modifica la columna tiempo_mostrar_ms de los niveles de Flash Matemático.
 * ============================================================================
 */
return new class extends Migration
{
    private const NOMBRE_JUEGO = 'Flash Matemático';

    /** grado => segundos por nivel (posición 0 = nivel 1) */
    private array $nuevos = [
        1 => [8, 7, 6, 8, 7, 10, 12,  9,  8,  7],
        2 => [10, 9, 8, 10, 9, 12, 14, 11, 10,  9],
        3 => [11, 10, 9, 11, 10, 13, 15, 12, 11, 10],
    ];

    /** Valores anteriores (de la migración 000003), para poder deshacer */
    private array $anteriores = [
        1 => [8, 7, 6, 8, 7, 7, 8, 6, 5, 4],
        2 => [10, 9, 8, 10, 9, 9, 10, 8, 7, 6],
        3 => [11, 10, 9, 11, 10, 10, 11, 9, 8, 7],
    ];

    public function up(): void   { $this->aplicar($this->nuevos); }
    public function down(): void { $this->aplicar($this->anteriores); }

    private function aplicar(array $tabla): void
    {
        $juegoId = DB::table('juegos')->where('nombre_es', self::NOMBRE_JUEGO)->value('id');
        if (!$juegoId) return;

        foreach ($tabla as $grado => $segundos) {
            foreach ($segundos as $i => $seg) {
                DB::table('niveles')
                    ->where('juego_id', $juegoId)
                    ->where('grado', $grado)
                    ->where('numero_nivel', $i + 1)
                    ->update(['tiempo_mostrar_ms' => $seg * 1000]);
            }
        }
    }
};
