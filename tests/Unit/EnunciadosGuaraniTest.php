<?php

namespace Tests\Unit;

use App\Support\EnunciadosGuarani;
use PHPUnit\Framework\TestCase;

/**
 * Enunciados en guaraní de Suma de Frutas (planilla revisada, IDs SF-EJB / SF-EJG).
 */
class EnunciadosGuaraniTest extends TestCase
{
    public function test_los_15_ejercicios_base_llevan_los_numeros_en_guarani_con_ha()
    {
        $this->assertCount(15, EnunciadosGuarani::SUMA_FRUTAS_BASE);

        $this->assertSame("¿Mboy'e pa mokõi ha mbohapy?", EnunciadosGuarani::sumaFrutas(1, 1, 2, '+', 3));
        $this->assertSame("¿Mboy'e pa pokõi ha mbohapy?", EnunciadosGuarani::sumaFrutas(1, 2, 7, '+', 3));
        $this->assertSame("¿Mboy'e pa porundy ha irundy?", EnunciadosGuarani::sumaFrutas(1, 3, 9, '+', 4));
        $this->assertSame("¿Mboy'e pa poteĩ ha poapy?", EnunciadosGuarani::sumaFrutas(1, 3, 6, '+', 8));
    }

    public function test_ya_no_queda_la_palabra_mas_en_castellano()
    {
        foreach (EnunciadosGuarani::SUMA_FRUTAS_BASE as $clave => $texto) {
            $this->assertStringNotContainsString(' más ', $texto, $clave);
            $this->assertMatchesRegularExpression("/^¿Mboy'e pa \S+ ha \S+\?$/u", $texto, $clave);
        }
    }

    public function test_cada_base_usa_los_numerales_que_corresponden_a_sus_cifras()
    {
        foreach (EnunciadosGuarani::SUMA_FRUTAS_BASE as $clave => $texto) {
            [, $a, $b] = array_map('intval', explode('|', $clave));
            $esperado = "¿Mboy'e pa " . EnunciadosGuarani::NUMERALES[$a] . ' ha ' . EnunciadosGuarani::NUMERALES[$b] . '?';
            $this->assertSame($esperado, $texto, "Ejercicio {$clave}");
        }
    }

    public function test_los_demas_ejercicios_usan_cifras()
    {
        $this->assertSame("¿Mboy'e pa 12 ha 5?", EnunciadosGuarani::plantilla(12, '+', 5));
        $this->assertSame("¿Mboy'e pa 15 ha 8 jepe'apy?", EnunciadosGuarani::plantilla(15, '-', 8));
        $this->assertSame("¿Mboy'e pa 15 ha 8 jepe'apy?", EnunciadosGuarani::plantilla(15, '−', 8));
    }

    public function test_un_ejercicio_nuevo_de_1_grado_no_usa_la_tabla_de_base()
    {
        // 1 + 1 se agregó después (migración 2026_10_11): lleva cifras, no numerales.
        $this->assertSame("¿Mboy'e pa 1 ha 1?", EnunciadosGuarani::sumaFrutas(1, 1, 1, '+', 1));
        // Una resta del nivel 4 nunca toma un texto de suma.
        $this->assertSame("¿Mboy'e pa 5 ha 2 jepe'apy?", EnunciadosGuarani::sumaFrutas(1, 4, 5, '-', 2));
        // La misma suma en 2.° grado lleva cifras aunque coincida con una de 1.°.
        $this->assertSame("¿Mboy'e pa 2 ha 3?", EnunciadosGuarani::sumaFrutas(2, 1, 2, '+', 3));
    }

    // ── Flash Matemático (FM-EJB-01..15) ────────────────────────────────

    public function test_flash_tiene_15_ejercicios_base_con_ha_y_numerales_correctos()
    {
        $this->assertCount(15, EnunciadosGuarani::FLASH_BASE);

        foreach (EnunciadosGuarani::FLASH_BASE as $clave => $texto) {
            [$nivel, $a, $b] = array_map('intval', explode('|', $clave));
            $this->assertStringNotContainsString(' más ', $texto, $clave);

            $cuenta = EnunciadosGuarani::NUMERALES[$a] . ' ha ' . EnunciadosGuarani::NUMERALES[$b] . '.';
            $inicio = [1 => '¡Emaña! ', 2 => "¡Pya'e! ", 3 => '¡Flash! '][$nivel];
            $this->assertStringStartsWith($inicio . $cuenta, $texto, "Flash {$clave}");
        }
    }

    public function test_flash_ejemplos_exactos_de_la_planilla()
    {
        $this->assertSame("¡Emaña! mbohapy ha irundy. ¡Eñemandu'a hese!", EnunciadosGuarani::flashBase(1, 3, 4));
        $this->assertSame("¡Pya'e! poteĩ ha irundy.", EnunciadosGuarani::flashBase(2, 6, 4));
        $this->assertSame('¡Flash! porundy ha mbohapy.', EnunciadosGuarani::flashBase(3, 9, 3));
        $this->assertSame('¡Flash! porundy ha po.', EnunciadosGuarani::flashBase(3, 9, 5));
        $this->assertNull(EnunciadosGuarani::flashBase(1, 9, 9));
    }
}
