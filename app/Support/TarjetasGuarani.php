<?php

namespace App\Support;

/**
 * SOPORTE: TarjetasGuarani
 * =========================================================================
 * Guaraní revisado por la persona hablante de las TARJETAS de «Encuentra el par»
 * (tabla tarjetas_nivel, columna nombre_gn): los 10 objetos escolares
 * (IDs EP-OBJ-01..10) y los números del 0 al 9 (EP-NUM-0..9).
 *
 * Fuente única: la usan el seeder (instalación nueva) y la migración
 * 2026_11_19_000004 (base existente). Se busca la tarjeta por su «valor»
 * interno (mochila, lapiz, … / 0 a 9), nunca por id.
 * Aplicado el 2026-11-19 a partir de la planilla «Guarani_EncuentraElPar_revisado».
 * =========================================================================
 */
final class TarjetasGuarani
{
    /** valor interno => nombre_gn */
    public const OBJETOS = [
        'mochila' => 'Mochila',
        'lapiz' => 'Haiha',
        'regla' => 'Regla',
        'tijera' => 'Jetapa',
        'crayon' => 'Crayón',
        'cuaderno' => 'Kuatiajehaipyre',
        'libro' => 'Aranduka',
        'lapicera' => 'Haiha syry',
        'carpeta' => 'Kuatiaryru',
        'calculadora' => 'Papapyhára',
    ];

    /** valor (0–9) => nombre_gn (formato «Papapy N (Numeral)») */
    public const NUMEROS = [
        '0' => 'Papapy 0 (mba\'eve)',
        '1' => 'Papapy 1 (Peteĩ)',
        '2' => 'Papapy 2 (Mokõi)',
        '3' => 'Papapy 3 (Mbohapy)',
        '4' => 'Papapy 4 (Irundy)',
        '5' => 'Papapy 5 (Po)',
        '6' => 'Papapy 6 (Poteĩ)',
        '7' => 'Papapy 7 (Pokõi)',
        '8' => 'Papapy 8 (Poapy)',
        '9' => 'Papapy 9 (Porundy)',
    ];

    /** @return string|null  nombre_gn de una tarjeta, o null si no está en la lista revisada */
    public static function nombre(string $tipoContenido, string $valor): ?string
    {
        return match ($tipoContenido) {
            'objeto' => self::OBJETOS[$valor] ?? null,
            'numero' => self::NUMEROS[$valor] ?? null,
            default  => null,
        };
    }
}
