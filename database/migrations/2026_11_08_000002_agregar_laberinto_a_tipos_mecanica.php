<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: agregar 'laberinto' al enum tipos_mecanica.tipo
 * -------------------------------------------------------------------------
 * El juego «El camino a la escuela» usa la mecánica 'laberinto'. Mismo patrón
 * que 2026_10_20_000001_agregar_memoria_a_tipos_mecanica: solo MySQL/MariaDB
 * (en otros motores no hace nada) y es idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto','memoria','laberinto') NOT NULL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        // Solo se revierte si ninguna fila usa 'laberinto' (si no, se perderían datos).
        if (DB::table('tipos_mecanica')->where('tipo', 'laberinto')->exists()) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto','memoria') NOT NULL"
        );
    }
};
