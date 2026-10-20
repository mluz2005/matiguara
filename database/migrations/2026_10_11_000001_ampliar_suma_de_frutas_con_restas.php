<?php

use App\Models\ContenidoNivel;
use App\Models\Nivel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MIGRACIÓN (solo DATOS, no cambia la estructura): Suma de Frutas con restas
 * y más variedad — 2026-10-11
 * =========================================================================
 * Qué hace:
 *   1. Agrega ejercicios de SUMA a los niveles 1, 2 y 3 (que tenían solo 5
 *      cada uno), para que cada partida pueda tomar una selección distinta.
 *      Los 15 ejercicios que ya existían NO se tocan.
 *   2. Crea 3 niveles nuevos con RESTAS (siempre minuendo >= sustraendo,
 *      así nunca hay resultados negativos):
 *        Nivel 4 — restas hasta 10        (se dibujan las frutas)
 *        Nivel 5 — restas hasta 15        (de 11 en adelante, solo números)
 *        Nivel 6 — sumas y restas mezcladas, hasta 15
 *
 * Cada partida sigue siendo de 5 ejercicios; lo que cambia es que se eligen
 * entre varios. Las frutas NO se guardan acá: las elige la aplicación al
 * mostrar el ejercicio (ver App\Support\FrutasJuego).
 *
 * Es seguro ejecutarla más de una vez: no duplica ejercicios ni niveles.
 *
 * GUARANÍ: los enunciados en guaraní guardados acá son el texto "de base"
 * (con números). El texto que ve el niño con las frutas sale de
 * App\Support\FrutasJuego y debe ser validado por una persona hablante.
 * =========================================================================
 */
return new class extends Migration
{
    /** Ejercicios nuevos de suma para los niveles existentes: numero_nivel => [[a, b], ...] */
    private array $sumasNuevas = [
        1 => [[1,1],[1,2],[2,1],[3,1],[1,3]],
        2 => [[6,4],[8,2],[5,5],[4,4],[2,7],[9,1],[3,6],[5,3]],
        3 => [[7,7],[8,6],[9,5],[6,6],[8,7],[9,3],[7,5],[5,8]],
    ];

    /** Niveles nuevos: numero_nivel => [dificultad, [[a, operador, b], ...]] */
    private array $nivelesNuevos = [
        4 => [3, [
            [5,'-',2],[6,'-',1],[7,'-',3],[8,'-',2],[9,'-',4],[10,'-',5],
            [6,'-',4],[8,'-',5],[7,'-',2],[9,'-',3],[10,'-',3],[8,'-',6],
        ]],
        5 => [4, [
            [12,'-',5],[13,'-',4],[11,'-',6],[14,'-',7],[15,'-',6],[12,'-',8],
            [13,'-',9],[15,'-',9],[14,'-',5],[11,'-',3],[10,'-',4],[10,'-',10],
        ]],
        6 => [5, [
            [9,'+',5],[12,'-',7],[8,'+',7],[15,'-',8],[6,'+',9],[14,'-',6],
            [7,'+',8],[13,'-',5],[9,'+',6],[11,'-',4],[10,'-',7],[8,'+',6],
        ]],
    ];

    public function up(): void
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Suma de Frutas')->value('id');
        if (!$juegoId) return; // entorno sin este juego — nada que hacer

        // 1) Más sumas en los niveles existentes
        foreach ($this->sumasNuevas as $numeroNivel => $pares) {
            $nivel = Nivel::where('juego_id', $juegoId)->where('numero_nivel', $numeroNivel)->first();
            if (!$nivel) continue;
            foreach ($pares as [$a, $b]) {
                $this->crearEjercicio($nivel->id, $a, '+', $b);
            }
        }

        // 2) Niveles nuevos con restas
        $grado = Nivel::where('juego_id', $juegoId)->where('numero_nivel', 1)->value('grado') ?? 1;

        foreach ($this->nivelesNuevos as $numeroNivel => [$dificultad, $ejercicios]) {
            $nivel = Nivel::where('juego_id', $juegoId)->where('numero_nivel', $numeroNivel)->first();
            if (!$nivel) {
                $nivel = Nivel::create([
                    'juego_id'                 => $juegoId,
                    'grado'                    => $grado,
                    'numero_nivel'             => $numeroNivel,
                    'dificultad'               => $dificultad,
                    'min_aciertos_pasar'       => 3,
                    'total_ejercicios'         => 5,
                    'desbloqueado_por_defecto' => false,
                    'tiempo_mostrar_ms'        => null,
                ]);
            }
            foreach ($ejercicios as [$a, $op, $b]) {
                $this->crearEjercicio($nivel->id, $a, $op, $b);
            }
        }
    }

    public function down(): void
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Suma de Frutas')->value('id');
        if (!$juegoId) return;

        // Quita los ejercicios agregados a los niveles 1-3
        foreach ($this->sumasNuevas as $numeroNivel => $pares) {
            $nivelId = DB::table('niveles')->where('juego_id', $juegoId)->where('numero_nivel', $numeroNivel)->value('id');
            if (!$nivelId) continue;
            foreach ($pares as [$a, $b]) {
                $this->borrarSiSePuede($nivelId, $a, '+', $b);
            }
        }

        // Quita los niveles 4-6 (sus ejercicios se eliminan en cascada) si nadie los jugó
        foreach (array_keys($this->nivelesNuevos) as $numeroNivel) {
            $nivelId = DB::table('niveles')->where('juego_id', $juegoId)->where('numero_nivel', $numeroNivel)->value('id');
            if (!$nivelId) continue;
            try {
                DB::table('niveles')->where('id', $nivelId)->delete();
            } catch (\Throwable $e) {
                // Ya tiene partidas jugadas: se conserva para no perder historial.
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────

    private function crearEjercicio(int $nivelId, int $a, string $op, int $b): void
    {
        $existe = ContenidoNivel::where('nivel_id', $nivelId)
            ->where('valor_a', $a)->where('operador', $op)->where('valor_b', $b)
            ->exists();
        if ($existe) return;

        $resultado = $op === '+' ? $a + $b : $a - $b;
        $orden     = (int) ContenidoNivel::where('nivel_id', $nivelId)->max('orden') + 1;

        $palabra   = $op === '+' ? 'más' : 'menos';
        $tipoOper  = $op === '+' ? 'suma_simple' : 'resta_simple';

        ContenidoNivel::create([
            'nivel_id'           => $nivelId,
            'tipo_operacion'     => $tipoOper,
            'enunciado_es'       => "¿Cuánto es {$a} {$palabra} {$b}?",
            'enunciado_gn'       => "¿Mboy'e pa {$a} {$palabra} {$b}?",
            'valor_a'            => $a,
            'operador'           => $op,
            'valor_b'            => $b,
            'respuesta_correcta' => (string) $resultado,
            'opciones_json'      => $this->opciones($resultado),
            'orden'              => min($orden, 255),
            'audio_es'           => null,
            'audio_gn'           => null,
        ]);
    }

    /** Respuesta correcta + 3 distractores cercanos, nunca negativos ni repetidos. */
    private function opciones(int $resultado): array
    {
        $candidatos = [$resultado + 1, $resultado - 1, $resultado + 2, $resultado - 2, $resultado + 3, $resultado + 4];
        $opciones = [$resultado];
        foreach ($candidatos as $c) {
            if ($c >= 0 && !in_array($c, $opciones, true)) $opciones[] = $c;
            if (count($opciones) === 4) break;
        }
        return $opciones;
    }

    private function borrarSiSePuede(int $nivelId, int $a, string $op, int $b): void
    {
        try {
            ContenidoNivel::where('nivel_id', $nivelId)
                ->where('valor_a', $a)->where('operador', $op)->where('valor_b', $b)
                ->delete();
        } catch (\Throwable $e) {
            // Ya fue usado en partidas: se conserva.
        }
    }
};
