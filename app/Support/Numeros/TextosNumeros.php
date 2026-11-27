<?php

namespace App\Support\Numeros;

/**
 * TEXTOS de «Mati y los números»: nombre de cada viaje y de cada parte, y la instrucción
 * que Mati dice antes de empezarla (en castellano y en guaraní).
 *
 * Guaraní: versión REVISADA (planilla Guarani_MatiYLosNumeros_revisado, columna «Guaraní corregido»,
 * 2026-11-19). Es la fuente única: la usan el seeder, la migración de datos, las vistas y numeros.js
 * (que la recibe por NM_CFG.tx). Las voces se graban aparte y se colocan en public/audio/numeros/
 * (ver NumerosService::voces()).
 */
final class TextosNumeros
{
    /** MN-JUE-01 / MN-JUE-02 — nombre e instrucciones del juego (tabla juegos). */
    public const NOMBRE_ES = 'Mati y los números';
    public const NOMBRE_GN = 'Mati ha papapykuéra';
    public const INSTRUCCIONES_GN = "Eguata Mati ndive ka'aguýre. Peteĩteĩ tendápe oĩ papapy ha hetakue: epoko mbohovái ndéve iporãvéva. ¡Ndaipóri kyta ni jopói, ñembosarái añónte!";

    /** Nombre de cada viaje (= nivel del menú). */
    public const VIAJES = [
        1 => ['es' => 'Los símbolos',   'gn' => 'Umi techaukaha',   'ico' => '🔢'],
        2 => ['es' => 'Las cantidades', 'gn' => 'Umi hetakue',      'ico' => '🐾'],
        3 => ['es' => 'Los signos',     'gn' => 'Umi mbojuaju ha jepe\'a rechaukaha', 'ico' => '➕'],
    ];

    /** parte => título e instrucción de Mati (MN-PAR-nn-T / MN-PAR-nn-I) */
    public const PARTES = [
        1  => ['es' => 'Calentamiento',          'gn' => 'Ñembosako\'i',
               'ies' => '¡Hola! Soy Mati. Tocá la luciérnaga cada vez que aparezca.',
               'ign' => "¡Mba'éichapa! Che ha'e Mati. Epoko muãre ojehecha jave."],
        2  => ['es' => 'Número y palillos',      'gn' => "Papapy ha yvyra'i",
               'ies' => 'Mirá el número. Tocá el montón de ramitas que tiene esa cantidad.',
               'ign' => "Emaña papapýre. Epoko yvyra'i atýre oguerekóva upe hetakue."],
        3  => ['es' => 'Palillos y número',      'gn' => "Yvyra'i ha papapy",
               'ies' => 'Mirá las ramitas. Tocá el número que dice cuántas hay.',
               'ign' => "Emaña umi yvyra'íre. Epoko papapýre he'íva mboýpa oĩ."],
        4  => ['es' => '¿Dónde hay más puntos?', 'gn' => '¿Moõpa oĩ hetave kyta?',
               'ies' => 'No hace falta contar. Tocá el lado que tiene más puntos.',
               'ign' => 'Natekotevẽi reipapa. Epoko pe yke oguerekóva hetave kyta.'],
        5  => ['es' => '¿Cuál es más grande?',   'gn' => '¿Mávapa tuichave?',
               'ies' => 'Tocá el número que es más grande.',
               'ign' => 'Epoko papapy tuichavévare.'],
        6  => ['es' => 'Puntos muy rápidos',     'gn' => 'Kyta pya\'eterei',
               'ies' => 'Los puntos aparecen un ratito y se van. ¿Cuántos viste? Tocá el número.',
               'ign' => "Umi kyta ojehecha sapy'ami ha okañy. ¿Mboýpa rehecha? Epoko papapýre."],
        7  => ['es' => 'Línea de números',       'gn' => 'Papapy tysýi',
               'ies' => 'Mirá la línea. Tocá el lugar donde va el número.',
               'ign' => 'Emaña tysýire. Epoko tenda oho hápe pe papapy.'],
        8  => ['es' => 'Falta un signo',         'gn' => 'Ofalta peteĩ techaukaha',
               'ies' => 'A la cuenta le falta un signo. Tocá el signo que va: más o menos.',
               'ign' => 'Papapy apópe ofalta peteĩ techaukaha. Epoko techaukaha oúvare: mbojuaju térã jepe\'a.'],
        9  => ['es' => 'Cuentas con palillos',  'gn' => "Papapy apo yvyra'i ndive",
               'ies' => 'Mirá las ramitas y resolvé la cuenta. Tocá el resultado.',
               'ign' => "Emaña umi yvyra'íre ha emyengovia papapy apo. Epoko osẽvare."],
        10 => ['es' => 'Ordenar números',        'gn' => 'Ñemohenda papapykuéra',
               'ies' => 'Los números van en orden. ¿Cuál falta? Tocalo.',
               'ign' => 'Umi papapy oho hendápe porã. ¿Mávapa ofalta? Epoko hese.'],
    ];

    /** Figuritas de la selva que se muestran al terminar un viaje (una por viaje, en orden). */
    public const FIGURITAS = [
        ['img' => 'yaguarete', 'es' => 'el yaguareté', 'gn' => 'jaguarete'],
        ['img' => 'carpincho', 'es' => 'el carpincho', 'gn' => "kapi'yva"],
        ['img' => 'tatu',      'es' => 'el tatú',      'gn' => 'tatu'],
        ['img' => 'nandu',     'es' => 'el ñandú',     'gn' => 'ñandu'],
        ['img' => 'yacare',    'es' => 'el yacaré',    'gn' => 'jakare'],
        ['img' => 'tuka',      'es' => 'el tucán',     'gn' => 'tukã'],
        ['img' => 'mborevi',   'es' => 'el tapir',     'gn' => 'mborevi'],
        ['img' => 'mainumby',  'es' => 'el colibrí',   'gn' => 'mainumby'],
        ['img' => 'karumbe',   'es' => 'la tortuga',   'gn' => 'karumbe'],
    ];

    /**
     * Textos de las pantallas (MN-UI-nn) y de las preguntas (MN-PRE-nn). Los llevan numeros.js
     * (NM_CFG.tx) y las vistas. Marcadores: {viaje} = nombre del viaje, {figurita} = nombre de la
     * figurita, {n} = número; cada lengua lleva el nombre en SU lengua.
     * Los que no tienen ID propio en la planilla se marcan «derivado» (ver DECISIONES_MatiYLosNumeros.md).
     */
    public const UI = [
        'hola'         => ['es' => '¡Hola! Soy Mati', 'gn' => "¡Mba'éichapa! Che ha'e Mati"],                                   // MN-UI-01
        'intro'        => ['es' => 'Hoy vamos de viaje por la selva: {viaje}. Tocá lo que te parezca. ¡No hay puntos!',
                           'gn' => "Ko árape jaha jeguatápe ka'aguýre: {viaje}. Epoko pe ndéve iporãvéva. ¡Ndaipóri kyta!"], // MN-UI-02
        'vamos'        => ['es' => '▶ ¡Vamos!',           'gn' => '▶ ¡Jaha!'],                                                // MN-UI-03
        'empezar'      => ['es' => '¡Listo! Empezar',     'gn' => '¡Oĩmbáma! Ñañepyrũ'],                                      // MN-UI-04
        'listo'        => ['es' => 'Listo ✔',             'gn' => 'Oĩmbáma ✔'],                                               // MN-UI-05
        'vozGn'        => ['es' => '🔊 Guaraní',          'gn' => '🔊 Guaraní'],                                              // MN-UI-06
        'salir'        => ['es' => 'Salir',               'gn' => 'Ñesẽ'],                                                    // MN-UI-07
        'salirOtra'    => ['es' => '¿Salir? Tocá otra vez', 'gn' => '¿Resẽse? Epoko jey'],                                   // MN-UI-07
        'errTitulo'    => ['es' => 'Uy, no hay conexión', 'gn' => 'Áina, ndaipóri ñanduti'],                                  // MN-UI-08
        'errBoton'     => ['es' => 'Probar otra vez',     'gn' => "Eha'ã jey"],                                               // MN-UI-09
        'sinConexion'  => ['es' => 'Sin conexión. Seguimos igual.', 'gn' => "Ñanduti'ỹre. Jaha tenonde upéicha."],            // MN-UI-10
        'salioMal'     => ['es' => 'Algo salió mal. Probá de nuevo.', 'gn' => "Peteĩ mba'e osẽ vai. Eha'ã jey."],             // MN-UI-11
        'finTitulo'    => ['es' => '¡Terminaste el viaje!', 'gn' => "¡Remohu'ãma pe jeguata!"],                              // MN-UI-12
        'finTexto'     => ['es' => 'Te llevás una figurita de la selva: {figurita}.',
                           'gn' => "Reraha peteĩ ta'anga ka'aguypegua: {figurita}."],                                         // MN-UI-13
        'descTitulo'   => ['es' => '¡Descansemos!',       'gn' => "¡Ñapytu'u!"],                                              // MN-UI-14
        'descTexto'    => ['es' => 'Guardamos lo que hiciste. ¡Gracias por jugar con Mati!',
                           'gn' => "Roñongatu rejapo va'ekue. ¡Aguyje reñembosarái haguére Mati ndive!"],                     // MN-UI-15
        'tiempoTitulo' => ['es' => '¡Terminó el tiempo de hoy!', 'gn' => '¡Opa ko ára pegua aravo!'],                         // MN-UI-16
        'tiempoTexto'  => ['es' => 'Guardamos lo que hiciste. Descansá un ratito y mirá lejos.',
                           'gn' => "Roñongatu rejapo va'ekue. Epytu'u sapy'ami ha emaña mombyry."],                           // MN-UI-17
        'volverJuegos' => ['es' => '🎮 Volver a los juegos', 'gn' => '🎮 Jaha jey ñembosaraikuérape'],                       // MN-UI-18
        'irDescansar'  => ['es' => '😌 Ir a descansar',   'gn' => "😌 Jaha ñapytu'u"],                                        // MN-UI-18
        'hoy1'         => ['es' => '¡Hoy ya hiciste un viaje! Mañana seguimos con {viaje}.',
                           'gn' => "¡Ko árape rejapóma peteĩ jeguata! Ko'ẽrõ jaha jey {viaje} ndive."],                      // MN-UI-19
        'hoy2'         => ['es' => 'Mientras tanto, ¡jugá otros juegos!', 'gn' => "Upe aja, ¡eñembosarái ambue ñembosaráipe!"], // MN-UI-20
        'hoyJugar'     => ['es' => '🎮 Jugar otros juegos', 'gn' => '🎮 Ñembosarái ambue'],                                    // MN-UI-21
        'hoySeguir'    => ['es' => 'Seguir igual',        'gn' => 'Jaha tenonde upéicha'],                                    // MN-UI-21
        'ciclo'        => ['es' => '🦜 Ciclo {n} · Hoy toca: {viaje}', 'gn' => '🦜 Jere {n} · Ko árape oĩ: {viaje}'],         // MN-UI-22
        'viajeN'       => ['es' => 'Viaje {n}',           'gn' => 'Jeguata {n}'],                                             // MN-UI-22
    ];

    /** Preguntas de cada parte (MN-PRE-nn). Clave = tipo de pregunta de numeros.js. */
    public const PREGUNTAS = [
        'luz'          => ['es' => '¡Tocá la luz!',            'gn' => '¡Epoko tesapépe!'],            // MN-PRE-01
        'luzDemo'      => ['es' => 'Tocá la luciérnaga',       'gn' => 'Epoko muãme'],                 // MN-PRE-02
        'ramitas'      => ['es' => 'Tocá las ramitas que son:', 'gn' => "Epoko yvyra'i ha'éva:"],       // parte 2 — derivado (ver decisiones)
        'numero'       => ['es' => '¿Qué número es?',          'gn' => '¿Mba\'e papapýpa kóva?'],       // MN-PRE-03 (parte 3)
        'puntos'       => ['es' => '¿Dónde hay más puntos?',   'gn' => '¿Moõpa oĩ hetave kyta?'],      // MN-PRE-04
        'grande'       => ['es' => '¿Cuál es más grande?',     'gn' => '¿Mávapa tuichave?'],           // MN-PRE-05
        'rapidoDemo'   => ['es' => 'Mirá los puntos',          'gn' => 'Emaña umi kytáre'],            // MN-PRE-06
        'rapido'       => ['es' => '¡Mirá bien!',              'gn' => '¡Emaña porã!'],                // MN-PRE-07
        'linea'        => ['es' => '¿Dónde va este número?',   'gn' => '¿Moõpa oho ko papapy?'],       // MN-PRE-08
        'signo'        => ['es' => '¿Qué signo falta?',        'gn' => "¿Mba'e techaukahápa ofalta?"], // MN-PRE-09
        'cuenta'       => ['es' => 'Resolvé la cuenta',        'gn' => 'Emyengovia papapy apo'],       // parte 9 — derivado
        'falta'        => ['es' => '¿Qué número falta?',       'gn' => "¿Mba'e papapýpa ofalta?"],     // MN-PRE-10
    ];

    /** Cambia {viaje}, {figurita}, {n} en un texto. */
    public static function rellenar(string $texto, array $vars): string
    {
        $buscar = [];
        $poner  = [];
        foreach ($vars as $k => $v) {
            $buscar[] = '{' . $k . '}';
            $poner[]  = (string) $v;
        }
        return str_replace($buscar, $poner, $texto);
    }

    /** Todo lo que necesita numeros.js (viene en NM_CFG.tx). */
    public static function paraJs(): array
    {
        return ['ui' => self::UI, 'pre' => self::PREGUNTAS];
    }

    /** Figurita que corresponde a un viaje de un ciclo (se van recorriendo las nueve). */
    public static function figurita(int $ciclo, int $viaje): array
    {
        $i = (($ciclo - 1) * 3 + ($viaje - 1)) % count(self::FIGURITAS);
        return self::FIGURITAS[$i];
    }
}
