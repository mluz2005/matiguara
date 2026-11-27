<?php

use App\Support\TarjetasGuarani;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): guaraní revisado de «Encuentra el par» — 2026-11-19
 * =========================================================================
 *   1. juegos         → nombre_gn e instrucciones_gn          (EP-JUE-01 / EP-JUE-02)
 *   2. tarjetas_nivel → nombre_gn de los 10 objetos y los números 0 a 9
 *                       (EP-OBJ-01..10 y EP-NUM-0..9), en los 10 niveles de los
 *                       tres grados.
 *
 * Los textos de las tarjetas salen de App\Support\TarjetasGuarani. Cada tarjeta se
 * busca por su «valor» interno (mochila, lapiz, … / 0 a 9), nunca por id.
 * Es seguro repetirla. «down» no restaura el borrador anterior.
 *
 * Los textos que se ven en pantalla durante la partida (globos, carteles, ayuda,
 * nombres de los mundos) viven en el código de las vistas y de memoria-v2.js.
 * =========================================================================
 */
return new class extends Migration
{
    public const NOMBRE_GN        = 'Ejuhu iñirũ ojoguáva';
    public const INSTRUCCIONES_GN = "Embojere mokõi kuatia'atã ha ejuhu umi ojoguáva opa mboyve aravo.";

    public function up(): void
    {
        if (Schema::hasTable('juegos')) {
            DB::table('juegos')->where('nombre_es', 'Encuentra el par')->update([
                'nombre_gn'        => self::NOMBRE_GN,
                'instrucciones_gn' => self::INSTRUCCIONES_GN,
            ]);
        }

        if (!Schema::hasTable('tarjetas_nivel')) return;

        foreach (TarjetasGuarani::OBJETOS as $valor => $nombreGn) {
            DB::table('tarjetas_nivel')
                ->where('tipo_contenido', 'objeto')->where('valor', (string) $valor)
                ->where(fn ($q) => $q->whereNull('nombre_gn')->orWhere('nombre_gn', '!=', $nombreGn))
                ->update(['nombre_gn' => $nombreGn]);
        }

        foreach (TarjetasGuarani::NUMEROS as $valor => $nombreGn) {
            DB::table('tarjetas_nivel')
                ->where('tipo_contenido', 'numero')->where('valor', (string) $valor)
                ->where(fn ($q) => $q->whereNull('nombre_gn')->orWhere('nombre_gn', '!=', $nombreGn))
                ->update(['nombre_gn' => $nombreGn]);
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío (ver nota arriba).
    }
};
