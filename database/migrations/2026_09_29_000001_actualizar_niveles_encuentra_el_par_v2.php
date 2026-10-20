<?php

use App\Models\Nivel;
use App\Models\TarjetaNivel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MIGRACIÓN: Reestructuración de niveles de "Encuentra el par" (2026-09-29)
 * =========================================================================
 * Nueva tabla de niveles acordada para que la dificultad suba más gradual
 * (de a pares consecutivos: 1-2, 3-4, 5-6, 7-8, y luego 9-10), y para que
 * los niveles 5 en adelante mezclen pares de objetos y de números al azar
 * en el mismo tablero (antes, cada nivel era de un solo tipo de contenido).
 *
 * ESTA MIGRACIÓN SOLO CAMBIA DATOS, NO LA ESTRUCTURA DE LA BASE DE DATOS:
 * actualiza valores en `niveles` (igual que la migración de pares de la
 * semana pasada) y, para los niveles cuyo TIPO de contenido cambia,
 * reemplaza el pool de `tarjetas_nivel` correspondiente.
 *
 * Los niveles 1, 2 y 3 no necesitan pool nuevo: siguen siendo "objetos"
 * igual que antes, con el mismo pool completo de 10 objetos. Los niveles
 * 7, 8 y 10 ya eran "mixto" y sus pools existentes ya tienen margen de
 * sobra para la nueva cantidad de pares (menor que antes), así que
 * tampoco se tocan. Solo se regenera el pool de los niveles 4, 5, 6 y 9,
 * que sí cambian de tipo de contenido.
 *
 * IMPORTANTE: las tarjetas viejas de esos 4 niveles NO se borran, se
 * marcan como `activo = false`. Borrarlas podría chocar con el historial
 * de partidas ya jugadas (movimientos_memoria) que las referencian.
 * MemoriaService::generarMazo() ya solo toma tarjetas activas, así que las
 * viejas simplemente dejan de usarse.
 * =========================================================================
 */
return new class extends Migration
{
    /**
     * Nueva definición de niveles: numero_nivel => [pares, tiempo_limite_seg, dificultad, contenido]
     */
    private array $nivelesNuevos = [
        1  => [2, 110, 1, 'objeto'],
        2  => [2, 100, 2, 'objeto'],
        3  => [3, 110, 2, 'objeto'],
        4  => [3, 100, 3, 'objeto'],
        5  => [4, 130, 3, 'mixto'],
        6  => [4, 120, 4, 'mixto'],
        7  => [5, 140, 4, 'mixto'],
        8  => [5, 130, 5, 'mixto'],
        9  => [6, 140, 5, 'mixto'],
        10 => [7, 140, 5, 'mixto'],
    ];

    /**
     * Valores anteriores a esta migración (para poder revertir con
     * `php artisan migrate:rollback`).
     */
    private array $nivelesAnteriores = [
        1  => [2, 90,  1],
        2  => [3, 100, 2],
        3  => [4, 110, 2],
        4  => [5, 100, 3],
        5  => [6, 120, 3],
        6  => [7, 110, 4],
        7  => [7, 130, 4],
        8  => [7, 140, 5],
        9  => [7, 130, 5],
        10 => [7, 150, 5],
    ];

    /** Niveles cuyo TIPO de contenido cambia y necesitan un pool de tarjetas nuevo. */
    private array $nivelesConPoolNuevo = [4, 5, 6, 9];

    public function up(): void
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Encuentra el par')->value('id');
        if (!$juegoId) return; // entorno sin este juego todavía — nada que hacer

        foreach ($this->nivelesNuevos as $numeroNivel => [$pares, $tiempoLimite, $dificultad, $contenido]) {
            $nivel = Nivel::where('juego_id', $juegoId)->where('numero_nivel', $numeroNivel)->first();
            if (!$nivel) continue;

            $nivel->update([
                'total_ejercicios'   => $pares,
                'min_aciertos_pasar' => $pares,
                'tiempo_limite_seg'  => $tiempoLimite,
                'dificultad'         => $dificultad,
            ]);

            if (in_array($numeroNivel, $this->nivelesConPoolNuevo, true)) {
                // Desactivar (no borrar) las tarjetas viejas de este nivel.
                TarjetaNivel::where('nivel_id', $nivel->id)->update(['activo' => false]);

                if ($contenido === 'objeto') {
                    $this->insertarObjetos($nivel, $this->objetos());
                } else { // mixto: mismo cálculo de tamaño de pool que usa el sembrador original
                    $mitad = (int) ceil($pares / 2);
                    $this->insertarObjetos($nivel, array_slice($this->objetos(), 0, min(10, $pares + 1)));
                    $this->insertarNumeros($nivel, $this->numeros(), $mitad + 1);
                }
            }
        }
    }

    public function down(): void
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Encuentra el par')->value('id');
        if (!$juegoId) return;

        foreach ($this->nivelesAnteriores as $numeroNivel => [$pares, $tiempoLimite, $dificultad]) {
            DB::table('niveles')
                ->where('juego_id', $juegoId)
                ->where('numero_nivel', $numeroNivel)
                ->update([
                    'total_ejercicios'   => $pares,
                    'min_aciertos_pasar' => $pares,
                    'tiempo_limite_seg'  => $tiempoLimite,
                    'dificultad'         => $dificultad,
                ]);
        }

        // Nota: esto NO reactiva las tarjetas viejas que se desactivaron en
        // up(). Si hace falta deshacer eso también, se resuelve puntualmente.
    }

    private function insertarObjetos(Nivel $nivel, array $objetos): void
    {
        foreach ($objetos as $obj) {
            TarjetaNivel::create([
                'nivel_id'       => $nivel->id,
                'tipo_contenido' => 'objeto',
                'valor'          => $obj['valor'],
                'nombre_es'      => $obj['es'],
                'nombre_gn'      => $obj['gn'],
                'emoji'          => $obj['emoji'],
                'activo'         => true,
            ]);
        }
    }

    private function insertarNumeros(Nivel $nivel, array $numeros, int $cantidad): void
    {
        $seleccion = array_slice($numeros, 0, min(count($numeros), $cantidad + 2));

        foreach ($seleccion as $num) {
            TarjetaNivel::create([
                'nivel_id'       => $nivel->id,
                'tipo_contenido' => 'numero',
                'valor'          => $num['valor'],
                'nombre_es'      => "Número {$num['valor']}",
                'nombre_gn'      => $num['gn'],
                'emoji'          => null,
                'activo'         => true,
            ]);
        }
    }

    /** Mismo pool de objetos que usa MemoriaJuegoSeeder — se repite acá para no depender del sembrador. */
    private function objetos(): array
    {
        return [
            ['valor' => 'mochila',     'es' => 'Mochila',     'gn' => 'Mochila',           'emoji' => '🎒'],
            ['valor' => 'lapiz',       'es' => 'Lápiz',       'gn' => 'Kuatiajokuaaha',     'emoji' => '✏️'],
            ['valor' => 'regla',       'es' => 'Regla',       'gn' => 'Regla',              'emoji' => '📏'],
            ['valor' => 'tijera',      'es' => 'Tijera',      'gn' => 'Tijera',             'emoji' => '✂️'],
            ['valor' => 'crayon',      'es' => 'Crayón',      'gn' => 'Crayón',             'emoji' => '🖍️'],
            ['valor' => 'cuaderno',    'es' => 'Cuaderno',    'gn' => 'Kuatiañe\'ẽ',        'emoji' => '📓'],
            ['valor' => 'libro',       'es' => 'Libro',       'gn' => 'Aranduka',           'emoji' => '📚'],
            ['valor' => 'lapicera',    'es' => 'Lapicera',    'gn' => 'Lapicera',           'emoji' => '🖊️'],
            ['valor' => 'carpeta',     'es' => 'Carpeta',     'gn' => 'Carpeta',            'emoji' => '📁'],
            ['valor' => 'calculadora','es' => 'Calculadora', 'gn' => 'Calculadora',        'emoji' => '🧮'],
        ];
    }

    /** Mismo pool de números (0-9) que usa MemoriaJuegoSeeder. */
    private function numeros(): array
    {
        return [
            ['valor' => '0', 'gn' => 'Zero'],
            ['valor' => '1', 'gn' => 'Peteĩ'],
            ['valor' => '2', 'gn' => 'Mokõi'],
            ['valor' => '3', 'gn' => 'Mbohapy'],
            ['valor' => '4', 'gn' => 'Irundy'],
            ['valor' => '5', 'gn' => 'Po'],
            ['valor' => '6', 'gn' => 'Poteĩ'],
            ['valor' => '7', 'gn' => 'Pokõi'],
            ['valor' => '8', 'gn' => 'Poapy'],
            ['valor' => '9', 'gn' => 'Porundy'],
        ];
    }
};
