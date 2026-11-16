<?php

namespace App\Support\Numeros;

/**
 * TEXTOS de «Mati y los números»: nombre de cada viaje y de cada parte, y la instrucción
 * que Mati dice antes de empezarla (en castellano y en guaraní).
 *
 * ⚠️ El guaraní es BORRADOR: debe validarlo una persona hablante antes de usarse con niños
 * (RN-07). Las voces se graban aparte y se colocan en public/audio/numeros/ (ver NumerosService::voces()).
 */
final class TextosNumeros
{
    /** Nombre de cada viaje (= nivel del menú). */
    public const VIAJES = [
        1 => ['es' => 'Los símbolos',  'gn' => 'Papapy sï',        'ico' => '🔢'],
        2 => ['es' => 'Las cantidades', 'gn' => 'Hetaha',          'ico' => '🐾'],
        3 => ['es' => 'Los signos',     'gn' => 'Mbohetave ha mbohupa', 'ico' => '➕'],
    ];

    /** parte => título e instrucción */
    public const PARTES = [
        1  => ['es' => 'Calentamiento',          'gn' => 'Ñepyrũ',
               'ies' => '¡Hola! Soy Mati. Tocá la luciérnaga cada vez que aparezca.',
               'ign' => 'Maitei! Che Mati. Epoko pe tambéra ojehechávo.'],
        2  => ['es' => 'Número y palillos',      'gn' => 'Papapy ha ramita',
               'ies' => 'Mirá el número. Tocá el montón de ramitas que tiene esa cantidad.',
               'ign' => 'Ehecha pe papapy. Epoko pe ramita hetáva hague.'],
        3  => ['es' => 'Palillos y número',      'gn' => 'Ramita ha papapy',
               'ies' => 'Mirá las ramitas. Tocá el número que dice cuántas hay.',
               'ign' => 'Ehecha umi ramita. Epoko pe papapy heta hague.'],
        4  => ['es' => '¿Dónde hay más puntos?', 'gn' => 'Moõpa hetave punto',
               'ies' => 'No hace falta contar. Tocá el lado que tiene más puntos.',
               'ign' => 'Ndaikotevẽi ehecha. Epoko pe hetave punto oĩháme.'],
        5  => ['es' => '¿Cuál es más grande?',   'gn' => 'Mávapa tuichavéva',
               'ies' => 'Tocá el número que es más grande.',
               'ign' => 'Epoko pe papapy tuichavéva.'],
        6  => ['es' => 'Puntos muy rápidos',     'gn' => 'Punto pya\'e',
               'ies' => 'Los puntos aparecen un ratito y se van. ¿Cuántos viste? Tocá el número.',
               'ign' => 'Umi punto osẽ sapy\'a. Mboýpa rehecha? Epoko pe papapy.'],
        7  => ['es' => 'Línea de números',       'gn' => 'Papapy rape',
               'ies' => 'Mirá la línea. Tocá el lugar donde va el número.',
               'ign' => 'Ehecha pe rape. Epoko moõpa oho pe papapy.'],
        8  => ['es' => 'Falta un signo',         'gn' => 'Ofalta peteĩ techaukaha',
               'ies' => 'A la cuenta le falta un signo. Tocá el signo que va: más o menos.',
               'ign' => 'Pe cuenta ofalta peteĩ techaukaha. Epoko mávapa oho: más térã menos.'],
        9  => ['es' => 'Cuentas con palillos',  'gn' => 'Cuenta ramita ndive',
               'ies' => 'Mirá las ramitas y resolvé la cuenta. Tocá el resultado.',
               'ign' => 'Ehecha umi ramita ha eresolve pe cuenta. Epoko pe resultado.'],
        10 => ['es' => 'Ordenar números',        'gn' => 'Papapy ñemoĩ',
               'ies' => 'Los números van en orden. ¿Cuál falta? Tocalo.',
               'ign' => 'Umi papapy oho ordénpe. Mávapa ofalta? Epoko.'],
    ];

    /** Figuritas de la selva que se muestran al terminar un viaje (una por viaje, en orden). */
    public const FIGURITAS = [
        ['img' => 'yaguarete', 'es' => 'el yaguareté', 'gn' => 'jaguarete'],
        ['img' => 'carpincho', 'es' => 'el carpincho', 'gn' => "kapi'iváva"],
        ['img' => 'tatu',      'es' => 'el tatú',      'gn' => 'tatu'],
        ['img' => 'nandu',     'es' => 'el ñandú',     'gn' => 'ñandu'],
        ['img' => 'yacare',    'es' => 'el yacaré',    'gn' => 'jakare'],
        ['img' => 'tuka',      'es' => 'el tucán',     'gn' => 'tukã'],
        ['img' => 'mborevi',   'es' => 'el tapir',     'gn' => 'mborevi'],
        ['img' => 'mainumby',  'es' => 'el colibrí',   'gn' => 'mainumby'],
        ['img' => 'karumbe',   'es' => 'la tortuga',   'gn' => 'karumbe'],
    ];

    /** Figurita que corresponde a un viaje de un ciclo (se van recorriendo las nueve). */
    public static function figurita(int $ciclo, int $viaje): array
    {
        $i = (($ciclo - 1) * 3 + ($viaje - 1)) % count(self::FIGURITAS);
        return self::FIGURITAS[$i];
    }
}
