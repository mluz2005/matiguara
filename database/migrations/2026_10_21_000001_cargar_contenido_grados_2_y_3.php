<?php

use Database\Seeders\ContenidoGrados23Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS, no cambia la estructura): contenido de 2.° y 3.er
 * grado para Suma de Frutas y Encuentra el par — 2026-10-21
 * =========================================================================
 * Qué hace: ejecuta el sembrador idempotente ContenidoGrados23Seeder, que
 *   - agrega 8 niveles por grado (hasta 100 en 2.°, hasta 1000 en 3.°) a
 *     Suma de Frutas, con 12 ejercicios por nivel; y
 *   - copia los 10 niveles de Encuentra el par (y su pool de tarjetas) a
 *     2.° y 3.° grado, con menos tiempo límite.
 *
 * En una instalación nueva esta migración no encuentra todavía los juegos
 * (los crean los sembradores, que corren después): no hace nada. En ese caso
 * se ejecuta el sembrador a mano al final de la carga de datos:
 *   php artisan db:seed --class=ContenidoGrados23Seeder
 *
 * Es seguro ejecutarla más de una vez: no duplica nada.
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['juegos', 'niveles', 'contenido_nivel', 'tarjetas_nivel'] as $tabla) {
            if (!Schema::hasTable($tabla)) {
                return;
            }
        }

        (new ContenidoGrados23Seeder())->run();
    }

    public function down(): void
    {
        // Solo se quitan niveles de 2.° y 3.° de estos dos juegos (Flash Matemático no se toca).
        $ids = DB::table('juegos')->whereIn('nombre_es', ['Suma de Frutas', 'Encuentra el par'])->pluck('id');

        foreach (DB::table('niveles')->whereIn('juego_id', $ids)->whereIn('grado', ContenidoGrados23Seeder::GRADOS)->pluck('id') as $nivelId) {
            try {
                DB::table('niveles')->where('id', $nivelId)->delete(); // ejercicios y tarjetas se borran en cascada
            } catch (\Throwable $e) {
                // Ya tiene partidas o progreso: se conserva para no perder historial.
            }
        }
    }
};
