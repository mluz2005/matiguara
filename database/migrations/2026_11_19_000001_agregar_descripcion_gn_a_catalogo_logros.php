<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: descripción en guaraní de las medallas — 2026-11-19
 * -------------------------------------------------------------------------
 * Hasta ahora las medallas tenían nombre_gn pero la descripción ("Acertá 3
 * ejercicios seguidos.") solo existía en castellano. La persona hablante
 * tradujo también las descripciones (IDs CMP-MED-01d a CMP-MED-10d), así que
 * se agrega la columna. Es opcional: si está vacía, las vistas muestran solo
 * el castellano, como hasta ahora.
 *
 * Los textos los carga la migración 2026_11_19_000002.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        Schema::table('catalogo_logros', function (Blueprint $table) {
            if (!Schema::hasColumn('catalogo_logros', 'descripcion_gn')) {
                $table->string('descripcion_gn', 255)->nullable()->after('descripcion_es');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        Schema::table('catalogo_logros', function (Blueprint $table) {
            if (Schema::hasColumn('catalogo_logros', 'descripcion_gn')) {
                $table->dropColumn('descripcion_gn');
            }
        });
    }
};
