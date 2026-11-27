<?php

namespace Tests\Unit;

use App\Support\TextosGuaraniCompartidos;
use PHPUnit\Framework\TestCase;

/**
 * Textos compartidos (mensajes de ánimo y medallas) revisados por la persona hablante.
 * Se comprueba que la fuente única esté completa y que los seeders usen las mismas claves.
 */
class TextosGuaraniCompartidosTest extends TestCase
{
    private function leer(string $ruta): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $ruta);
    }

    public function test_hay_13_mensajes_y_10_medallas_completos()
    {
        $this->assertCount(13, TextosGuaraniCompartidos::MENSAJES);
        $this->assertCount(10, TextosGuaraniCompartidos::MEDALLAS);

        foreach (TextosGuaraniCompartidos::MENSAJES as $es => $gn) {
            $this->assertNotSame('', trim($gn), $es);
        }
        foreach (TextosGuaraniCompartidos::MEDALLAS as $es => [$nombre, $descripcion]) {
            $this->assertNotSame('', trim($nombre), $es);
            $this->assertNotSame('', trim($descripcion), $es);
        }
    }

    public function test_cada_mensaje_del_seeder_tiene_su_guarani_revisado()
    {
        $seeder = $this->leer('database/seeders/MensajesRetroSeeder.php');
        $this->assertSame(13, preg_match_all("/'texto_es' =>/", $seeder));

        foreach (TextosGuaraniCompartidos::MENSAJES as $es => $gn) {
            $this->assertStringContainsString("'texto_es' => '" . addcslashes($es, "'") . "'", $seeder, 'texto_es: ' . $es);
            $this->assertStringContainsString("'texto_gn' => '" . addcslashes($gn, "'") . "'", $seeder, 'texto_gn: ' . $gn);
        }
    }

    public function test_cada_medalla_del_seeder_tiene_el_nombre_revisado()
    {
        $seeder = $this->leer('database/seeders/CatalogoLogrosSeeder.php');

        foreach (TextosGuaraniCompartidos::MEDALLAS as $es => [$nombre]) {
            if ($es === 'Flash Master') {
                continue; // la crea la migración 2026_10_12_000006
            }
            $this->assertStringContainsString("'nombre_es' => '" . addcslashes($es, "'") . "'", $seeder, $es);
            $this->assertStringContainsString("'nombre_gn' => '" . addcslashes($nombre, "'") . "'", $seeder, $nombre);
        }

        $flash = $this->leer('database/migrations/2026_10_12_000006_crear_medalla_flash_master.php');
        $this->assertStringContainsString("'nombre_gn'      => '" . TextosGuaraniCompartidos::MEDALLAS['Flash Master'][0] . "'", $flash);
    }

    public function test_los_helpers_devuelven_lo_revisado_o_el_valor_actual()
    {
        $this->assertSame('Yva', TextosGuaraniCompartidos::mensaje('no existe', 'Yva'));
        $this->assertNull(TextosGuaraniCompartidos::medalla('no existe'));
        $this->assertSame('Papapy Mbo\'ehára', TextosGuaraniCompartidos::medalla('Maestro del Cálculo')[0]);
    }
}
