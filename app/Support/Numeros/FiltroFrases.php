<?php

namespace App\Support\Numeros;

/**
 * FILTRO DE FRASES del informe de «Atención numérica» (Etapa 3).
 *
 * Todo texto que redacta la IA pasa por acá ANTES de guardarse o mostrarse. Si aparece algo que
 * suene a diagnóstico, a etiqueta o a alarma, el texto se descarta y se usa la plantilla de
 * respaldo. Es una red de seguridad: el prompt ya le pide a la IA que no lo haga, pero no
 * confiamos solo en eso (RN-02: nada de diagnósticos clínicos).
 *
 * Es una clase PURA (sin base de datos ni framework) para poder probarla fácilmente.
 */
final class FiltroFrases
{
    public const LARGO_MIN = 40;
    public const LARGO_MAX = 1800;

    /** Frases que SÍ están permitidas aunque contengan una palabra vigilada («no es un diagnóstico»). */
    private const PERMITIDAS = [
        'no es un diagnostico', 'no es diagnostico', 'no es ningun diagnostico', 'no constituye un diagnostico',
        'no se trata de un diagnostico', 'no reemplaza un diagnostico', 'no reemplaza ningun diagnostico',
        'no se puede diagnosticar', 'no es posible diagnosticar', 'no diagnostica', 'no sirve para diagnosticar',
        'no es una evaluacion', 'ni un diagnostico', 'ni diagnostico',
    ];

    /** Patrones (sobre texto en minúsculas y sin tildes) → motivo. Etiquetas clínicas, estigmas y alarma. */
    private const PROHIBIDAS = [
        '/discalcul/'                                    => 'etiqueta clínica',
        '/dislexi/'                                      => 'etiqueta clínica',
        '/disgrafi|disortograf|dispraxi/'                => 'etiqueta clínica',
        '/\btdah?\b|\btda\b|hiperactiv/'                 => 'etiqueta clínica',
        '/autis|asperger|\bteaa?\b/'                     => 'etiqueta clínica',
        '/trastorno|sindrome|patolog|enfermedad|discapacidad|deficit|deficien/' => 'término clínico',
        '/diagnostic/'                                   => 'habla de diagnóstico',
        '/psicolog|psicopedag|neurolog|psiquiatr|pediatr|\bmedic[oa]s?\b|terapi|tratamiento|medicacion/' => 'deriva o receta',
        '/deriv(ar|ac|e\b)|especialista|consulta medica/' => 'deriva',
        '/problemas? de aprendizaje|dificultades? de aprendizaje|necesidades educativas/' => 'etiqueta',
        '/\b(tiene|tienen|padece|sufre|presenta)\b[^.]{0,30}\b(problema|dificultad|trastorno)/' => 'afirma que «tiene» algo',
        '/\b(sufre|sufren|padece|padecen)\b/'          => 'afirma que «tiene» algo',
        '/retras|retard|atrasad|anormal|incapaz|no puede aprender|no va a poder|no sabe nada/' => 'estigma',
        '/\b(lent[oa]s?|torp[ea]s?|flojo|floja|vago|vaga|tonto|tonta|burro|burra)\b/' => 'estigma',
        '/urgente|alarma|alarmante|preocupante|\bgrave|\bgravedad|peligro|riesgo|fracaso|crisis|emergencia|inquietante/' => 'alarma',
        '/muy por debajo|muy mal|desastros|catastrof/'   => 'alarma',
    ];

    /** Solo para el texto de la familia: nada de colores, porcentajes ni «semáforo». */
    private const FAMILIA_PROHIBIDAS = [
        '/semaforo|\brojo\b|\bamarillo\b|\bverde\b|\bnaranja\b/' => 'habla del semáforo',
        '/%|por ciento|porcentaje/'                              => 'cifras',
        '/puntaje|puntos? de corte|\bcorte\b/'                   => 'cifras',
    ];

    /**
     * Problemas encontrados en un texto. Lista vacía = el texto sirve.
     *
     * @param  bool $paraFamilia  aplica además las reglas del texto que va a la familia
     * @return string[]
     */
    public static function problemas(string $texto, bool $paraFamilia = false): array
    {
        $problemas = [];
        $largo = mb_strlen(trim($texto));
        if ($largo < self::LARGO_MIN) {
            $problemas[] = 'texto demasiado corto';
        }
        if ($largo > self::LARGO_MAX) {
            $problemas[] = 'texto demasiado largo';
        }

        // Marcadores raros: solo se permite {nombre}; nada de enlaces, correos ni teléfonos.
        $sinNombre = str_replace('{nombre}', '', $texto);
        if (preg_match('/[{}\[\]<>]/', $sinNombre)) {
            $problemas[] = 'marcadores o etiquetas no permitidos';
        }
        if (preg_match('~https?://|www\.|@|\d{6,}~i', $texto)) {
            $problemas[] = 'enlaces, correos o teléfonos';
        }

        $limpio = self::normalizar($texto);
        foreach (self::PERMITIDAS as $ok) {
            $limpio = str_replace($ok, ' ', $limpio);
        }

        $reglas = self::PROHIBIDAS + ($paraFamilia ? self::FAMILIA_PROHIBIDAS : []);
        foreach ($reglas as $patron => $motivo) {
            if (preg_match($patron, $limpio)) {
                $problemas[] = $motivo;
            }
        }

        return array_values(array_unique($problemas));
    }

    public static function esValido(string $texto, bool $paraFamilia = false): bool
    {
        return self::problemas($texto, $paraFamilia) === [];
    }

    /** Minúsculas, sin tildes y con los espacios normalizados. */
    public static function normalizar(string $texto): string
    {
        $t = mb_strtolower($texto, 'UTF-8');
        $t = strtr($t, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            '«' => '"', '»' => '"', '“' => '"', '”' => '"', '’' => "'", '–' => '-', '—' => '-', '−' => '-',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $t));
    }
}
