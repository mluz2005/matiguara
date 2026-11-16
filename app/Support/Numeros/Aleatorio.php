<?php

namespace App\Support\Numeros;

/**
 * Generador pseudoaleatorio con semilla (xorshift32).
 * -------------------------------------------------------------------------
 * «Mati y los números» necesita que las preguntas FIJAS sean iguales para todos
 * los niños del mismo grado y ciclo, y que las ADAPTATIVAS se puedan rearmar igual
 * si el niño actualiza la página. Por eso no se usa mt_rand/random_int: con la
 * misma semilla esta clase da siempre la misma secuencia, en cualquier PHP.
 */
final class Aleatorio
{
    private int $estado;

    public function __construct(int $semilla)
    {
        $e = $semilla & 0xFFFFFFFF;
        $this->estado = $e === 0 ? 2463534242 : $e;
        // Se descartan unos números para que semillas parecidas no den secuencias parecidas.
        for ($i = 0; $i < 6; $i++) {
            $this->entero();
        }
    }

    /** Semilla estable a partir de un texto (por ejemplo "g2-p5-c3-fija"). */
    public static function deTexto(string $texto): int
    {
        return crc32($texto) & 0xFFFFFFFF;
    }

    /** Entero de 32 bits sin signo. */
    public function entero(): int
    {
        $x = $this->estado;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= ($x >> 17);
        $x ^= ($x << 5) & 0xFFFFFFFF;
        $this->estado = $x & 0xFFFFFFFF;
        return $this->estado;
    }

    /** Entero entre $min y $max, ambos incluidos. */
    public function entre(int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }
        return $min + (int) ($this->entero() % ($max - $min + 1));
    }

    /** Un elemento al azar de la lista. */
    public function elegir(array $lista): mixed
    {
        $lista = array_values($lista);
        return $lista[$this->entre(0, count($lista) - 1)];
    }

    /** Lista mezclada (Fisher-Yates). */
    public function barajar(array $lista): array
    {
        $lista = array_values($lista);
        for ($i = count($lista) - 1; $i > 0; $i--) {
            $j = $this->entre(0, $i);
            [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
        }
        return $lista;
    }

    /** Verdadero con probabilidad ~1/2. */
    public function moneda(): bool
    {
        return ($this->entero() & 1) === 1;
    }
}
