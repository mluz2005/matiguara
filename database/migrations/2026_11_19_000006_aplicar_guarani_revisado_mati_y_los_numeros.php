<?php

use App\Support\Numeros\TextosNumeros;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): guaraní revisado de «Mati y los números» — 2026-11-19
 * =========================================================================
 *   juegos → nombre_gn (MN-JUE-01) e instrucciones_gn (MN-JUE-02).
 *
 * El resto del juego (viajes, partes, preguntas, botones, figuritas) no está en la base:
 * vive en App\Support\Numeros\TextosNumeros y llega con los archivos nuevos.
 *
 * El juego se busca por su nombre en castellano, nunca por id. Es seguro repetirla.
 * «down» no restaura el borrador anterior.
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('juegos')) return;

        DB::table('juegos')->where('nombre_es', TextosNumeros::NOMBRE_ES)->update([
            'nombre_gn'        => TextosNumeros::NOMBRE_GN,
            'instrucciones_gn' => TextosNumeros::INSTRUCCIONES_GN,
        ]);
    }

    public function down(): void
    {
        // Los datos revisados no se deshacen.
    }
};
