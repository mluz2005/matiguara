<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pantalla de inicio: la mascota es un botón que muestra la información
 * institucional; el enlace 'Acerca de' vive dentro de esa burbuja.
 */
class PantallaInicioTest extends TestCase
{
    public function test_inicio_muestra_mascota_boton_y_los_tres_perfiles(): void
    {
        $r = $this->get('/');

        $r->assertOk();
        $r->assertSee('id="mascotaBtn"', false);
        $r->assertSee('aria-expanded="false"', false);
        $r->assertSee('Matemáticas en castellano y guaraní');
        $r->assertSee('Soy estudiante');
        $r->assertSee('Soy docente');
        $r->assertSee('Soy padre/madre');
        $r->assertSee('¡Tocame para conocerme!');
        $r->assertSee('data-perfil', false);
    }

    public function test_la_burbuja_oculta_lleva_los_datos_institucionales_y_acerca_de(): void
    {
        $r = $this->get('/');

        $r->assertSee('id="burbujaInfo"', false);
        $r->assertSee('Primer Ciclo EEB');
        $r->assertSee('Universidad Autónoma de Encarnación 2026');
        $r->assertSee(route('acerca'), false);
    }

    /** Íconos de la mascota y manifiesto: lo que Chrome usa al crear el acceso directo. */
    public function test_las_pantallas_publicas_declaran_iconos_y_manifiesto(): void
    {
        foreach (['/', '/acceso', '/login', '/familias'] as $ruta) {
            $r = $this->get($ruta);
            if ($r->status() !== 200) {
                continue; // alguna ruta puede redirigir según la sesión
            }
            $r->assertSee('rel="manifest"', false);
            $r->assertSee('icons/icon-192.png', false);
            $r->assertSee('icons/apple-touch-icon.png', false);
        }
    }

    public function test_los_archivos_de_iconos_existen_y_el_manifiesto_es_valido(): void
    {
        foreach (['favicon.ico', 'apple-touch-icon.png', 'icons/icon-192.png', 'icons/icon-512.png',
                  'icons/icon-maskable-512.png', 'icons/favicon-32.png'] as $f) {
            $this->assertFileExists(public_path($f));
            $this->assertGreaterThan(500, filesize(public_path($f)), "$f está vacío");
        }

        $m = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertSame('MatiGuarã', $m['name']);
        $this->assertSame('standalone', $m['display']);
        $tamanos = array_column($m['icons'], 'sizes');
        $this->assertContains('192x192', $tamanos);
        $this->assertContains('512x512', $tamanos);
    }
}
