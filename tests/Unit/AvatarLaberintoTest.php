<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\LaberintoController;
use PHPUnit\Framework\TestCase;

/**
 * «El camino a la escuela»: el personaje del niño no debe tener sexo, porque el sistema no lo pide
 * y el dibujo no puede contradecir el nombre (nena con dibujo de nene, o al revés).
 */
class AvatarLaberintoTest extends TestCase
{
    public function test_los_personajes_son_ninos_neutros_sin_sexo()
    {
        $this->assertNotEmpty(LaberintoController::AVATARES);

        foreach (LaberintoController::AVATARES as $avatar) {
            $this->assertStringStartsWith("\u{1F9D2}", $avatar, 'Debe ser el niño neutro (🧒), no una nena ni un nene');
            $this->assertStringNotContainsString("\u{1F467}", $avatar, 'No debe ser una nena (👧)');
            $this->assertStringNotContainsString("\u{1F466}", $avatar, 'No debe ser un nene (👦)');
        }
    }

    public function test_hay_variedad_de_tonos_de_piel()
    {
        $this->assertGreaterThanOrEqual(3, count(array_unique(LaberintoController::AVATARES)));
    }
}
