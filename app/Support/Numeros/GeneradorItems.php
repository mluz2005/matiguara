<?php

namespace App\Support\Numeros;

/**
 * GENERADOR DE PREGUNTAS — «Mati y los números»
 * =========================================================================
 * Función pura (no toca la base): dados el grado, la parte, el ciclo, el nivel
 * adaptativo y una semilla, devuelve las preguntas de una parte. Cada pregunta
 * es un arreglo con todo lo que el servidor necesita para corregirla; al
 * navegador se le envía SIN la respuesta correcta (ver NumerosService).
 *
 * PREGUNTAS FIJAS Y ADAPTATIVAS (documento de requerimientos, «Estructura»)
 *   · FIJAS: siempre del nivel «esperado» y iguales para todos los niños del
 *     grado en un mismo ciclo (semilla = grado + parte + ciclo). Sostienen las
 *     señales de riesgo porque son comparables. Cambian entre ciclos y no
 *     repiten una pregunta del ciclo anterior (RF-11).
 *   · ADAPTATIVAS: salen del nivel actual del niño (1 inicial, 2 esperado,
 *     3 ampliado) y se arman con la semilla de la partida.
 *   · El orden dentro de la parte y el lado de la respuesta correcta se
 *     sortean con la semilla de la partida (RF-10).
 *
 * EL CERO se evalúa dentro de las partes existentes: cada parte con contenido
 * tiene al menos una pregunta FIJA con cero (`cero => true`).
 *
 * ⚠️ Rangos, proporciones y distractores de 2.º y 3.º son una propuesta del
 * proyecto para calibrar en el piloto, no valores validados.
 * =========================================================================
 */
final class GeneradorItems
{
    public const INICIAL  = 1;
    public const ESPERADO = 2;
    public const AMPLIADO = 3;

    /** Partes de cada viaje (la 1 es el calentamiento, que va en todos). */
    public const VIAJES = [
        1 => [1, 2, 3, 5],    // los símbolos
        2 => [1, 4, 6, 7],    // las cantidades
        3 => [1, 8, 9, 10],   // los signos
    ];

    /** parte => [área, preguntas fijas, preguntas adaptativas] */
    public const PARTES = [
        1  => ['area' => 'velocidad',          'fijas' => 4, 'adaptativas' => 0],
        2  => ['area' => 'numeros_cantidades', 'fijas' => 3, 'adaptativas' => 3],
        3  => ['area' => 'numeros_cantidades', 'fijas' => 3, 'adaptativas' => 3],
        4  => ['area' => 'sentido_cantidad',   'fijas' => 3, 'adaptativas' => 3],
        5  => ['area' => 'numeros_cantidades', 'fijas' => 6, 'adaptativas' => 4],
        6  => ['area' => 'sentido_cantidad',   'fijas' => 4, 'adaptativas' => 4],
        7  => ['area' => 'sentido_cantidad',   'fijas' => 3, 'adaptativas' => 3],
        8  => ['area' => 'signos_calculo',     'fijas' => 3, 'adaptativas' => 3],
        9  => ['area' => 'signos_calculo',     'fijas' => 4, 'adaptativas' => 4],
        10 => ['area' => 'numeros_cantidades', 'fijas' => 3, 'adaptativas' => 3],
    ];

    /** Tipo de pregunta (lo que dibuja el navegador) de cada parte. */
    public const TIPOS = [
        1 => 'toque', 2 => 'num_a_cantidad', 3 => 'cantidad_a_num', 4 => 'mas_puntos', 5 => 'mayor',
        6 => 'puntos_rapidos', 7 => 'linea', 8 => 'signo', 9 => 'cuenta_palillos', 10 => 'secuencia',
    ];

    /** Tope de los números según [grado][nivel-1] (partes 2, 3, 5 y 10). */
    private const RANGO = [
        1 => [5, 10, 15],
        2 => [20, 50, 100],
        3 => [100, 500, 1000],
    ];

    /** Largo de la línea de números según [grado][nivel-1] (parte 7). */
    private const LINEA = [
        1 => [5, 10, 15],
        2 => [20, 100, 100],
        3 => [100, 1000, 1000],
    ];

    /** Tope de las cuentas de las partes 8 y 9 según [grado][nivel-1]. */
    private const CUENTAS = [
        1 => [5, 10, 15],
        2 => [10, 20, 50],     // parte 8
        3 => [50, 100, 200],
    ];
    private const CUENTAS_PALILLOS = [
        1 => [5, 10, 15],
        2 => [20, 50, 100],    // parte 9
        3 => [50, 100, 200],
    ];

    /** Parte 4: [mínimo de puntos, máximo, proporción mínima] según [grado][nivel-1]. */
    private const PUNTOS = [
        1 => [[2, 8, 1.9],   [4, 14, 1.4],  [6, 18, 1.3]],
        2 => [[4, 14, 1.8],  [8, 24, 1.3],  [10, 30, 1.2]],
        3 => [[8, 24, 1.7],  [12, 36, 1.2], [16, 48, 1.1]],
    ];

    /** Parte 10: saltos posibles según [grado][nivel-1]. */
    private const SALTOS = [
        1 => [[1], [1], [1, 2]],
        2 => [[1], [1, 10], [2, 5, 10]],
        3 => [[1, 10], [10, 100], [5, 10, 100]],
    ];

    /** Parte 6: cantidad máxima de puntos según nivel-1. */
    private const PUNTOS_RAPIDOS_MAX = [4, 6, 8];

    /**
     * Parte 6: milisegundos que se ven los puntos, según el grado. Los más chicos necesitan más tiempo
     * para mirar y retener la cantidad (decisión de diseño, antes del piloto: 1.° 2 s, 2.° 1,5 s, 3.° 1,2 s).
     * Cada viaje guarda este valor dentro de sus preguntas, así que siempre se sabe con qué tiempo se hizo.
     */
    public const PUNTOS_RAPIDOS_MS = [1 => 2000, 2 => 1500, 3 => 1200];

    /** Pares de números que se confunden por su forma (1.er grado). */
    private const PARECIDOS = [6 => 9, 9 => 6, 2 => 5, 5 => 2, 3 => 8, 8 => 3, 1 => 7, 7 => 1];

    // =========================================================================
    // API
    // =========================================================================

    /** Partes que se juegan en el viaje (1 a 3). */
    public static function partesDelViaje(int $viaje): array
    {
        return self::VIAJES[max(1, min(3, $viaje))];
    }

    /** Cantidad de preguntas de un viaje (fijas + adaptativas de todas sus partes). */
    public static function totalDelViaje(int $viaje): int
    {
        $t = 0;
        foreach (self::partesDelViaje($viaje) as $p) {
            $t += self::PARTES[$p]['fijas'] + self::PARTES[$p]['adaptativas'];
        }
        return $t;
    }

    public static function tipoDe(int $parte): string
    {
        return self::TIPOS[$parte];
    }

    public static function areaDe(int $parte): string
    {
        return self::PARTES[$parte]['area'];
    }

    /**
     * Las preguntas de UNA parte, ya mezcladas (fijas + adaptativas).
     *
     * @param  int $nivel    nivel adaptativo actual (1, 2 o 3)
     * @param  int $semilla  semilla de la partida (orden, lado y preguntas adaptativas)
     * @return array<int,array>  se numeran de 0 en adelante en el orden en que se muestran
     */
    public static function parte(int $grado, int $parte, int $ciclo, int $nivel, int $semilla): array
    {
        $nivel = max(1, min(3, $nivel));
        $cfg   = self::PARTES[$parte];

        $items = self::fijas($grado, $parte, $ciclo);

        $rAdapt = new Aleatorio($semilla + $parte * 7919 + $nivel * 104729);
        $usados = array_column($items, 'clave');
        for ($k = 0; $k < $cfg['adaptativas']; $k++) {
            $item = null;
            for ($intento = 0; $intento < 40; $intento++) {
                $item = self::crear($grado, $parte, $nivel, $rAdapt, false, $k);
                if (!in_array($item['clave'], $usados, true)) {
                    break;
                }
            }
            $usados[] = $item['clave'];
            $items[]  = $item;
        }

        // Orden y lado al azar con la semilla de la partida (los números no cambian).
        $r = new Aleatorio($semilla + $parte * 131071);
        $items = $r->barajar($items);
        foreach ($items as $i => $it) {
            $items[$i] = self::mezclarLado($it, $r);
        }
        return array_values($items);
    }

    /** Preguntas fijas (nivel esperado) de una parte: iguales para todo el grado en ese ciclo. */
    public static function fijas(int $grado, int $parte, int $ciclo): array
    {
        $cfg = self::PARTES[$parte];
        $prev = $ciclo > 1 ? self::fijasSinFiltro($grado, $parte, $ciclo - 1) : [];
        $evitar = [];
        foreach ($prev as $p) {
            if (!$p['cero']) {
                $evitar[] = $p['clave'];
            }
        }

        $r = new Aleatorio(Aleatorio::deTexto("g{$grado}-p{$parte}-c{$ciclo}-fija"));
        $items = [];
        $usados = [];
        for ($slot = 0; $slot < $cfg['fijas']; $slot++) {
            $item = null;
            for ($intento = 0; $intento < 60; $intento++) {
                $item = self::crear($grado, $parte, self::ESPERADO, $r, true, $slot);
                $repetida = in_array($item['clave'], $usados, true)
                    || (!$item['cero'] && in_array($item['clave'], $evitar, true));
                if (!$repetida) {
                    break;
                }
            }
            $usados[] = $item['clave'];
            $items[]  = $item;
        }
        return $items;
    }

    /** Una pregunta de demostración (fácil) para explicar la parte; incluye la respuesta correcta. */
    public static function demostracion(int $grado, int $parte): array
    {
        $r = new Aleatorio(Aleatorio::deTexto("g{$grado}-p{$parte}-demo"));
        return self::crear($grado, $parte, self::INICIAL, $r, false, 1);
    }

    // =========================================================================
    // Internos
    // =========================================================================

    private static function fijasSinFiltro(int $grado, int $parte, int $ciclo): array
    {
        $r = new Aleatorio(Aleatorio::deTexto("g{$grado}-p{$parte}-c{$ciclo}-fija"));
        $items = [];
        $usados = [];
        for ($slot = 0; $slot < self::PARTES[$parte]['fijas']; $slot++) {
            for ($intento = 0; $intento < 60; $intento++) {
                $item = self::crear($grado, $parte, self::ESPERADO, $r, true, $slot);
                if (!in_array($item['clave'], $usados, true)) {
                    break;
                }
            }
            $usados[] = $item['clave'];
            $items[]  = $item;
        }
        return $items;
    }

    private static function crear(int $g, int $parte, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $datos = match ($parte) {
            1  => self::p1($r),
            2  => self::p2($g, $nivel, $r, $fija, $slot),
            3  => self::p3($g, $nivel, $r, $fija, $slot),
            4  => self::p4($g, $nivel, $r, $slot),
            5  => self::p5($g, $nivel, $r, $fija, $slot),
            6  => self::p6($g, $nivel, $r, $fija, $slot),
            7  => self::p7($g, $nivel, $r, $fija, $slot),
            8  => self::p8($g, $nivel, $r, $fija, $slot),
            9  => self::p9($g, $nivel, $r, $fija, $slot),
            10 => self::p10($g, $nivel, $r, $fija, $slot),
        };

        return [
            'parte'    => $parte,
            'tipo'     => self::TIPOS[$parte],
            'area'     => self::PARTES[$parte]['area'],
            'fija'     => $fija,
            'nivel'    => $nivel,
            'clave'    => $datos['clave'],
            'cero'     => (bool) ($datos['cero'] ?? false),
            'datos'    => $datos['datos'],
            'opciones' => $datos['opciones'] ?? null,
            'correcta' => (string) $datos['correcta'],
            'tipos'    => $datos['tipos'] ?? null,
        ];
    }

    /** Intercambia izquierda/derecha (partes 4 y 5) o baraja las opciones, según el sorteo. */
    private static function mezclarLado(array $it, Aleatorio $r): array
    {
        if (in_array($it['tipo'], ['mas_puntos', 'mayor'], true)) {
            if ($r->moneda()) {
                [$it['datos']['izq'], $it['datos']['der']] = [$it['datos']['der'], $it['datos']['izq']];
                $it['correcta'] = $it['correcta'] === 'izq' ? 'der' : 'izq';
            }
        } elseif (is_array($it['opciones']) && in_array($it['tipo'], ['num_a_cantidad', 'cantidad_a_num', 'cuenta_palillos', 'secuencia'], true)) {
            $it['opciones'] = $r->barajar($it['opciones']);
        }
        return $it;
    }

    // ── Parte 1: calentamiento ───────────────────────────────────────────

    private static function p1(Aleatorio $r): array
    {
        $x = $r->entre(15, 85);
        $y = $r->entre(20, 80);
        return ['clave' => "p1:$x:$y", 'datos' => ['x' => $x, 'y' => $y], 'correcta' => 'toque'];
    }

    // ── Parte 2: número → palillos ───────────────────────────────────────

    private static function p2(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cap  = self::RANGO[$g][$nivel - 1];
        $cero = $fija && $slot === 0;
        $v    = $cero ? 0 : $r->entre(1, $cap);

        [$opts, $tipos] = self::opcionesNumericas($g, $v, $cap, $r);

        return [
            'clave'    => "p2:$v",
            'cero'     => $cero,
            'datos'    => ['numero' => $v],
            'opciones' => array_map(fn ($n) => ['valor' => $n] + self::representar($g, $n), $opts),
            'correcta' => $v,
            'tipos'    => $tipos,
        ];
    }

    // ── Parte 3: palillos → número ───────────────────────────────────────

    private static function p3(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cap  = self::RANGO[$g][$nivel - 1];
        $cero = $fija && $slot === 0;
        $v    = $cero ? 0 : $r->entre(1, $cap);

        [$opts, $tipos] = self::opcionesNumericas($g, $v, $cap, $r);

        return [
            'clave'    => "p3:$v",
            'cero'     => $cero,
            'datos'    => self::representar($g, $v) + ['numero' => $v],
            'opciones' => $opts,
            'correcta' => $v,
            'tipos'    => $tipos,
        ];
    }

    // ── Parte 4: ¿dónde hay más puntos? ──────────────────────────────────

    private static function p4(int $g, int $nivel, Aleatorio $r, int $slot): array
    {
        [$min, $max, $prop] = self::PUNTOS[$g][$nivel - 1];

        $a = $b = 0;
        for ($i = 0; $i < 200; $i++) {
            $grande  = $r->entre(max($min, 2), $max);
            $objetivo = $prop + ($r->entre(0, 6) / 10);
            $chico   = (int) round($grande / $objetivo);
            if ($chico >= $min - 1 && $chico >= 1 && $grande / max(1, $chico) >= $prop - 1e-9 && $grande > $chico) {
                $a = $grande;
                $b = $chico;
                break;
            }
        }
        if ($b === 0) {            // respaldo: siempre válido
            $a = $max;
            $b = (int) floor($max / $prop);
        }

        // «tamano»: todos los puntos iguales (más puntos = más superficie).
        // «area»:  la superficie total es igual (más puntos = puntos más chicos).
        $control = ($slot % 2 === 0) ? 'tamano' : 'area';

        return [
            'clave'    => "p4:$a:$b:$control",
            'datos'    => ['izq' => $a, 'der' => $b, 'control' => $control, 'semilla' => $r->entre(1, 1000000)],
            'opciones' => ['izq', 'der'],
            'correcta' => 'izq',
        ];
    }

    // ── Parte 5: ¿cuál es más grande? ────────────────────────────────────

    private static function p5(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cero = false;

        if ($g === 1) {
            [$lo, $hi, $dmin, $dmax] = [[0, 5, 2, 4], [1, 10, 1, 4], [1, 15, 1, 2]][$nivel - 1];
            if ($fija && $slot === 0)      { $x = 0; $y = 1; $cero = true; }
            elseif ($fija && $slot === 1)  { $x = 0; $y = $r->entre(4, 9); $cero = true; }
            else {
                do {
                    $x = $r->entre($lo, $hi);
                    $y = $r->entre($lo, $hi);
                } while (abs($x - $y) < $dmin || abs($x - $y) > $dmax);
            }
        } elseif ($g === 2) {
            // Misma decena: hay que mirar las unidades.
            $decMax = [1, 4, 9][$nivel - 1];
            $dec = $r->entre(1, $decMax);
            if ($fija && $slot === 0)      { $x = $dec * 10; $y = $dec * 10 + $r->entre(1, 9); $cero = true; }   // 30 y 37
            else {
                do {
                    $ux = $r->entre(0, 9);
                    $uy = $r->entre(0, 9);
                    $d  = abs($ux - $uy);
                } while ($d < [3, 1, 1][$nivel - 1] || $d > [9, 9, 2][$nivel - 1]);
                $x = $dec * 10 + $ux;
                $y = $dec * 10 + $uy;
            }
        } else {
            // Misma centena: se mira la decena y la unidad; el cero dentro del número cuenta.
            $cenMax = [1, 4, 9][$nivel - 1];
            $cen = $r->entre(1, $cenMax);
            if ($fija && $slot === 0) {                       // 305 y 350
                $d = $r->entre(1, 9);
                $x = $cen * 100 + $d;
                $y = $cen * 100 + $d * 10;
                $cero = true;
            } else {
                do {
                    $x = $cen * 100 + $r->entre(0, 99);
                    $y = $cen * 100 + $r->entre(0, 99);
                    $d = abs($x - $y);
                } while ($d < [30, 5, 3][$nivel - 1] || $d > [99, 99, 20][$nivel - 1]);
            }
        }

        $correcta = $x > $y ? 'izq' : 'der';
        return [
            'clave'    => 'p5:' . min($x, $y) . ':' . max($x, $y),
            'cero'     => $cero,
            'datos'    => ['izq' => $x, 'der' => $y],
            'opciones' => ['izq', 'der'],
            'correcta' => $correcta,
        ];
    }

    // ── Parte 6: puntos muy rápidos ──────────────────────────────────────

    private static function p6(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $max = self::PUNTOS_RAPIDOS_MAX[$nivel - 1];
        if ($fija) {
            // Siempre las mismas cantidades de referencia: 0 (concepto del cero), 2, 4 y 6.
            $n = [0, 2, 4, 6][$slot % 4];
        } else {
            $n = $r->entre(1, $max);
        }
        return [
            'clave'    => "p6:$n:" . $r->entre(1, 99999),
            'cero'     => $n === 0,
            'datos'    => ['n' => $n, 'ms' => self::PUNTOS_RAPIDOS_MS[$g] ?? self::PUNTOS_RAPIDOS_MS[1], 'semilla' => $r->entre(1, 1000000), 'max' => $max],
            'opciones' => range(0, $max),
            'correcta' => $n,
        ];
    }

    // ── Parte 7: línea de números ────────────────────────────────────────

    private static function p7(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $largo = self::LINEA[$g][$nivel - 1];
        $cero  = false;
        if ($fija) {
            $esperado = self::LINEA[$g][1];
            // Referencias de la línea: el 0 (concepto del cero), un número del primer tramo y uno del último.
            // Cambian de un ciclo a otro porque salen del sorteo con semilla del ciclo.
            $obj = match ($slot % 3) {
                0       => 0,
                1       => $r->entre((int) round($esperado * 0.2), (int) round($esperado * 0.4)),
                default => $r->entre((int) round($esperado * 0.6), (int) round($esperado * 0.8)),
            };
            $largo = $esperado;
            $cero = $obj === 0;
        } else {
            $obj = $r->entre(1, $largo - 1);
        }
        return [
            'clave'    => "p7:$largo:$obj",
            'cero'     => $cero,
            'datos'    => ['max' => $largo, 'objetivo' => $obj],
            'correcta' => $obj,
        ];
    }

    // ── Parte 8: falta un signo ──────────────────────────────────────────

    private static function p8(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cap  = self::CUENTAS[$g][$nivel - 1];
        $cero = false;

        if ($fija && $slot === 0) {                 // a − a = 0
            $a = $r->entre(2, max(2, intdiv($cap, 2)));
            [$x, $y, $z, $op] = [$a, $a, 0, '-'];
            $cero = true;
        } elseif ($fija && $slot === 1) {           // 0 + b = b
            $b = $r->entre(2, max(2, intdiv($cap, 2)));
            [$x, $y, $z, $op] = [0, $b, $b, '+'];
            $cero = true;
        } elseif ($r->moneda()) {                   // suma
            $z = $r->entre(3, $cap);
            $x = $r->entre(1, $z - 1);
            [$y, $op] = [$z - $x, '+'];
        } else {                                    // resta
            $x = $r->entre(3, $cap);
            $y = $r->entre(1, $x - 1);
            [$z, $op] = [$x - $y, '-'];
        }

        return [
            'clave'    => "p8:$x:$y:$z",
            'cero'     => $cero,
            'datos'    => ['a' => $x, 'b' => $y, 'c' => $z],
            'opciones' => ['+', '-'],
            'correcta' => $op,
            'tipos'    => ['+' => 'operacion_contraria', '-' => 'operacion_contraria'],
        ];
    }

    // ── Parte 9: cuentas con palillos ────────────────────────────────────

    private static function p9(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cap  = self::CUENTAS_PALILLOS[$g][$nivel - 1];
        $cero = false;

        // ¿La cuenta debe «llevar»? 2.º: solo en ampliado. 3.º: en esperado y ampliado.
        $llevada = match ($g) { 1 => null, 2 => $nivel === 3, default => $nivel >= 2 };

        if ($fija && $slot === 0) {                   // a + 0
            $a = $r->entre(2, max(2, $cap));
            [$x, $y, $op, $res] = [$a, 0, '+', $a];
            $cero = true;
        } elseif ($fija && $slot === 1) {             // a − a
            $a = $r->entre(2, max(2, $cap));
            [$x, $y, $op, $res] = [$a, $a, '-', 0];
            $cero = true;
        } else {
            $x = $y = $res = 0;
            $op = '+';
            for ($i = 0; $i < 300; $i++) {
                $op = $r->moneda() ? '+' : '-';
                if ($op === '+') {
                    $x = $r->entre(1, $cap - 1);
                    $y = $r->entre(1, $cap - $x);
                    $res = $x + $y;
                    $lleva = ($x % 10) + ($y % 10) >= 10;
                } else {
                    $x = $r->entre(2, $cap);
                    $y = $r->entre(1, $x - 1);
                    $res = $x - $y;
                    $lleva = ($x % 10) < ($y % 10);
                }
                if ($g === 1 || $llevada === null || $lleva === $llevada || $i > 250) {
                    break;
                }
            }
        }

        // Opciones: la correcta + la «operación contraria» + un «uno más o uno menos» (o uno lejano).
        $contraria = $op === '+' ? abs($x - $y) : $x + $y;
        $tipos = [];
        $opts  = [$res];
        if ($contraria !== $res) {
            $opts[] = $contraria;
            $tipos[(string) $contraria] = 'operacion_contraria';
        }
        $mu = array_values(array_filter([$res + 1, $res - 1], fn ($c) => $c >= 0 && !in_array($c, $opts, true)));
        if ($mu) {
            $c = $r->elegir($mu);
            $opts[] = $c;
            $tipos[(string) $c] = 'mas_menos_uno';
        }
        $guard = 0;
        while (count($opts) < 3 && $guard++ < 100) {
            $c = $r->entre(0, max($cap, $res + 5));
            if (!in_array($c, $opts, true) && abs($c - $res) >= 2) {
                $opts[] = $c;
                $tipos[(string) $c] = 'lejano';
            }
        }

        return [
            'clave'    => "p9:$x$op$y",
            'cero'     => $cero,
            'datos'    => ['a' => $x, 'b' => $y, 'op' => $op, 'repA' => self::representar($g, $x), 'repB' => self::representar($g, $y)],
            'opciones' => array_slice($opts, 0, 3),
            'correcta' => $res,
            'tipos'    => $tipos,
        ];
    }

    // ── Parte 10: ordenar números (qué número falta) ─────────────────────

    private static function p10(int $g, int $nivel, Aleatorio $r, bool $fija, int $slot): array
    {
        $cap    = self::RANGO[$g][$nivel - 1];
        $saltos = self::SALTOS[$g][$nivel - 1];
        $cero   = false;

        if ($fija && $slot === 0) {                       // falta el 0: _, 1, 2, 3, 4  (o _, 10, 20…)
            $paso = $g === 1 ? 1 : 10;
            $inicio = 0;
            $falta  = 0;
            $cero = true;
        } else {
            $paso = $r->elegir($saltos);
            $maxInicio = max(0, $cap - 4 * $paso);
            $inicio = $r->entre(0, $maxInicio);
            if (($paso >= 10 || $g === 3) && $inicio > 0) {      // saltos grandes: empezar en múltiplo del paso
                $inicio = intdiv($inicio, $paso) * $paso;
            }
            $falta = $r->entre(1, 3);                            // nunca el primero ni el último
        }

        $seq = [];
        for ($i = 0; $i < 5; $i++) {
            $seq[] = $inicio + $i * $paso;
        }
        $correcta = $seq[$falta];
        $seq[$falta] = null;

        // Distractores: uno más / uno menos, y uno lejano; nunca un número que ya se ve en la fila.
        $opts  = [$correcta];
        $tipos = [];
        $visibles = array_filter($seq, fn ($x) => $x !== null);
        $mu = array_values(array_filter([$correcta + 1, $correcta - 1], fn ($c) => $c >= 0 && !in_array($c, $visibles, true)));
        if ($mu) {
            $c = $r->elegir($mu);
            $opts[] = $c;
            $tipos[(string) $c] = 'mas_menos_uno';
        }
        $guard = 0;
        $lejos = max(3, 2 * $paso);
        while (count($opts) < 3 && $guard++ < 200) {
            $c = $r->entre(0, max($cap, $inicio + 5 * $paso));
            if (!in_array($c, $opts, true) && !in_array($c, $visibles, true) && abs($c - $correcta) >= $lejos) {
                $opts[] = $c;
                $tipos[(string) $c] = 'lejano';
            }
        }
        $guard = 0;
        while (count($opts) < 3 && $guard++ < 50) {              // respaldo para rangos muy cortos
            $c = $r->entre(0, $cap + 10);
            if (!in_array($c, $opts, true) && !in_array($c, $visibles, true)) {
                $opts[] = $c;
                $tipos[(string) $c] = 'lejano';
            }
        }

        return [
            'clave'    => 'p10:' . implode(',', array_map(fn ($x) => $x ?? '_', $seq)),
            'cero'     => $cero,
            'datos'    => ['seq' => $seq],
            'opciones' => $opts,
            'correcta' => $correcta,
            'tipos'    => $tipos,
        ];
    }

    // ── Utilidades de números y palillos ─────────────────────────────────

    /**
     * Cómo se dibuja un número con palillos: paquetes de 100 (c), atados de 10 (d) y sueltas (u).
     * 1.er grado: solo sueltas (y un atado si pasa de 10). 2.º: atados y sueltas. 3.º: los tres.
     */
    public static function representar(int $grado, int $n): array
    {
        if ($grado === 1) {
            return $n > 10 ? ['c' => 0, 'd' => 1, 'u' => $n - 10] : ['c' => 0, 'd' => 0, 'u' => $n];
        }
        if ($grado === 2) {
            return ['c' => 0, 'd' => intdiv($n, 10), 'u' => $n % 10];
        }
        return ['c' => intdiv($n, 100), 'd' => intdiv($n % 100, 10), 'u' => $n % 10];
    }

    /**
     * Tres opciones numéricas: la correcta y dos distractores, con su tipo de error.
     *
     * @return array{0:int[],1:array<string,string>}  [opciones, tipo de error por opción]
     */
    private static function opcionesNumericas(int $g, int $v, int $cap, Aleatorio $r): array
    {
        $usados = [$v];
        $tipos  = [];
        $cands  = [];

        $par = self::parecido($g, $v, $cap);
        if ($par !== null) {
            $cands[] = [$par, 'parecido'];
        }

        $mu = array_values(array_filter([$v + 1, $v - 1], fn ($c) => $c >= 0 && $c <= $cap));
        if ($mu) {
            $cands[] = [$r->elegir($mu), 'mas_menos_uno'];
        }

        $lejos = max(3, (int) ceil($cap * 0.3));
        $lej = [];
        for ($c = 0; $c <= $cap; $c++) {
            if (abs($c - $v) >= $lejos) {
                $lej[] = $c;
            }
        }
        if ($lej) {
            $cands[] = [$r->elegir($lej), 'lejano'];
        }

        // Si hay «parecido» va siempre; el otro distractor se sortea entre los restantes.
        $elegidos = [];
        if ($par !== null) {
            $elegidos[] = $cands[0];
            $resto = array_slice($cands, 1);
        } else {
            $resto = $cands;
        }
        $resto = $r->barajar($resto);
        foreach ($resto as $c) {
            if (count($elegidos) >= 2) {
                break;
            }
            $elegidos[] = $c;
        }

        $opts = [$v];
        foreach ($elegidos as [$val, $tipo]) {
            if (!in_array($val, $opts, true)) {
                $opts[] = $val;
                $tipos[(string) $val] = $tipo;
            }
        }
        $guard = 0;
        while (count($opts) < 3 && $guard++ < 100) {              // respaldo
            $c = $r->entre(0, $cap);
            if (!in_array($c, $opts, true)) {
                $opts[] = $c;
                $tipos[(string) $c] = 'lejano';
            }
        }

        return [$r->barajar($opts), $tipos];
    }

    /** Número que se confunde por su forma o por el orden de sus cifras (null si no hay). */
    private static function parecido(int $g, int $v, int $cap): ?int
    {
        if ($g === 1) {
            $p = self::PARECIDOS[$v] ?? null;
            return ($p !== null && $p <= $cap) ? $p : null;
        }

        $s = (string) $v;
        $cands = [];
        if ($g === 3 && str_contains($s, '0') && strlen($s) === 3) {
            $cands[] = (int) str_replace('0', '', $s);                       // 305 → 35
            $cands[] = (int) ($s[0] . $s[2] . $s[1]);                        // 305 → 350
        }
        if (strlen($s) >= 2) {
            $cands[] = (int) strrev($s);                                     // 12 → 21
            $cands[] = (int) (substr($s, 0, -2) . $s[strlen($s) - 1] . $s[strlen($s) - 2]);   // 345 → 354
        }
        foreach ($cands as $c) {
            if ($c !== $v && $c >= 0 && $c <= $cap) {
                return $c;
            }
        }
        return null;
    }
}
