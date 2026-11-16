<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: agregar 'numeros' al enum tipos_mecanica.tipo
 * -------------------------------------------------------------------------
 * El juego «Mati y los números» (tamizaje) usa la mecánica 'numeros'. Mismo
 * patrón que la de 'laberinto': solo MySQL/MariaDB y es idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto','memoria','laberinto','numeros') NOT NULL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        if (DB::table('tipos_mecanica')->where('tipo', 'numeros')->exists()) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto','memoria','laberinto') NOT NULL"
        );
    }
};
