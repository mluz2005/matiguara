<?php

namespace Tests\Unit;

use App\Models\ContenidoNivel;
use App\Support\AyudaJuegos;
use App\Support\PistasSumaFrutas;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní de las pistas (SF-PIS-01..09) y de la ayuda de Suma de Frutas (SF-AYU-1..5).
 */
class PistasSumaFrutasGuaraniTest extends TestCase
{
    private function pista(int $a, string $op, int $b): array
    {
        $e = new ContenidoNivel();
        $e->valor_a = $a;
        $e->operador = $op;
        $e->valor_b = $b;
        return PistasSumaFrutas::para($e);
    }

    public function test_llevada_usa_unidades_y_decena_en_guarani()
    {
        $p = $this->pista(27, '+', 15);
        $this->assertSame('llevada', $p['tipo']);
        $this->assertSame('peteĩnguéra: 7 + 5 = 12. Peteĩ paha ha 2 peteĩnguéra.', $p['gn']);
    }

    public function test_llevada_en_decenas_pasa_a_centena()
    {
        $p = $this->pista(150, '+', 60);
        $this->assertSame('panguéra: 5 + 6 = 11. Peteĩ saha ha 1 panguéra.', $p['gn']);
    }

    public function test_partes_sin_llevada_dice_ehecha_peicha()
    {
        $p = $this->pista(20, '+', 30);
        $this->assertSame('partes', $p['tipo']);
        $this->assertSame('Ehecha péicha: 20 + 30.', $p['gn']);
    }

    public function test_partes_con_varias_cuentas_une_con_ha_y_no_con_y()
    {
        $p = $this->pista(23, '+', 41);
        $this->assertSame('Embojuaju: 20 + 40 ha 3 + 1. Upéi embojuaju opaite.', $p['gn']);
        $this->assertStringNotContainsString(' y ', $p['gn']);
        // El castellano no cambia.
        $this->assertStringContainsString('20 + 40 y 3 + 1', $p['es']);
    }

    public function test_resta_por_partes()
    {
        $this->assertSame('Ehecha péicha: 80 − 30.', $this->pista(80, '-', 30)['gn']);
        $this->assertSame("Eipe'a: 80 − 30 ha 7 − 2. Upéi embojuaju opaite.", $this->pista(87, '-', 32)['gn']);
    }

    public function test_resta_con_prestamo()
    {
        $p = $this->pista(52, '-', 17);
        $this->assertSame('prestamo', $p['tipo']);
        $this->assertSame('peteĩnguéra: 12 − 7. Peteĩ paha oñemoĩ umi peteĩnguéra-pe.', $p['gn']);
    }

    public function test_resta_con_cero_a_la_izquierda()
    {
        $p = $this->pista(205, '-', 38);
        $this->assertSame("peteĩnguéra: 5 ha 8. Eipe'a 8 15-gui.", $p['gn']);
    }

    public function test_contar_y_quitar_no_cambian()
    {
        $this->assertStringStartsWith('Eñepyrũ 5 guive ha eipapa 3 ambue', $this->pista(5, '+', 3)['gn']);
        $this->assertSame("Eñepyrũ 9 guive ha eipe'a 4, peteĩ-peteĩ.", $this->pista(9, '-', 4)['gn']);
    }

    public function test_ninguna_pista_deja_unidades_decenas_centenas_en_castellano()
    {
        $casos = [[27, '+', 15], [150, '+', 60], [52, '-', 17], [205, '-', 38], [20, '+', 30], [800, '+', 700]];
        foreach ($casos as [$a, $op, $b]) {
            $gn = $this->pista($a, $op, $b)['gn'];
            foreach (['unidades', 'decenas', 'centenas', 'decena', 'centena', 'unidad de mil'] as $palabra) {
                $this->assertStringNotContainsString($palabra, $gn, "{$a} {$op} {$b}: «{$palabra}»");
            }
        }
    }

    public function test_ayuda_de_suma_de_frutas_con_el_guarani_revisado()
    {
        $gn = array_column(AyudaJuegos::pasosDe('Suma de Frutas'), 'gn');
        $this->assertSame([
            '¡Ehecha yva!',
            'Eipapa opaite.',
            "Umi yva ❌ rehegua ojepe'a.",
            'Ehetũ mbohovái oĩporãva.',
            'Rejavy ramo, ¡guereko oportunidad pyahu peteĩ pista ndive!',
        ], $gn);
    }

    public function test_ayuda_de_flash_matematico_con_el_guarani_revisado()
    {
        $gn = array_column(AyudaJuegos::pasosDe('Flash Matemático'), 'gn');
        $this->assertSame([
            '¡Emaña umi papapýre!',
            'Okañy hikuái. ¡Eñemandu\'a!',
            'Epoko mbohováire.',
        ], $gn);
    }
}
