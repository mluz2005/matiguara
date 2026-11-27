<?php

namespace App\Support;

/**
 * SOPORTE: TextosGuaraniCompartidos
 * =========================================================================
 * Guaraní revisado por la persona hablante de los textos que usan los CINCO
 * juegos y que viven en la base de datos: los mensajes de ánimo
 * (mensajes_retro, IDs CMP-RET-01..13) y las medallas (catalogo_logros,
 * IDs CMP-MED-01..10).
 *
 * Es la ÚNICA fuente: la usan los seeders (instalación nueva) y la migración
 * 2026_11_19_000002 (base existente). Para corregir un texto se cambia acá.
 * Aplicado el 2026-11-19 a partir de la planilla «Guarani_SumaDeFrutas_revisado».
 * =========================================================================
 */
final class TextosGuaraniCompartidos
{
    /** texto_es (exacto, como está en la base) => texto_gn */
    public const MENSAJES = [
        // CMP-RET-01
        '¡Excelente! Lo lograste. Seguí así, vas muy bien.' =>
            '¡Iporãite! Rejapo porã. Eikove katu, iporãite.',
        // CMP-RET-02
        '¡Muy bien! Esa respuesta es correcta. ¡Sos increíble!' =>
            '¡Iporãite! Upe mbohovái oĩporã. ¡Nde arandu!',
        // CMP-RET-03
        '¡Perfecto en el primer intento! Sos un crack de las matemáticas.' =>
            '¡Iporãite ñepyrũme! Nde arandu matemátikape.',
        // CMP-RET-04
        '¡Lo conseguiste! El esfuerzo siempre vale la pena. ¡Adelante!' =>
            '¡Rejapo! Tembiapo porã aréma opyta. ¡Eho tenonde!',
        // CMP-RET-05
        'No importa, ¡intentalo de nuevo! Cada error te hace más fuerte.' =>
            'Nahániri, ¡erejere jevy! Opa jejavy omombareteve.',
        // CMP-RET-06
        'Casi lo tenés. ¡Revisá bien y volvé a intentarlo!' =>
            'Hasyete mbytépe. ¡Ehecha porã ha erejere jevy!',
        // CMP-RET-07
        'Buena pregunta difícil. Pensá despacio y podrás lograrlo.' =>
            'Porã porã. Emano mbegue ha rejapóta.',
        // CMP-RET-08
        'No te rindas, ¡vos podés! Mirá bien los números y probá.' =>
            'Ani remboyke, ¡nde ikatúva! Ehecha porã papapy ha eñeme\'ẽ.',
        // CMP-RET-09
        '¡Racha activa! Llevás varios aciertos seguidos. ¡No pares!' =>
            '¡Racha oĩva! Rejapyhy hetave acierto. ¡Ani rekyhyje!',
        // CMP-RET-10
        '¡Nuevo récord! ¡Esta es tu mejor racha de aciertos! ¡Extraordinario!' =>
            '¡Récord pyahu! Ko ne racha iporãvéva. ¡Iporãite!',
        // CMP-RET-11
        '¡Nivel superado! ¡Sos increíble! El siguiente nivel te espera.' =>
            '¡Nivel oñembohasa! ¡Nde arandu! Nivel pyahu oha\'arõ ndéve.',
        // CMP-RET-12
        '¡Lo lograste! Pasaste al siguiente nivel. ¡Seguí brillando!' =>
            '¡Rejapo! Rehasa nivel pyahúpe. ¡Ehesape katu!',
        // CMP-RET-13
        '¡Bienvenido de nuevo! Hoy vas a aprender cosas increíbles. ¡Empecemos!' =>
            '¡Maitei jevy! Ko árape reñe\'ẽta mba\'e ipyahúva. ¡Ñañepyrũ!',
    ];

    /** nombre_es de la medalla => [nombre_gn, descripcion_gn] */
    public const MEDALLAS = [
        // CMP-MED-01
        'Primera Racha' => ['Peteĩha Racha', 'Embohovái 3 tembiapo oñondive.'],
        // CMP-MED-02
        'Imparable' => ['Oky\'o\'ỹva', 'Embohovái 10 tembiapo oñondive.'],
        // CMP-MED-03
        'Maestro del Cálculo' => ['Papapy Mbo\'ehára', 'Embohovái 20 tembiapo oñondive.'],
        // CMP-MED-04
        'Primer Paso' => ['Peteĩha Tape', 'Embosarãi ne ñepyrũha.'],
        // CMP-MED-05
        'Constante' => ['Oñondive', 'Embosarãi 10 ñembosarãi.'],
        // CMP-MED-06
        'Nivel Conquistado' => ['Nivel Oñembojára', 'Ehasa ne ñepyrũha nivel.'],
        // CMP-MED-07
        'Explorador' => ['Mba\'ehecha', 'Ehasa 5 nivel ambue.'],
        // CMP-MED-08
        'Semana Completa' => ['Arapokõindy Kue', 'Embosarãi 7 ára oñondive.'],
        // CMP-MED-09
        'Leyenda' => ['Mombe\'ugua\'u', 'Embosarãi 30 ára oñondive.'],
        // CMP-MED-10
        'Flash Master' => ['Flash Master', 'Ehasa pe nivel pahue Flash Matemático-gui. ¡Nepenamandu\'a iporãite!'],
    ];

    public static function mensaje(string $textoEs, ?string $actual = null): ?string
    {
        return self::MENSAJES[$textoEs] ?? $actual;
    }

    /** @return array{0:string,1:string}|null  [nombre_gn, descripcion_gn] */
    public static function medalla(string $nombreEs): ?array
    {
        return self::MEDALLAS[$nombreEs] ?? null;
    }
}
