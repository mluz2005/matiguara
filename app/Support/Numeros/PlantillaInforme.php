<?php

namespace App\Support\Numeros;

/**
 * PLANTILLA DE RESPALDO del informe de «Atención numérica» (Etapa 3).
 *
 * Se usa cuando la IA no responde, responde algo que no se puede leer o escribe una frase que el
 * filtro no permite. Redacta con los mismos datos (semáforo, áreas, señales) un informe sencillo,
 * sin diagnósticos, en el mismo formato: lectura, actividades para el aula y texto para la familia.
 *
 * Clase PURA (sin base de datos): se prueba con arreglos. El texto de la familia lleva el
 * marcador {nombre}, que se reemplaza recién al mostrarlo.
 */
final class PlantillaInforme
{
    private const AREA_SIMPLE = [
        'numeros_cantidades' => 'reconocer los números y relacionarlos con cantidades',
        'sentido_cantidad'   => 'calcular a ojo cuánto hay y comparar cantidades',
        'signos_calculo'     => 'elegir si hay que sumar o restar y resolver cuentas chiquitas',
    ];

    private const ACTIVIDADES_AULA = [
        'numeros_cantidades' => [
            'Contar objetos reales (semillas, tapitas, palitos) y unir cada cantidad con su número escrito.',
            'Con tarjetas del 0 al 10, ordenarlas de menor a mayor y decir en voz alta cuál va antes y cuál después.',
        ],
        'sentido_cantidad' => [
            'Mostrar dos montoncitos y preguntar cuál tiene más antes de contar; después contar juntos para comprobar.',
            'Dibujar una cinta numérica en el piso y pedir que ubique un número (por ejemplo el 7) caminando hasta ahí.',
        ],
        'signos_calculo' => [
            'Con fichas o palillos, agregar y sacar, y decir en voz alta si se hizo una suma o una resta.',
            'Contar pequeños cuentos («llegaron dos más», «se fueron tres») y escribir la cuenta de lo que pasó.',
        ],
    ];

    private const ACTIVIDADES_CASA = [
        'numeros_cantidades' => 'contar juntos objetos de la casa (cucharas, tapitas) y buscar números en los precios o en la calle',
        'sentido_cantidad'   => 'mostrarle dos montoncitos de semillas y preguntarle cuál tiene más antes de contar',
        'signos_calculo'     => 'jugar con fichas a agregar y sacar, diciendo en voz alta «ahora hay más» o «ahora hay menos»',
    ];

    /**
     * @param  array $resultado  salida de TamizajeService::evaluar/calcular
     * @return array{lectura:string, actividades:string, texto_familia:string}
     */
    public static function generar(array $resultado): array
    {
        $areasAtencion = self::areasConAtencion($resultado);

        return [
            'lectura'       => self::lectura($resultado, $areasAtencion),
            'actividades'   => self::actividades($resultado, $areasAtencion),
            'texto_familia' => self::familia($areasAtencion),
        ];
    }

    /** Áreas que no están en verde, de la más baja a la más alta. */
    private static function areasConAtencion(array $resultado): array
    {
        $lista = [];
        foreach (($resultado['areas'] ?? []) as $clave => $a) {
            if (isset(TextosTamizaje::AREAS[$clave]) && ($a['pct'] ?? null) !== null && ($a['color'] ?? 'verde') !== 'verde') {
                $lista[$clave] = (int) $a['pct'];
            }
        }
        asort($lista);

        return array_keys($lista);
    }

    private static function lectura(array $r, array $atencion): string
    {
        $color = $r['semaforo'] ?? null;
        $partes = [];

        $partes[] = match ($color) {
            'rojo'     => 'En varias áreas los aciertos de los últimos ciclos quedaron por debajo de lo esperado. Conviene mirar con más atención cómo trabaja con los números y, si lo ves oportuno, conversar con la familia.',
            'amarillo' => 'En algunos aspectos los aciertos de los últimos ciclos quedaron algo por debajo de lo esperado. Conviene mirar con más atención cómo trabaja con los números.',
            default    => 'En los últimos ciclos no se ven señales claras. Seguí observando como siempre.',
        };

        $detalle = [];
        foreach (TextosTamizaje::AREAS as $clave => $nombre) {
            $pct = $r['areas'][$clave]['pct'] ?? null;
            if ($pct !== null) {
                $detalle[] = "«{$nombre}»: {$pct} % de aciertos";
            }
        }
        if ($detalle) {
            $partes[] = 'Por área: ' . implode('; ', $detalle) . '.';
        }

        if ($atencion) {
            $nombres = array_map(fn ($c) => '«' . TextosTamizaje::AREAS[$c] . '»', array_slice($atencion, 0, 2));
            $partes[] = 'Donde más conviene mirar: ' . implode(' y ', $nombres) . ' (' . self::ayudaCorta($atencion[0]) . ').';
        }

        foreach (($r['senales'] ?? []) as $s) {
            if (isset(TextosTamizaje::SENALES[$s])) {
                $partes[] = 'Señal extra: ' . mb_strtolower(TextosTamizaje::SENALES[$s], 'UTF-8') . '.';
            }
        }

        $partes[] = 'Esto no es un diagnóstico: un resultado puede deberse al cansancio, a la distracción o a un mal día. Lo que vos observás en el aula pesa más que este informe.';

        return implode(' ', $partes);
    }

    private static function ayudaCorta(string $area): string
    {
        return self::AREA_SIMPLE[$area] ?? '';
    }

    private static function actividades(array $r, array $atencion): string
    {
        if (!$atencion) {
            return "1) Seguí con las actividades habituales de números.\n2) Cuando quieras, proponé desafíos un poco más difíciles para que siga disfrutando.";
        }

        $ideas = [];
        foreach (array_slice($atencion, 0, 2) as $area) {
            foreach (self::ACTIVIDADES_AULA[$area] as $idea) {
                $ideas[] = $idea;
            }
        }
        if (in_array('cero', $r['senales'] ?? [], true)) {
            $ideas[] = 'Trabajar el cero con cajitas vacías: «no hay ninguno» también es una cantidad.';
        }
        $ideas = array_slice($ideas, 0, 3);

        $lineas = [];
        foreach ($ideas as $i => $idea) {
            $lineas[] = ($i + 1) . ') ' . $idea;
        }

        return implode("\n", $lineas);
    }

    private static function familia(array $atencion): string
    {
        $abre = 'Hola, familia de {nombre}. Les escribo para compartirles que {nombre} está jugando en la escuela a «Mati y los números», un juego que nos ayuda a ver cómo se lleva con los números.';

        if (!$atencion) {
            return $abre . ' Hasta ahora viene trabajando bien. Los invito a seguir acompañándolo con juegos simples en casa, como contar cosas juntos. Cualquier duda, me pueden consultar cuando quieran. Un saludo cordial.';
        }

        $area  = $atencion[0];
        $cuesta = self::AREA_SIMPLE[$area];
        $casa   = self::ACTIVIDADES_CASA[$area];

        return $abre . " Notamos que a veces le cuesta un poco {$cuesta}. Es algo frecuente a esta edad y se puede acompañar con juegos cortos en casa, por ejemplo: {$casa}. "
            . 'Esto no es un diagnóstico ni una evaluación: solo queremos acompañarlo mejor entre todos. Los invito a conversar cuando les quede cómodo. Un saludo cordial.';
    }
}
