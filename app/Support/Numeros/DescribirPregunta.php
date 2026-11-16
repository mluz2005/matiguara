<?php

namespace App\Support\Numeros;

/**
 * Pasa una pregunta de «Mati y los números» a texto corto en castellano, para que la docente
 * pueda ver QUÉ se le preguntó al niño, qué respondió y cuál era la respuesta correcta.
 *
 * Recibe la pregunta tal como se guarda en detalle_numeros.partes_json (con datos, opciones y correcta).
 */
final class DescribirPregunta
{
    /** Enunciado de la pregunta. */
    public static function enunciado(array $item): string
    {
        $d = $item['datos'] ?? [];

        return match ($item['tipo'] ?? '') {
            'toque'           => 'Tocar la luciérnaga',
            'num_a_cantidad'  => 'Número ' . ($d['numero'] ?? '?') . ': elegir las ramitas que lo muestran',
            'cantidad_a_num'  => 'Ramitas de ' . ($d['numero'] ?? '?') . ': elegir el número',
            'mas_puntos'      => '¿Dónde hay más puntos? (' . ($d['izq'] ?? '?') . ' a la izquierda y ' . ($d['der'] ?? '?') . ' a la derecha)',
            'mayor'           => '¿Cuál es más grande? ' . ($d['izq'] ?? '?') . ' o ' . ($d['der'] ?? '?'),
            'puntos_rapidos'  => 'Puntos muy rápidos: se mostraron ' . ($d['n'] ?? '?'),
            'linea'           => 'Línea del 0 al ' . ($d['max'] ?? '?') . ': ubicar el ' . ($d['objetivo'] ?? '?'),
            'signo'           => 'Falta el signo: ' . ($d['a'] ?? '?') . ' _ ' . ($d['b'] ?? '?') . ' = ' . ($d['c'] ?? '?'),
            'cuenta_palillos' => 'Cuenta con ramitas: ' . ($d['a'] ?? '?') . ' ' . (($d['op'] ?? '+') === '-' ? '−' : '+') . ' ' . ($d['b'] ?? '?'),
            'secuencia'       => 'Ordenar: ' . implode(', ', array_map(fn ($x) => $x ?? '_', $d['seq'] ?? [])),
            default           => 'Pregunta',
        };
    }

    /** Lo que el niño respondió (o la respuesta correcta), dicho en palabras. */
    public static function respuesta(array $item, string $valor): string
    {
        $d = $item['datos'] ?? [];

        return match ($item['tipo'] ?? '') {
            'toque'          => 'tocó',
            'num_a_cantidad' => 'ramitas de ' . $valor,
            'mas_puntos'     => $valor === 'izq' ? 'izquierda' : ($valor === 'der' ? 'derecha' : $valor),
            'mayor'          => $valor === 'izq' ? (string) ($d['izq'] ?? $valor) : ($valor === 'der' ? (string) ($d['der'] ?? $valor) : $valor),
            default          => $valor,
        };
    }
}
