<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: agregar 'memoria' al enum tipos_mecanica.tipo
 * -------------------------------------------------------------------------
 * MemoriaJuegoSeeder crea el tipo de mecánica ['tipo' => 'memoria'], pero la
 * migración original de tipos_mecanica solo admitía suma, resta,
 * multiplicacion, division y mixto. Con las migraciones corridas desde cero
 * (por ejemplo en la base de pruebas matiguara_test) el seeder fallaba con
 * "Data truncated for column 'tipo'".
 *
 * Es idempotente: si la columna ya admite 'memoria' (por ejemplo, porque se
 * ajustó a mano en la base de desarrollo), el ALTER deja la definición igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto','memoria') NOT NULL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('tipos_mecanica')) return;
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;

        // Solo se revierte si ninguna fila usa 'memoria' (si no, se perderían datos).
        if (DB::table('tipos_mecanica')->where('tipo', 'memoria')->exists()) return;

        DB::statement(
            "ALTER TABLE tipos_mecanica MODIFY tipo " .
            "ENUM('suma','resta','multiplicacion','division','mixto') NOT NULL"
        );
    }
};
