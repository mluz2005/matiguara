<?php

use App\Support\TextosCamino;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): guaraní revisado de «El camino a la escuela» — 2026-11-19
 * =========================================================================
 *   1. juegos          → instrucciones_gn (CE-JUE-02). El nombre «Tape mbo'ehaópe»
 *                        (CE-JUE-01) quedó igual.
 *   2. contenido_nivel → enunciado_gn de los problemas (CE-PRO-01..08). Cada problema
 *                        se rearma desde su enunciado en castellano (App\Support\TextosCamino).
 *                        Un enunciado que no sea de ninguna de las 8 historias no se toca.
 *   3. catalogo_logros → nombre_gn y descripcion_gn de las 4 medallas (CE-MED-1..4).
 *                        (Necesita la columna de la migración 2026_11_19_000001.)
 *
 * Todo se busca por nombre en castellano, nunca por id. Es seguro repetirla.
 * «down» no restaura el borrador anterior.
 * =========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('juegos')) return;

        $juegoId = DB::table('juegos')->where('nombre_es', 'El camino a la escuela')->value('id');

        if ($juegoId) {
            DB::table('juegos')->where('id', $juegoId)->update([
                'nombre_gn'        => TextosCamino::NOMBRE_GN,
                'instrucciones_gn' => TextosCamino::INSTRUCCIONES_GN,
            ]);

            if (Schema::hasTable('contenido_nivel')) {
                $filas = DB::table('contenido_nivel as c')
                    ->join('niveles as n', 'n.id', '=', 'c.nivel_id')
                    ->where('n.juego_id', $juegoId)
                    ->select('c.id', 'c.enunciado_es', 'c.enunciado_gn')
                    ->orderBy('c.id')
                    ->get();

                foreach ($filas as $f) {
                    $nuevo = TextosCamino::enunciado((string) $f->enunciado_es);
                    if ($nuevo !== null && $f->enunciado_gn !== $nuevo) {
                        DB::table('contenido_nivel')->where('id', $f->id)->update(['enunciado_gn' => $nuevo]);
                    }
                }
            }
        }

        if (!Schema::hasTable('catalogo_logros')) return;

        $conDescripcion = Schema::hasColumn('catalogo_logros', 'descripcion_gn');
        foreach (TextosCamino::MEDALLAS as $nombreEs => [$nombreGn, $descripcionGn]) {
            $cambios = ['nombre_gn' => $nombreGn];
            if ($conDescripcion) {
                $cambios['descripcion_gn'] = $descripcionGn;
            }
            DB::table('catalogo_logros')->where('nombre_es', $nombreEs)->update($cambios);
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío (ver nota arriba).
    }
};
