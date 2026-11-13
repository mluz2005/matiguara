<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Formato único para nombres y apellidos de personas.
 *
 *   «  MARÍA   luz pérez » → «María Luz Pérez»
 *   «JUAN DE LA CRUZ»      → «Juan de la Cruz»
 *   «ana-maría o'brien»    → «Ana-María O'Brien»
 *
 * Se aplica al CREAR y al EDITAR, para que la lista no quede mezclada entre
 * MAYÚSCULAS, minúsculas y «Capitalizado». Las tildes y la «ñ» no se tocan.
 *
 * clave() sirve para comparar nombres sin importar mayúsculas, tildes ni
 * espacios de más (detectar posibles duplicados).
 */
class NombrePropio
{
    /** Palabras que van en minúscula salvo al principio («Juan de la Cruz»). */
    private const PARTICULAS = ['de', 'del', 'la', 'las', 'los', 'y', 'e', 'da', 'das', 'do', 'dos', 'di', 'van', 'von'];

    public static function formatear(?string $texto): string
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));
        if ($texto === '') {
            return '';
        }

        $palabras = explode(' ', mb_strtolower($texto, 'UTF-8'));
        foreach ($palabras as $i => $palabra) {
            if ($i > 0 && in_array($palabra, self::PARTICULAS, true)) {
                continue;
            }
            // Mayúscula al empezar la palabra y después de guion o apóstrofo.
            $palabras[$i] = (string) preg_replace_callback(
                '/(^|[-\'’])(\p{L})/u',
                fn (array $m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
                $palabra
            );
        }

        return implode(' ', $palabras);
    }

    /** Clave de comparación: minúsculas, sin tildes y con un solo espacio entre palabras. */
    public static function clave(?string $texto): string
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));

        return Str::lower(Str::ascii($texto));
    }
}
