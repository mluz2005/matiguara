<?php

namespace Tests\Unit;

use App\Support\TarjetasGuarani;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de las tarjetas de «Encuentra el par» (EP-OBJ-01..10, EP-NUM-0..9).
 */
class TarjetasGuaraniTest extends TestCase
{
    private function seeder(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/database/seeders/MemoriaJuegoSeeder.php');
    }

    public function test_hay_10_objetos_y_10_numeros()
    {
        $this->assertCount(10, TarjetasGuarani::OBJETOS);
        $this->assertCount(10, TarjetasGuarani::NUMEROS);
    }

    public function test_cada_numero_lleva_su_numeral_en_guarani()
    {
        $numerales = ['mba\'eve', 'Peteĩ', 'Mokõi', 'Mbohapy', 'Irundy', 'Po', 'Poteĩ', 'Pokõi', 'Poapy', 'Porundy'];
        foreach ($numerales as $n => $numeral) {
            $this->assertSame("Papapy {$n} ({$numeral})", TarjetasGuarani::NUMEROS[(string) $n]);
        }
    }

    public function test_el_seeder_usa_los_mismos_textos()
    {
        $seeder = $this->seeder();

        foreach (TarjetasGuarani::OBJETOS as $valor => $gn) {
            $this->assertMatchesRegularExpression(
                "/'valor' => '" . preg_quote($valor, '/') . "',\\s*'es' => '[^']*',\\s*'gn' => '" . preg_quote(addcslashes($gn, "'"), '/') . "'/u",
                $seeder, "objeto {$valor}"
            );
        }
        foreach (TarjetasGuarani::NUMEROS as $valor => $gn) {
            $this->assertStringContainsString(
                "['valor' => '{$valor}', 'gn' => '" . addcslashes($gn, "'") . "']", $seeder, "número {$valor}"
            );
        }
    }

    public function test_el_seeder_lleva_el_nombre_y_las_instrucciones_revisadas()
    {
        $this->assertStringContainsString("'nombre_gn'        => 'Ejuhu iñirũ ojoguáva'", $this->seeder());
        $this->assertStringContainsString("Embojere mokõi kuatia'atã ha ejuhu umi ojoguáva opa mboyve aravo.", $this->seeder());
    }

    public function test_nombre_devuelve_lo_revisado_o_null()
    {
        $this->assertSame('Haiha syry', TarjetasGuarani::nombre('objeto', 'lapicera'));
        $this->assertSame('Papapy 5 (Po)', TarjetasGuarani::nombre('numero', '5'));
        $this->assertNull(TarjetasGuarani::nombre('objeto', 'no existe'));
        $this->assertNull(TarjetasGuarani::nombre('otro', '1'));
    }

    public function test_los_textos_de_pantalla_del_juego_estan_aplicados()
    {
        $js = file_get_contents(dirname(__DIR__, 2) . '/public/js/memoria-v2.js');
        foreach ([
            "¡Emaña porã umi kuatia'atãre!", '¡Ñañembosarái!', '¡Iporãite pe joapy! ×', "¡Haimete! Eha'ã jey",
            '¡Rejupi yvatekuépe!', "Ñaha'ãve michĩmi", '¡Rejuhupaite umi ojoguáva!', '¡Opáma aravo!',
            'Roñongatu hína osẽva ndéve', "Ndaikatúi roñongatu ko'ágã", '¡Regana peteĩ jeguaka pyahu!',
        ] as $texto) {
            $this->assertStringContainsString($texto, $js, $texto);
        }
        // Textos del borrador anterior que ya no deben estar.
        foreach (['Jaikuaapaite', 'Jaha ñembosarái', 'Iporãiterei!'] as $viejo) {
            $this->assertStringNotContainsString($viejo, $js, $viejo);
        }

        foreach (['memoria-tablero.blade.php', 'memoria-tablero-v2.blade.php'] as $vista) {
            $blade = file_get_contents(dirname(__DIR__, 2) . '/resources/views/estudiante/' . $vista);
            $this->assertStringContainsString("¡Mba'éichapa, {{", $blade, $vista);
            $this->assertStringContainsString('⏱ Tiempo · Aravo', $blade, $vista);
            $this->assertStringContainsString("Embojere mokõi kuatia'atã ha ejuhu umi ojoguáva.", $blade, $vista);
            $this->assertStringContainsString("'gn' => 'Ñanduti'", $blade, $vista);
        }
    }
}
