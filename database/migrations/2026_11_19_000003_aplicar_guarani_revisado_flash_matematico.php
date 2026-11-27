<?php

use App\Support\EnunciadosGuarani;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): guaraní revisado de «Flash Matemático» — 2026-11-19
 * =========================================================================
 *   1. juegos          → instrucciones_gn (FM-JUE-02). El nombre «Papapy Pya'e»
 *                        (FM-JUE-01) quedó igual, no se toca.
 *   2. contenido_nivel → enunciado_gn de los 15 ejercicios originales de 1.° grado
 *                        (FM-EJB-01..15). Los ejercicios agregados después llevan
 *                        «¡Flash! {cuenta}.» (FM-EJG-01), que no cambia.
 *
 * Los textos salen de App\Support\EnunciadosGuarani::FLASH_BASE.
 * Se busca por nombre del juego, nivel y números (nunca por id). Se puede repetir
 * sin efectos. «down» no restaura el borrador anterior.
 * =========================================================================
 */
return new class extends Migration
{
    public const INSTRUCCIONES_GN = "¡Emaña porã umi papapýre, okañýta hína! Eñemandu'a hesekuéra ha eiporavo osẽva.";

    public function up(): void
    {
        if (!Schema::hasTable('juegos')) return;

        $juegoId = DB::table('juegos')->where('nombre_es', 'Flash Matemático')->value('id');
        if (!$juegoId) return;

        DB::table('juegos')->where('id', $juegoId)->update(['instrucciones_gn' => self::INSTRUCCIONES_GN]);

        if (!Schema::hasTable('contenido_nivel')) return;

        $filas = DB::table('contenido_nivel as c')
            ->join('niveles as n', 'n.id', '=', 'c.nivel_id')
            ->where('n.juego_id', $juegoId)
            ->where('n.grado', 1)
            ->where('c.operador', '+')
            ->whereNull('c.operador_2')
            ->select('c.id', 'c.enunciado_gn', 'c.valor_a', 'c.valor_b', 'n.numero_nivel')
            ->get();

        foreach ($filas as $f) {
            $nuevo = EnunciadosGuarani::flashBase((int) $f->numero_nivel, (int) $f->valor_a, (int) $f->valor_b);
            if ($nuevo !== null && $f->enunciado_gn !== $nuevo) {
                DB::table('contenido_nivel')->where('id', $f->id)->update(['enunciado_gn' => $nuevo]);
            }
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío (ver nota arriba).
    }
};
