<?php

namespace Tests\Unit;

use App\Support\NombrePropio;
use PHPUnit\Framework\TestCase;

class NombrePropioTest extends TestCase
{
    public function test_unifica_mayusculas_minusculas_y_espacios()
    {
        $this->assertSame('María Luz Pérez', NombrePropio::formatear('  MARÍA   luz pérez '));
        $this->assertSame('Ana', NombrePropio::formatear('ANA'));
        $this->assertSame('Ñandú Gómez', NombrePropio::formatear('ñANDÚ gómez'));
    }

    public function test_las_particulas_quedan_en_minuscula_salvo_al_principio()
    {
        $this->assertSame('Juan de la Cruz', NombrePropio::formatear('JUAN DE LA CRUZ'));
        $this->assertSame('De la Cruz', NombrePropio::formatear('de la cruz'));
        $this->assertSame('María de los Ángeles', NombrePropio::formatear('MARÍA DE LOS ÁNGELES'));
    }

    public function test_guiones_y_apostrofos()
    {
        $this->assertSame('Ana-María', NombrePropio::formatear('ana-maría'));
        $this->assertSame("O'Brien", NombrePropio::formatear("o'brien"));
    }

    public function test_texto_vacio()
    {
        $this->assertSame('', NombrePropio::formatear('   '));
        $this->assertSame('', NombrePropio::formatear(null));
    }

    public function test_la_clave_ignora_mayusculas_tildes_y_espacios()
    {
        $this->assertSame(NombrePropio::clave('María  PÉREZ'), NombrePropio::clave('maria perez'));
        $this->assertNotSame(NombrePropio::clave('Ana López'), NombrePropio::clave('Ana Lopera'));
    }
}
