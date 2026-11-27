<?php

namespace Tests\Unit;

use App\Support\AyudaJuegos;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de la ayuda de Flash Matemático (FM-AYU-1..3).
 */
class AyudaFlashGuaraniTest extends TestCase
{
    public function test_la_ayuda_de_flash_lleva_el_guarani_revisado()
    {
        $gn = array_column(AyudaJuegos::pasosDe('Flash Matemático'), 'gn');

        $this->assertSame([
            '¡Emaña umi papapýre!',
            "Okañy hikuái. ¡Eñemandu'a!",
            'Epoko mbohováire.',
        ], $gn);
    }
}
