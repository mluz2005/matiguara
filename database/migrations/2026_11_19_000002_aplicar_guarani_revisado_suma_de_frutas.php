<?php

use App\Support\EnunciadosGuarani;
use App\Support\TextosGuaraniCompartidos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): guaraní revisado de «Suma de Frutas» y de los textos
 * compartidos — 2026-11-19
 * =========================================================================
 * La persona hablante revisó las planillas de textos (Suma de Frutas y la hoja
 * «Compartidos»). Esta migración lleva esas correcciones a una base que ya
 * tiene datos. Una instalación nueva recibe lo mismo desde los seeders.
 *
 *   1. juegos            → nombre_gn e instrucciones_gn de «Suma de Frutas»   (SF-JUE-01/02)
 *   2. contenido_nivel   → enunciado_gn de TODOS los ejercicios del juego     (SF-EJB / SF-EJG)
 *   3. mensajes_retro    → texto_gn de los 13 mensajes de ánimo               (CMP-RET-01..13)
 *   4. catalogo_logros   → nombre_gn y descripcion_gn de las medallas         (CMP-MED-01..10)
 *
 * Los textos salen de App\Support\EnunciadosGuarani y
 * App\Support\TextosGuaraniCompartidos (fuente única).
 *
 * Se busca cada fila por su texto/nombre en castellano, nunca por id. Es
 * segura de repetir: solo escribe lo que cambia. No borra nada. «down» no
 * restaura el guaraní anterior (era un borrador sin revisar); si hiciera falta,
 * se vuelve a cargar desde una copia de la base.
 * =========================================================================
 */
return new class extends Migration
{
    public const NOMBRE_GN       = "Yva Ñemongu'e";
    public const INSTRUCCIONES_GN = 'Embojuaju umi yva rehecháva ha eiporavo mbohovái oĩporãva. ¡Ikatu nde!';

    public function up(): void
    {
        $this->juego();
        $this->enunciados();
        $this->mensajes();
        $this->medallas();
    }

    public function down(): void
    {
        // Intencionalmente vacío (ver nota arriba).
    }

    private function juego(): void
    {
        if (!Schema::hasTable('juegos')) return;

        DB::table('juegos')->where('nombre_es', 'Suma de Frutas')->update([
            'nombre_gn'        => self::NOMBRE_GN,
            'instrucciones_gn' => self::INSTRUCCIONES_GN,
        ]);
    }

    private function enunciados(): void
    {
        if (!Schema::hasTable('juegos') || !Schema::hasTable('contenido_nivel')) return;

        $juegoId = DB::table('juegos')->where('nombre_es', 'Suma de Frutas')->value('id');
        if (!$juegoId) return;

        $filas = DB::table('contenido_nivel as c')
            ->join('niveles as n', 'n.id', '=', 'c.nivel_id')
            ->where('n.juego_id', $juegoId)
            ->whereNotNull('c.valor_a')->whereNotNull('c.valor_b')
            ->select('c.id', 'c.enunciado_gn', 'c.valor_a', 'c.operador', 'c.valor_b', 'n.grado', 'n.numero_nivel')
            ->orderBy('c.id')
            ->get();

        foreach ($filas as $f) {
            $nuevo = EnunciadosGuarani::sumaFrutas(
                (int) $f->grado, (int) $f->numero_nivel,
                (int) $f->valor_a, (string) $f->operador, (int) $f->valor_b
            );
            if ($f->enunciado_gn !== $nuevo) {
                DB::table('contenido_nivel')->where('id', $f->id)->update(['enunciado_gn' => $nuevo]);
            }
        }
    }

    private function mensajes(): void
    {
        if (!Schema::hasTable('mensajes_retro')) return;

        foreach (TextosGuaraniCompartidos::MENSAJES as $textoEs => $textoGn) {
            DB::table('mensajes_retro')->where('texto_es', $textoEs)->update(['texto_gn' => $textoGn]);
        }
    }

    private function medallas(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        $conDescripcion = Schema::hasColumn('catalogo_logros', 'descripcion_gn');

        foreach (TextosGuaraniCompartidos::MEDALLAS as $nombreEs => [$nombreGn, $descripcionGn]) {
            $cambios = ['nombre_gn' => $nombreGn];
            if ($conDescripcion) {
                $cambios['descripcion_gn'] = $descripcionGn;
            }
            DB::table('catalogo_logros')->where('nombre_es', $nombreEs)->update($cambios);
        }
    }
};
