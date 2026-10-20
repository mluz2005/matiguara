<?php

namespace App\Support;

/**
 * AYUDA PARA NIÑOS — "¿Cómo se juega?"
 * =========================================================================
 * Todos los textos de la ayuda están ACÁ, en un solo archivo, para poder
 * corregirlos sin tocar nada más (igual que App\Support\FrutasJuego).
 *
 * ⚠️ GUARANÍ = BORRADOR. Todas las frases en guaraní (campo 'gn') deben ser
 * validadas por una persona hablante antes de usarse con niños.
 *
 * Cada paso:
 *   clave  → nombre de los archivos de audio de ese paso:
 *              public/audio/ayuda/es/{clave}.mp3   (opcional: voz grabada en castellano;
 *                                                   si no existe, se usa la voz del dispositivo)
 *              public/audio/ayuda/gn/{clave}.mp3   (guaraní; el botón 🔊 Guaraní solo aparece si existe)
 *   dibujo → piezas que se dibujan en fila. Un texto entre corchetes es una
 *            "tarjeta": [9]; con asterisco queda resaltada: [11*].
 *   es/gn  → frase corta en castellano / guaraní.
 * =========================================================================
 */
class AyudaJuegos
{
    /** Pasos de cada juego, por su nombre en castellano (juegos.nombre_es). */
    public static function pasosDe(string $nombreJuego): array
    {
        return match ($nombreJuego) {
            'Suma de Frutas' => [
                ['clave' => 'suma_1',  'dibujo' => ['🍎🍎🍎', '➕', '🍎🍎'],
                 'es' => '¡Mirá las frutas!',                      'gn' => '¡Ehecha umi yva!'],
                ['clave' => 'suma_2',  'dibujo' => ['👆', '🍎🍎🍎🍎🍎'],
                 'es' => 'Contalas todas.',                        'gn' => '¡Eipapa opa!'],
                ['clave' => 'suma_3',  'dibujo' => ['🍎🍎🍎🍎', '❌❌'],
                 'es' => 'Las frutas con ❌ se sacan.',            'gn' => 'Umi ❌ ojepe\'a.'],
                ['clave' => 'suma_4',  'dibujo' => ['[3]', '[4]', '[5*]', '👆'],
                 'es' => 'Tocá la respuesta correcta.',            'gn' => 'Eiporavo pe mbohovái iporãva.'],
            ],
            'Flash Matemático' => [
                ['clave' => 'flash_1', 'dibujo' => ['👀', '7 + 4'],
                 'es' => '¡Mirá los números!',                     'gn' => '¡Ehecha umi papapy!'],
                ['clave' => 'flash_2', 'dibujo' => ['🙈', '? + ?'],
                 'es' => 'Se esconden. ¡Acordate!',                'gn' => 'Oñeñomi. ¡Eñemandu\'a!'],
                ['clave' => 'flash_3', 'dibujo' => ['[9]', '[11*]', '[13]', '👆'],
                 'es' => 'Tocá la respuesta.',                     'gn' => 'Eiporavo pe mbohovái.'],
            ],
            'Encuentra el par' => [
                ['clave' => 'memoria_1', 'dibujo' => ['🃏', '🃏', '👆'],
                 'es' => 'Dá vuelta dos cartas.',                  'gn' => 'Embojere mokõi kuatia.'],
                ['clave' => 'memoria_2', 'dibujo' => ['🍎', '=', '🍎', '✅'],
                 'es' => 'Si son iguales, ¡se quedan!',            'gn' => 'Ojojogua ramo, ¡opyta!'],
                ['clave' => 'memoria_3', 'dibujo' => ['✅', '⏱', '➕'],
                 'es' => 'Cada par que encontrás te da más tiempo.', 'gn' => 'Ejuhu vove peteĩ par, ⏱ hetave.'],
                ['clave' => 'memoria_4', 'dibujo' => ['🃏🃏🃏', '⏱', '🏁'],
                 'es' => 'Encontrá todos los pares antes de que se acabe el tiempo.',
                 'gn' => 'Ejuhu opa pe par tiempo opama mboyve.'],
            ],
            default => self::generales(),
        };
    }

    /** Ayuda general: niveles, medallas y botones de sonido. */
    public static function generales(): array
    {
        return [
            ['clave' => 'general_1', 'dibujo' => ['🔒', '➡️', '⭐'],
             'es' => 'Superá un nivel para abrir el siguiente.',
             'gn' => 'Ehasa peteĩ nivel ha ojepe\'áta pe oúva.'],
            ['clave' => 'general_2', 'dibujo' => ['🏅', '⭐', '🏆'],
             'es' => 'Jugá y ganá medallas.',
             'gn' => 'Eñembosarái ha ehupyty medalla.'],
            ['clave' => 'general_3', 'dibujo' => ['🎵', '🔊'],
             'es' => 'Tocá 🎵 y 🔊 para prender o apagar la música y la voz.',
             'gn' => 'Eikutu 🎵 ha 🔊 emyendu térã emomirĩ hag̃ua.'],
        ];
    }

    /**
     * Agrega a cada paso las direcciones de sus audios, SOLO si el archivo existe.
     * (La voz en castellano cae en la del dispositivo si no hay archivo.)
     */
    public static function conAudios(array $pasos): array
    {
        return array_map(function (array $p) {
            $es = 'audio/ayuda/es/' . $p['clave'] . '.mp3';
            $gn = 'audio/ayuda/gn/' . $p['clave'] . '.mp3';
            $p['audio_es'] = file_exists(public_path($es)) ? asset($es) : null;
            $p['audio_gn'] = file_exists(public_path($gn)) ? asset($gn) : null;
            return $p;
        }, $pasos);
    }

    /** Todos los pasos de todos los juegos (para armar el guion de grabación). */
    public static function todos(): array
    {
        $todos = [];
        foreach (['Suma de Frutas', 'Flash Matemático', 'Encuentra el par'] as $j) {
            foreach (self::pasosDe($j) as $p) $todos[] = $p + ['juego' => $j];
        }
        foreach (self::generales() as $p) $todos[] = $p + ['juego' => 'Ayuda general'];
        return $todos;
    }
}
