<?php

namespace App\Support;

/**
 * SOPORTE: EnunciadosGuarani
 * =========================================================================
 * Un solo lugar para el guaraní de los enunciados guardados en la base de
 * datos de «Suma de Frutas» (columna contenido_nivel.enunciado_gn).
 *
 * Texto revisado por la persona hablante (planilla «Guarani_SumaDeFrutas_revisado»,
 * IDs SF-EJB-01 a SF-EJB-15 y SF-EJG-01/02) y aplicado el 2026-11-19.
 *
 *   • Los 15 ejercicios originales de 1.° grado (niveles 1 a 3) llevan los
 *     números escritos en guaraní (peteĩ, mokõi, mbohapy, …).
 *   • Todos los demás llevan las cifras: «¿Mboy'e pa 12 ha 5?».
 *
 * Los enunciados son de respaldo: con frutas, el niño ve las frases de
 * App\Support\FrutasJuego. Aun así se mantienen correctos porque aparecen
 * en reportes y en el panel docente.
 * =========================================================================
 */
final class EnunciadosGuarani
{
    /** Numerales 1 a 10 (SF-GLO-N01..N10, confirmados en la revisión). */
    public const NUMERALES = [
        1 => 'peteĩ', 2 => 'mokõi', 3 => 'mbohapy', 4 => 'irundy', 5 => 'po',
        6 => 'poteĩ', 7 => 'pokõi', 8 => 'poapy', 9 => 'porundy', 10 => 'pa',
    ];

    /**
     * Los 15 ejercicios originales de 1.° grado: "nivel|a|b" (todos son sumas).
     * El texto de cada uno es el de la columna «Guaraní corregido» (SF-EJB-xx).
     */
    public const SUMA_FRUTAS_BASE = [
        '1|2|3' => "¿Mboy'e pa mokõi ha mbohapy?",
        '1|4|1' => "¿Mboy'e pa irundy ha peteĩ?",
        '1|3|2' => "¿Mboy'e pa mbohapy ha mokõi?",
        '1|1|4' => "¿Mboy'e pa peteĩ ha irundy?",
        '1|2|2' => "¿Mboy'e pa mokõi ha mokõi?",
        '2|7|3' => "¿Mboy'e pa pokõi ha mbohapy?",
        '2|5|4' => "¿Mboy'e pa po ha irundy?",
        '2|6|2' => "¿Mboy'e pa poteĩ ha mokõi?",
        '2|4|5' => "¿Mboy'e pa irundy ha po?",
        '2|3|7' => "¿Mboy'e pa mbohapy ha pokõi?",
        '3|8|5' => "¿Mboy'e pa poapy ha po?",
        '3|9|4' => "¿Mboy'e pa porundy ha irundy?",
        '3|7|6' => "¿Mboy'e pa pokõi ha poteĩ?",
        '3|6|8' => "¿Mboy'e pa poteĩ ha poapy?",
        '3|9|6' => "¿Mboy'e pa porundy ha poteĩ?",
    ];

    /**
     * Flash Matemático: los 15 ejercicios originales de 1.° grado, "nivel|a|b" (todos son sumas).
     * Texto de la columna «Guaraní corregido» (FM-EJB-01..15). En pantalla NO se muestra
     * (Flash oculta la cuenta); queda guardado para reportes y como respaldo.
     */
    public const FLASH_BASE = [
        '1|3|4' => "¡Emaña! mbohapy ha irundy. ¡Eñemandu'a hese!",
        '1|2|5' => "¡Emaña! mokõi ha po. ¡Eñemandu'a hese!",
        '1|4|3' => "¡Emaña! irundy ha mbohapy. ¡Eñemandu'a hese!",
        '1|1|5' => "¡Emaña! peteĩ ha po. ¡Eñemandu'a hese!",
        '1|5|3' => "¡Emaña! po ha mbohapy. ¡Eñemandu'a hese!",
        '2|6|4' => "¡Pya'e! poteĩ ha irundy.",
        '2|7|2' => "¡Pya'e! pokõi ha mokõi.",
        '2|5|5' => "¡Pya'e! po ha po.",
        '2|8|2' => "¡Pya'e! poapy ha mokõi.",
        '2|4|6' => "¡Pya'e! irundy ha poteĩ.",
        '3|9|3' => "¡Flash! porundy ha mbohapy.",
        '3|8|4' => "¡Flash! poapy ha irundy.",
        '3|7|5' => "¡Flash! pokõi ha po.",
        '3|6|7' => "¡Flash! poteĩ ha pokõi.",
        '3|9|5' => "¡Flash! porundy ha po.",
    ];

    public static function flashBase(int $numeroNivel, int $a, int $b): ?string
    {
        return self::FLASH_BASE["{$numeroNivel}|{$a}|{$b}"] ?? null;
    }

    /** Plantilla para todos los demás ejercicios (SF-EJG-01 suma, SF-EJG-02 resta). */
    public static function plantilla(int $a, string $operador, int $b): string
    {
        return self::esResta($operador)
            ? "¿Mboy'e pa {$a} ha {$b} jepe'apy?"
            : "¿Mboy'e pa {$a} ha {$b}?";
    }

    /**
     * Enunciado en guaraní de un ejercicio de Suma de Frutas.
     *
     * @param int $grado        grado del nivel (1, 2 o 3)
     * @param int $numeroNivel  número de nivel dentro del grado
     */
    public static function sumaFrutas(int $grado, int $numeroNivel, int $a, string $operador, int $b): string
    {
        if ($grado === 1 && !self::esResta($operador)) {
            $clave = "{$numeroNivel}|{$a}|{$b}";
            if (isset(self::SUMA_FRUTAS_BASE[$clave])) {
                return self::SUMA_FRUTAS_BASE[$clave];
            }
        }

        return self::plantilla($a, $operador, $b);
    }

    private static function esResta(string $operador): bool
    {
        return in_array(trim($operador), ['-', '−', '–'], true);
    }
}
