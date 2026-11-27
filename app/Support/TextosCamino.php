<?php

namespace App\Support;

/**
 * SOPORTE: TextosCamino
 * =========================================================================
 * Guaraní revisado por la persona hablante de «El camino a la escuela»
 * (planilla «Guarani_ElCaminoALaEscuela_revisado») que vive en la base de datos:
 *
 *   • juegos.instrucciones_gn ............. CE-JUE-02
 *   • catalogo_logros (4 medallas) ........ CE-MED-1..4 y CE-MED-1d..4d
 *   • contenido_nivel.enunciado_gn ........ los 738 problemas, armados a partir
 *     de las 8 plantillas CE-PRO-01..08 (ver ENUNCIADOS) y los 8 personajes (CE-NOM-01).
 *
 * El resto (mensajes de Mati, pistas, soluciones, botones, carteles) está en
 * public/js/laberinto-v2.js y en la vista laberinto-tablero.blade.php.
 *
 * Fuente única de los problemas: la usan la migración 2026_11_19_000005 (base
 * existente) y el banco database/seeders/data/laberinto_ejercicios.json (instalación
 * nueva; una prueba comprueba que coincidan fila por fila).
 * =========================================================================
 */
final class TextosCamino
{
    public const NOMBRE_GN = "Tape mbo'ehaópe";
    public const INSTRUCCIONES_GN = 'Eraha ne irũme ógagui mbo\'ehaópe. Peteĩ mba\'e ojokóramo nde rape, emyengovia pe apañuãi reipe\'a hag̃ua. ¡Embyaty chipa tape po\'ípe!';

    /** Los 8 personajes de los problemas (los mismos en castellano y guaraní: CE-NOM-01). */
    public const PERSONAJES = ['Don Pedro', 'Ña Rosa', 'Tito', 'Mabel', 'Doña Elva', 'Juancito', 'Don Ramón', 'Tía Nena'];

    /** nombre_es de la medalla => [nombre_gn, descripcion_gn] */
    public const MEDALLAS = [
        'Primer camino a la escuela' => ['Peteĩha tape mbo\'ehaópe', 'Eguahẽ mbo\'ehaópe peteĩha jey Tape mbo\'ehaópe-pe.'],
        'Camino perfecto' => ['Tape iporãitereíva', 'Eguahẽ mbo\'ehaópe mbohapy mbyja ndive: pytyvõrã\'ỹre, hu\'y\'ỹre ha rehecha\'ỹre ñemyengovia.'],
        'Chipero' => ['Chipero', 'Embyaty opaite chipa tape po\'ígui peteĩ tapépe.'],
        'Maestro del camino' => ['Tape mbo\'ehára', 'Ehasa pe yvatekue paha Tape mbo\'ehaópe-pe.'],
    ];

    /**
     * Plantillas de los problemas (CE-PRO-01..08, columna «Guaraní corregido»).
     * {nombre} = personaje · {n1} {n2} {n3} = los números del problema, en el orden en que
     * aparecen en el enunciado en castellano.
     */
    public const ENUNCIADOS = [
        'suma_mandioca' => '{nombre} oguereko {n1} mandi\'o. Omono\'õ {n2} ve. ¿Mboy mandi\'o piko oguereko ko\'ágã?',
        'resta_pesca' => '{nombre} oipirakutu {n1} pira. Ohejajey {n2} ysyry\'ípe. ¿Mboy pira piko opyta chupe?',
        'suma_corral' => 'Korápe oĩ {n1} vaka. Oike {n2} vaka ve. ¿Mboy vaka piko oĩ ko\'ágã?',
        'resta_corral' => 'Korápe oĩ {n1} vaka. Osẽ {n2} vaka kapi\'ipépe. ¿Mboy vaka piko opyta korápe?',
        'falta_corral' => 'Korápe oĩ {n1} vaka. Ñemuha guasurã oñeikotevẽ {n2}. ¿Mboy vaka piko ofalta?',
        'dos_pasos_vende_hornea' => '{nombre} oguereko {n1} chipa. Ohepyme\'ẽ {n2}. Upéi ombojy {n3} ve. ¿Mboy chipa piko oguereko ko\'ágã?',
        'dos_pasos_hornea_vende' => '{nombre} oguereko {n1} chipa. Ombojy {n2} ve. Upéi ohepyme\'ẽ {n3}. ¿Mboy chipa piko oguereko ko\'ágã?',
        'dos_pasos_falta' => '{nombre} oguereko {n1} chipa. Upéi ombojy {n2} ve. Peteĩ joguahára oipota {n3}. ¿Mboy chipa piko ofalta chupe?',
    ];

    /** Patrones del enunciado en castellano → plantilla (singular y plural: «1 vaca» / «2 vacas»). */
    private const PATRONES = [
        'suma_mandioca'          => '/^(?:%N%) tiene \d+ mandiocas?\. Cosecha \d+ más\./u',
        'resta_pesca'            => '/^(?:%N%) pescó \d+ peces\. Devuelve \d+ al arroyo\./u',
        'suma_corral'            => '/^En el corral hay \d+ vacas?\. Entran \d+ vacas más\./u',
        'resta_corral'           => '/^En el corral hay \d+ vacas?\. Salen \d+ vacas al pasto\./u',
        'falta_corral'           => '/^En el corral hay \d+ vacas?\. Para la feria hacen falta \d+\./u',
        'dos_pasos_vende_hornea' => '/^(?:%N%) tiene \d+ chipas?\. Vende \d+\. Después hornea \d+ más\./u',
        'dos_pasos_hornea_vende' => '/^(?:%N%) tiene \d+ chipas?\. Hornea \d+ más\. Después vende \d+\./u',
        'dos_pasos_falta'        => '/^(?:%N%) tiene \d+ chipas?\. Después hornea \d+ más\. Un cliente quiere \d+\./u',
    ];

    /**
     * Enunciado en guaraní de un problema, a partir de su enunciado en castellano.
     * Devuelve null si el enunciado no es de ninguna de las 8 historias (no se toca).
     */
    public static function enunciado(string $enunciadoEs): ?string
    {
        $nombres = implode('|', array_map(fn ($n) => preg_quote($n, '/'), self::PERSONAJES));

        foreach (self::PATRONES as $clave => $patron) {
            if (!preg_match(str_replace('%N%', $nombres, $patron), $enunciadoEs)) {
                continue;
            }

            $nombre = '';
            foreach (self::PERSONAJES as $p) {
                if (str_starts_with($enunciadoEs, $p . ' ')) {
                    $nombre = $p;
                    break;
                }
            }

            // Los números del problema, en orden (el último «?» del enunciado no tiene cifras).
            preg_match_all('/\d+/', $enunciadoEs, $m);
            $n = $m[0];

            return strtr(self::ENUNCIADOS[$clave], [
                '{nombre}' => $nombre,
                '{n1}'     => $n[0] ?? '',
                '{n2}'     => $n[1] ?? '',
                '{n3}'     => $n[2] ?? '',
            ]);
        }

        return null;
    }
}
