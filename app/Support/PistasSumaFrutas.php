<?php

namespace App\Support;

use App\Models\ContenidoNivel;

/**
 * SOPORTE: PistasSumaFrutas
 * =========================================================================
 * Arma la PISTA que ve el niño cuando falla su primer intento en Suma de
 * Frutas y se le vuelve a mostrar el mismo ejercicio (segunda oportunidad).
 * La pista enseña una estrategia —no regala la respuesta—:
 *
 *   • Números hasta 10 ......... contar hacia adelante (suma) o quitar de a uno (resta).
 *   • Sin llevada/préstamo ..... sumar o restar «por partes» (decenas y unidades).
 *   • Suma con llevada ......... juntar las unidades y «llevar» una decena.
 *   • Resta con préstamo ....... «pedir prestada» una decena cuando falta.
 *
 * No se guarda nada en la base: la pista se calcula a partir de los números
 * del ejercicio, así que es estable si el niño actualiza la página.
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ GUARANÍ A VALIDAR: las frases en guaraní (campo 'gn' de cada pista)  │
 * │ son un BORRADOR para que las revise una persona hablante nativa.     │
 * │ Se corrigen SOLO en este archivo, en los métodos de abajo.           │
 * └──────────────────────────────────────────────────────────────────────┘
 * =========================================================================
 */
class PistasSumaFrutas
{
    private const LUGARES      = ['unidades', 'decenas', 'centenas'];
    private const LUGAR_SIGUIENTE = ['decena', 'centena', 'unidad de mil'];

    /**
     * @return array{tipo:string, es:string, gn:string}|null
     *         null si el ejercicio no es una suma o resta de dos números.
     */
    public static function para(ContenidoNivel $ejercicio): ?array
    {
        if ($ejercicio->valor_a === null || $ejercicio->valor_b === null) {
            return null;
        }

        $a  = (int) $ejercicio->valor_a;
        $b  = (int) $ejercicio->valor_b;
        $op = trim((string) $ejercicio->operador);

        if ($op === '+') {
            return self::pistaSuma($a, $b);
        }

        if (in_array($op, ['-', '−', '–'], true) && $a >= $b) {
            return self::pistaResta($a, $b);
        }

        return null;
    }

    // ── Suma ─────────────────────────────────────────────────────────────

    private static function pistaSuma(int $a, int $b): array
    {
        if ($a <= 10 && $b <= 10) {
            $serie = self::serieHaciaAdelante($a, $b);
            $sufijo = $serie === '' ? '.' : ": {$serie}";
            return [
                'tipo' => 'contar',
                'es'   => "Empezá en {$a} y seguí contando {$b} más{$sufijo}",
                'gn'   => "Eñepyrũ {$a} guive ha eipapa {$b} ambue{$sufijo}",
            ];
        }

        // Primera columna (de derecha a izquierda) donde se lleva
        $columna = self::columnaConLlevada($a, $b);
        if ($columna !== null) {
            $da = self::digito($a, $columna);
            $db = self::digito($b, $columna);
            $lugar = self::LUGARES[$columna] ?? 'unidades';
            $sig   = self::LUGAR_SIGUIENTE[$columna] ?? 'decena';
            $suma  = $da + $db;
            $resto = $suma - 10;

            return [
                'tipo' => 'llevada',
                'es'   => "Primero las {$lugar}: {$da} + {$db} = {$suma}. Son 1 {$sig} y {$resto} {$lugar}: ¡llevá la {$sig}!",
                'gn'   => "{$lugar}: {$da} + {$db} = {$suma}. Peteĩ {$sig} ha {$resto} {$lugar}.",
            ];
        }

        $partes = self::partes($a, $b, '+');
        if (!str_contains($partes, ' y ')) {
            return [
                'tipo' => 'partes',
                'es'   => "Pensalo así: {$partes}. Mirá cuántas decenas o centenas son.",
                'gn'   => "Embojuaju: {$partes}.",
            ];
        }
        return [
            'tipo' => 'partes',
            'es'   => "Sumá por partes: {$partes}. Después juntá los resultados.",
            'gn'   => "Embojuaju: {$partes}. Upéi embojuaju opaite.",
        ];
    }

    // ── Resta ────────────────────────────────────────────────────────────

    private static function pistaResta(int $a, int $b): array
    {
        if ($a <= 10 && $b <= 10) {
            return [
                'tipo' => 'quitar',
                'es'   => "Empezá en {$a} y contá {$b} para atrás, de a uno.",
                'gn'   => "Eñepyrũ {$a} guive ha eipe'a {$b}, peteĩ-peteĩ.",
            ];
        }

        $columna = self::columnaConPrestamo($a, $b);
        if ($columna !== null) {
            $da = self::digito($a, $columna);
            $db = self::digito($b, $columna);
            $lugar = self::LUGARES[$columna] ?? 'unidades';
            $sig   = self::LUGAR_SIGUIENTE[$columna] ?? 'decena';
            $tengo = $da + 10;

            // Si la columna de la izquierda tiene un 0 (p. ej. 205 − 38) no hay nada que prestar ahí:
            // se pide más a la izquierda. Se da una pista general, sin números que puedan confundir.
            if (self::digito($a, $columna + 1) === 0) {
                return [
                    'tipo' => 'prestamo',
                    'es'   => "En las {$lugar}, {$da} es menor que {$db}: hay que pedir prestado a la columna de la izquierda. Si ahí hay un 0, pedí a la que sigue.",
                    'gn'   => "{$lugar}: {$da} ha {$db}. Eipe'a {$db} {$tengo}-gui.",
                ];
            }

            return [
                'tipo' => 'prestamo',
                'es'   => "En las {$lugar}, {$da} es menor que {$db}: pedí prestada 1 {$sig}. Ahora tenés {$tengo} {$lugar} y restás {$db}.",
                'gn'   => "{$lugar}: {$tengo} − {$db}. Peteĩ {$sig} oñemoĩ umi {$lugar}-pe.",
            ];
        }

        $partes = self::partes($a, $b, '-');
        if (!str_contains($partes, ' y ')) {
            return [
                'tipo' => 'partes',
                'es'   => "Pensalo así: {$partes}. Mirá cuántas decenas o centenas quedan.",
                'gn'   => "Eipe'a: {$partes}.",
            ];
        }
        return [
            'tipo' => 'partes',
            'es'   => "Restá por partes: {$partes}. Después juntá los resultados.",
            'gn'   => "Eipe'a: {$partes}. Upéi embojuaju opaite.",
        ];
    }

    // ── Auxiliares ───────────────────────────────────────────────────────

    /**
     * Los primeros números de la cuenta hacia adelante, SIN llegar al resultado:
     * nunca más de b − 1 números ("5, 6, …"); con b = 1 no se muestra ninguno.
     */
    private static function serieHaciaAdelante(int $a, int $b): string
    {
        $mostrar = min(2, max($b - 1, 0));
        if ($mostrar === 0) {
            return '';
        }

        $numeros = [];
        for ($i = 1; $i <= $mostrar; $i++) {
            $numeros[] = $a + $i;
        }

        return implode(', ', $numeros) . ', …';
    }

    /** Dígito de la columna $columna (0 = unidades, 1 = decenas, 2 = centenas…). */
    private static function digito(int $n, int $columna): int
    {
        return intdiv($n, 10 ** $columna) % 10;
    }

    private static function columnaConLlevada(int $a, int $b): ?int
    {
        for ($c = 0; $c < 4; $c++) {
            if (self::digito($a, $c) + self::digito($b, $c) >= 10) {
                return $c;
            }
        }
        return null;
    }

    private static function columnaConPrestamo(int $a, int $b): ?int
    {
        for ($c = 0; $c < 4; $c++) {
            if (self::digito($a, $c) < self::digito($b, $c)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Cuentas parciales por columna, de izquierda a derecha, sin ceros
     * inútiles: "20 + 30 y 4 + 5" (suma) o "80 − 30 y 7 − 2" (resta).
     */
    private static function partes(int $a, int $b, string $op): string
    {
        $signo  = $op === '+' ? '+' : '−';
        $partes = [];

        for ($c = 3; $c >= 0; $c--) {
            $da = self::digito($a, $c) * (10 ** $c);
            $db = self::digito($b, $c) * (10 ** $c);

            if ($da === 0 && $db === 0) {
                continue;
            }
            $partes[] = "{$da} {$signo} {$db}";
        }

        return count($partes) > 1
            ? implode(', ', array_slice($partes, 0, -1)) . ' y ' . end($partes)
            : ($partes[0] ?? '');
    }
}
