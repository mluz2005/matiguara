<?php

namespace Tests\Unit;

use App\Support\TiempoFlash;
use PHPUnit\Framework\TestCase;

class TiempoFlashTest extends TestCase
{
    public function test_cada_unidad_suma_un_segundo()
    {
        $this->assertSame(8000, TiempoFlash::ms(8000, 0));
        $this->assertSame(13000, TiempoFlash::ms(8000, 5));
        $this->assertSame(32000, TiempoFlash::ms(12000, 20));
        $this->assertSame(13, TiempoFlash::segundos(8000, 5));
    }

    public function test_un_valor_negativo_acorta_pero_nunca_baja_de_dos_segundos()
    {
        $this->assertSame(3000, TiempoFlash::ms(8000, -5));
        $this->assertSame(2000, TiempoFlash::ms(6000, -5));
        $this->assertSame(2000, TiempoFlash::ms(4000, -5));
    }

    public function test_el_ajuste_se_limita_al_rango_permitido()
    {
        $this->assertSame(20, TiempoFlash::acotar(99));
        $this->assertSame(-5, TiempoFlash::acotar(-99));
        $this->assertSame(28000, TiempoFlash::ms(8000, 99));
    }

    public function test_un_nivel_sin_tiempo_de_flash_no_se_modifica()
    {
        $this->assertSame(0, TiempoFlash::ms(null, 10));
        $this->assertSame(0, TiempoFlash::ms(0, 10));
        $this->assertSame(0, TiempoFlash::ms('', 10));
    }

    public function test_acepta_el_valor_de_la_base_de_datos_como_texto()
    {
        $this->assertSame(10000, TiempoFlash::ms('8000', 2));
    }
}
