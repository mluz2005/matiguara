<?php

namespace App\Support;

use App\Models\ContenidoNivel;
use App\Models\SesionJuego;

/**
 * SOPORTE: FrutasJuego
 * =========================================================================
 * Arma la "escena de frutas" que se muestra en el juego Suma de Frutas:
 * en lugar de "3 + 5", el niño ve 3 naranjas y 5 naranjas, y contesta
 * "¿Cuántas naranjas hay en total?". Para restas ve un grupo de frutas
 * con algunas tachadas: "Si se sacan 3, ¿cuántas quedarían?".
 *
 * IMPORTANTE — la base de datos NO guarda frutas: cada ejercicio sigue
 * siendo solo "valor_a  operador  valor_b = respuesta". La fruta se elige
 * acá al mostrar el ejercicio, de forma estable (la misma fruta si el niño
 * actualiza la página) pero distinta entre ejercicios y entre partidas.
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ GUARANÍ A VALIDAR: todos los textos en guaraní de este archivo       │
 * │ (nombres de frutas y frases de las preguntas) son un BORRADOR que    │
 * │ debe revisar una persona hablante nativa. Se corrigen SOLO acá,      │
 * │ en FRUTAS y en FRASES_GN, sin tocar nada más del sistema.            │
 * └──────────────────────────────────────────────────────────────────────┘
 * =========================================================================
 */
class FrutasJuego
{
    /** Hasta esta cantidad por grupo se dibujan frutas; más que eso, solo números. */
    public const MAX_FRUTAS_POR_GRUPO = 10;

    /**
     * Catálogo de frutas, elegidas por ser comunes en la mesa y el patio de
     * una familia paraguaya (mango, coco, guayaba, banana/pakova, naranja,
     * sandía, piña, limón, palta, manzana). Se evitaron uva, frutilla y
     * durazno por ser menos cotidianas. El mamón y la mandioca también son
     * muy típicos, pero no tienen emoji; si se agregan imágenes propias,
     * se suman acá.
     * IMPORTANTE: mantener 10 frutas (o un número sin divisores comunes con 3),
     * para que las 5 preguntas de una partida lleven frutas distintas.
     *   es / es_pl : nombre en castellano (singular / plural)
     *   genero     : 'f' o 'm' (para "cuántas" / "cuántos")
     *   gn         : nombre en guaraní  ← A VALIDAR
     */
    private const FRUTAS = [
        ['emoji' => '🍎', 'es' => 'manzana',  'es_pl' => 'manzanas',  'genero' => 'f', 'gn' => 'manzana'],
        ['emoji' => '🍌', 'es' => 'banana',   'es_pl' => 'bananas',   'genero' => 'f', 'gn' => 'pakova'],
        ['emoji' => '🍊', 'es' => 'naranja',  'es_pl' => 'naranjas',  'genero' => 'f', 'gn' => 'naranja'],
        ['emoji' => '🍉', 'es' => 'sandía',   'es_pl' => 'sandías',   'genero' => 'f', 'gn' => 'sandía'],
        ['emoji' => '🥭', 'es' => 'mango',    'es_pl' => 'mangos',    'genero' => 'm', 'gn' => 'manga'],
        ['emoji' => '🥥', 'es' => 'coco',     'es_pl' => 'cocos',     'genero' => 'm', 'gn' => 'koko'],
        ['emoji' => '🍍', 'es' => 'piña',     'es_pl' => 'piñas',     'genero' => 'f', 'gn' => 'piña'],
        ['emoji' => '🍐', 'es' => 'guayaba',  'es_pl' => 'guayabas',  'genero' => 'f', 'gn' => 'arasa'],
        ['emoji' => '🍋', 'es' => 'limón',    'es_pl' => 'limones',   'genero' => 'm', 'gn' => 'limõ'],
        ['emoji' => '🥑', 'es' => 'palta',    'es_pl' => 'paltas',    'genero' => 'f', 'gn' => 'palta'],
    ];

    /**
     * Frases de las preguntas en guaraní  ← A VALIDAR
     *   {fruta} = nombre de la fruta   {a} = cantidad inicial   {b} = cantidad que se saca
     */
    private const FRASES_GN = [
        'suma'  => '¿Mboy {fruta} oĩ opaite?',
        'resta' => 'Oĩ {a} {fruta}. Ojepe\'a {b}. ¿Mboy opyta?',
    ];

    /**
     * Devuelve los datos de la escena, o null si el ejercicio no es una
     * suma/resta con dos números (en ese caso la vista muestra lo de siempre).
     *
     * @param  int $respondidos  Ejercicios ya respondidos en esta partida (para rotar la fruta)
     * @return array{
     *   operacion:string, modo:string, a:int, b:int, fruta:array,
     *   emoji:string, texto_es:string, texto_gn:string, descripcion:string
     * }|null
     */
    public static function escena(ContenidoNivel $ejercicio, SesionJuego $sesion, int $respondidos = 0): ?array
    {
        if ($ejercicio->valor_a === null || $ejercicio->valor_b === null) {
            return null;
        }

        $operacion = match (trim((string) $ejercicio->operador)) {
            '+'             => 'suma',
            '-', '−', '–'   => 'resta',
            default         => null,
        };
        if ($operacion === null) {
            return null;
        }

        $a = (int) $ejercicio->valor_a;
        $b = (int) $ejercicio->valor_b;
        if ($operacion === 'resta' && $b > $a) {
            return null; // nunca mostramos negativos: si hubiera un dato mal cargado, se muestra normal
        }

        // Fruta: arranca en un punto distinto en cada partida (según el número de
        // sesión) y avanza de a 3 en cada ejercicio. Como 3 y 10 no tienen
        // divisores comunes, las 5 preguntas de una partida llevan 5 frutas
        // distintas. Es ESTABLE: si el niño actualiza la página, la fruta no cambia.
        $cantidad = count(self::FRUTAS);
        $fruta = self::FRUTAS[(crc32((string) $sesion->id) + $respondidos * 3) % $cantidad];

        $modo = ($a <= self::MAX_FRUTAS_POR_GRUPO && $b <= self::MAX_FRUTAS_POR_GRUPO)
            ? 'frutas'
            : 'numeros';

        $nombreA = $a === 1 ? $fruta['es'] : $fruta['es_pl'];
        $cuantas = $fruta['genero'] === 'f' ? 'Cuántas' : 'Cuántos';
        $quedarian = $fruta['genero'] === 'f' ? 'cuántas' : 'cuántos';

        if ($operacion === 'suma') {
            $textoEs = "¿{$cuantas} {$fruta['es_pl']} hay en total?";
            $descripcion = "{$a} " . ($a === 1 ? $fruta['es'] : $fruta['es_pl'])
                         . " más {$b} " . ($b === 1 ? $fruta['es'] : $fruta['es_pl']);
        } else {
            $seSacan = $b === 1 ? 'Si se saca 1' : "Si se sacan {$b}";
            $textoEs = "Hay {$a} {$nombreA}. {$seSacan}, ¿{$quedarian} quedarían?";
            $descripcion = "{$a} {$nombreA}, se sacan {$b}";
        }

        $textoGn = strtr(self::FRASES_GN[$operacion], [
            '{fruta}' => $fruta['gn'],
            '{a}'     => $a,
            '{b}'     => $b,
        ]);

        return [
            'operacion'   => $operacion,
            'modo'        => $modo,
            'a'           => $a,
            'b'           => $b,
            'fruta'       => $fruta,
            'emoji'       => $fruta['emoji'],
            'texto_es'    => $textoEs,
            'texto_gn'    => $textoGn,
            'descripcion' => $descripcion,
        ];
    }

    /** Catálogo completo (para documentación / revisión del guaraní). */
    public static function catalogo(): array
    {
        return self::FRUTAS;
    }
}
