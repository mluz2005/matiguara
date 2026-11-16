<?php

namespace Tests\Unit;

use App\Support\Cumpleanos as C;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/** Fecha de nacimiento opcional de los niños y cuándo Mati los saluda. */
class CumpleanosTest extends TestCase
{
    private function hoy(): Carbon
    {
        return Carbon::create(2026, 11, 15)->startOfDay();
    }

    public function test_los_tres_campos_vacios_significan_sin_fecha_y_sin_error()
    {
        $this->assertSame([null, null], C::desdeFormulario(null, null, null, $this->hoy()));
        $this->assertSame([null, null], C::desdeFormulario('', '', '', $this->hoy()));
    }

    public function test_si_falta_uno_de_los_tres_se_avisa()
    {
        foreach ([[5, null, 2019], [5, 3, ''], ['', 3, 2019]] as [$d, $m, $a]) {
            [$fecha, $error] = C::desdeFormulario($d, $m, $a, $this->hoy());
            $this->assertNull($fecha);
            $this->assertNotNull($error);
        }
    }

    public function test_una_fecha_valida_se_arma_bien()
    {
        [$fecha, $error] = C::desdeFormulario(5, 3, 2019, $this->hoy());
        $this->assertNull($error);
        $this->assertSame('2019-03-05', $fecha->toDateString());
    }

    public function test_fechas_que_no_existen_se_rechazan()
    {
        foreach ([[31, 4, 2019], [29, 2, 2019], ['a', 3, 2019], ['-5', 3, 2019]] as [$d, $m, $a]) {
            [$fecha, $error] = C::desdeFormulario($d, $m, $a, $this->hoy());
            $this->assertNull($fecha, "$d/$m/$a");
            $this->assertNotNull($error);
        }

        [$fecha, $error] = C::desdeFormulario(29, 2, 2020, $this->hoy());
        $this->assertNull($error, '2020 es bisiesto');
    }

    public function test_no_se_acepta_una_fecha_futura_ni_una_edad_que_no_es_de_primer_ciclo()
    {
        $this->assertNotNull(C::desdeFormulario(20, 11, 2026, $this->hoy())[1], 'futura');
        $this->assertNotNull(C::desdeFormulario(1, 1, 2024, $this->hoy())[1], '2 años');
        $this->assertNotNull(C::desdeFormulario(1, 1, 2000, $this->hoy())[1], 'adulto');
        $this->assertNull(C::desdeFormulario(15, 11, 2022, $this->hoy())[1], '4 años justos');
        $this->assertNotNull(C::desdeFormulario(16, 11, 2022, $this->hoy())[1], 'casi 4 años');
    }

    public function test_es_hoy_incluye_el_29_de_febrero()
    {
        $this->assertTrue(C::esHoy(Carbon::create(2019, 11, 15), $this->hoy()));
        $this->assertFalse(C::esHoy(Carbon::create(2019, 11, 16), $this->hoy()));
        $this->assertFalse(C::esHoy(null, $this->hoy()));

        $bisiesto = Carbon::create(2020, 2, 29);
        $this->assertTrue(C::esHoy($bisiesto, Carbon::create(2026, 2, 28)), 'en año común se festeja el 28');
        $this->assertFalse(C::esHoy($bisiesto, Carbon::create(2026, 3, 1)));
        $this->assertTrue(C::esHoy($bisiesto, Carbon::create(2028, 2, 29)));
        $this->assertFalse(C::esHoy($bisiesto, Carbon::create(2028, 2, 28)));
    }

    public function test_el_saludo_lleva_el_nombre()
    {
        $this->assertSame('¡Feliz cumpleaños, Ana! 🎂', C::saludoEs('Ana'));
        $this->assertStringContainsString('Ana', C::saludoGn('Ana'));
    }
}
