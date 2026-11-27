<?php

namespace Tests\Unit;

use App\Support\AyudaJuegos;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de la ayuda de «Encuentra el par» (EP-AYU-1..4).
 */
class AyudaMemoriaGuaraniTest extends TestCase
{
    public function test_la_ayuda_de_encuentra_el_par_lleva_el_guarani_revisado()
    {
        $gn = array_column(AyudaJuegos::pasosDe('Encuentra el par'), 'gn');

        $this->assertSame([
            "Embojere mokõi kuatia'atã.",
            'Ojojogua ramo, ¡opyta!',
            "Peteĩteĩ mokõi ojoguáva rejuhúva ome'ẽ ndéve hetave aravo.",
            'Ejuhupaite umi ojoguáva opa mboyve aravo.',
        ], $gn);
    }
}
