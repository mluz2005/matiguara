<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Fondo de selva: reemplaza el papel tapiz de emojis (arco iris, manzana…).
 * Verifica que la imagen y la hoja de estilos existan y que las pantallas la carguen.
 */
class FondoSelvaTest extends TestCase
{
    public function test_la_imagen_y_la_hoja_de_estilos_existen()
    {
        $this->assertFileExists(public_path('img/fondo/selva.webp'));
        $this->assertFileExists(public_path('img/fondo/selva-vertical.webp'));
        $css = file_get_contents(public_path('css/fondo-selva.css'));
        $this->assertStringContainsString('../img/fondo/selva.webp', $css);
        $this->assertStringContainsString('../img/fondo/selva-vertical.webp', $css);
    }

    public function test_inicio_y_los_ingresos_cargan_el_fondo()
    {
        foreach (['/', '/login', '/familias', '/acceso'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('css/fondo-selva.css', false);
        }
    }
}
