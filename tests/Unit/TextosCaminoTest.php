<?php

namespace Tests\Unit;

use App\Support\TextosCamino;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de «El camino a la escuela» (planilla Guarani_ElCaminoALaEscuela_revisado).
 */
class TextosCaminoTest extends TestCase
{
    private function leer(string $ruta): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $ruta);
    }

    private function banco(): array
    {
        return json_decode($this->leer('database/seeders/data/laberinto_ejercicios.json'), true);
    }

    public function test_las_8_historias_se_arman_desde_el_castellano()
    {
        $this->assertSame(
            "Don Pedro oguereko 7 mandi'o. Omono'õ 1 ve. ¿Mboy mandi'o piko oguereko ko'ágã?",
            TextosCamino::enunciado('Don Pedro tiene 7 mandiocas. Cosecha 1 más. ¿Cuántas mandiocas tiene ahora?')
        );
        $this->assertSame(
            "Tito oipirakutu 96 pira. Ohejajey 6 ysyry'ípe. ¿Mboy pira piko opyta chupe?",
            TextosCamino::enunciado('Tito pescó 96 peces. Devuelve 6 al arroyo. ¿Cuántos peces le quedan?')
        );
        $this->assertSame(
            "Korápe oĩ 12 vaka. Oike 5 vaka ve. ¿Mboy vaka piko oĩ ko'ágã?",
            TextosCamino::enunciado('En el corral hay 12 vacas. Entran 5 vacas más. ¿Cuántas vacas hay ahora?')
        );
        $this->assertSame(
            "Korápe oĩ 14 vaka. Osẽ 6 vaka kapi'ipépe. ¿Mboy vaka piko opyta korápe?",
            TextosCamino::enunciado('En el corral hay 14 vacas. Salen 6 vacas al pasto. ¿Cuántas vacas quedan en el corral?')
        );
        $this->assertSame(
            "Korápe oĩ 9 vaka. Ñemuha guasurã oñeikotevẽ 15. ¿Mboy vaka piko ofalta?",
            TextosCamino::enunciado('En el corral hay 9 vacas. Para la feria hacen falta 15. ¿Cuántas vacas faltan?')
        );
        $this->assertSame(
            "Don Ramón oguereko 620 chipa. Ohepyme'ẽ 514. Upéi ombojy 227 ve. ¿Mboy chipa piko oguereko ko'ágã?",
            TextosCamino::enunciado('Don Ramón tiene 620 chipas. Vende 514. Después hornea 227 más. ¿Cuántas chipas tiene ahora?')
        );
        $this->assertSame(
            "Mabel oguereko 40 chipa. Ombojy 12 ve. Upéi ohepyme'ẽ 30. ¿Mboy chipa piko oguereko ko'ágã?",
            TextosCamino::enunciado('Mabel tiene 40 chipas. Hornea 12 más. Después vende 30. ¿Cuántas chipas tiene ahora?')
        );
        $this->assertSame(
            "Tía Nena oguereko 5 chipa. Upéi ombojy 6 ve. Peteĩ joguahára oipota 20. ¿Mboy chipa piko ofalta chupe?",
            TextosCamino::enunciado('Tía Nena tiene 5 chipas. Después hornea 6 más. Un cliente quiere 20. ¿Cuántas chipas le faltan?')
        );
    }

    public function test_el_singular_en_castellano_tambien_se_reconoce()
    {
        $this->assertNotNull(TextosCamino::enunciado('Tito tiene 1 mandioca. Cosecha 3 más. ¿Cuántas mandiocas tiene ahora?'));
        $this->assertNotNull(TextosCamino::enunciado('En el corral hay 1 vaca. Entran 4 vacas más. ¿Cuántas vacas hay ahora?'));
        $this->assertNotNull(TextosCamino::enunciado('Mabel tiene 1 chipa. Después hornea 6 más. Un cliente quiere 9. ¿Cuántas chipas le faltan?'));
    }

    public function test_un_enunciado_desconocido_no_se_toca()
    {
        $this->assertNull(TextosCamino::enunciado('Otro problema cualquiera.'));
        $this->assertNull(TextosCamino::enunciado('Alguien tiene 3 mandiocas. Cosecha 1 más. ¿Cuántas mandiocas tiene ahora?'));
    }

    public function test_el_banco_de_los_738_problemas_lleva_el_guarani_revisado()
    {
        $banco = $this->banco();
        $this->assertCount(738, $banco);

        foreach ($banco as $i => $f) {
            $esperado = TextosCamino::enunciado($f['enunciado_es']);
            $this->assertNotNull($esperado, "Fila {$i}: " . $f['enunciado_es']);
            $this->assertSame($esperado, $f['enunciado_gn'], "Fila {$i}");

            // Ningún resto del borrador anterior.
            foreach (['ambue', 'Ombyaty', 'Corral-pe', 'oikotevẽ gueteri', 'Mboy opyta'] as $viejo) {
                $this->assertStringNotContainsString($viejo, $f['enunciado_gn'], "Fila {$i}: {$viejo}");
            }
            // Las cifras del castellano aparecen, en el mismo orden, en el guaraní.
            preg_match_all('/\d+/', $f['enunciado_es'], $es);
            preg_match_all('/\d+/', $f['enunciado_gn'], $gn);
            $this->assertSame($es[0], $gn[0], "Fila {$i}: las cifras no coinciden");
        }
    }

    public function test_las_cuatro_medallas_y_las_instrucciones()
    {
        $this->assertCount(4, TextosCamino::MEDALLAS);
        $this->assertSame('Tape iporãitereíva', TextosCamino::MEDALLAS['Camino perfecto'][0]);
        $this->assertSame("Tape mbo'ehára", TextosCamino::MEDALLAS['Maestro del camino'][0]);
        $this->assertStringContainsString('¡Embyaty chipa tape po\'ípe!', TextosCamino::INSTRUCCIONES_GN);

        $seeder = $this->leer('database/seeders/CaminoEscuelaSeeder.php');
        $this->assertStringContainsString('TextosCamino::INSTRUCCIONES_GN', $seeder);
        foreach (array_keys(TextosCamino::MEDALLAS) as $nombre) {
            $this->assertStringContainsString("TextosCamino::MEDALLAS['{$nombre}'][0]", $seeder, $nombre);
        }
    }

    public function test_el_javascript_del_juego_lleva_los_textos_revisados()
    {
        $js = $this->leer('public/js/laberinto-v2.js');

        foreach ([
            // mensajes de Mati
            "¡Mba\\'éichapa! Oĩ jokoha tapépe.", 'Epoko peteĩ tenda tapépe ha jaguata oñondive.', "¡Reguahẽma mbo\\'ehaópe! ¡Oñembopu itapu!",
            // pistas y soluciones
            "Embojuaju raẽ 10 peve ha upéi embojuaju pe hembýva.", 'Embojuaju peteĩha peteĩha ndive, paha paha ndive',
            "Umi peteĩhápe noĩmbáiramo, ejerure peteĩ paha oñemboja'óva.", 'Ejapo mokõi jehasápe. Peteĩha:',
            'Ñambyaty raẽ:', 'Hemby ndéve', "Ajerure 1 ha ajapo", 'Osẽva:', 'Jahecha porã:', "Ahai ",
            // interfaz
            'Péicha oñemyengovia', 'Pytyvõrã', 'Reñembosarái', 'Roñongatu hína nde rape…', "¡Tape iporãitereíva!",
            'Ndaikatúi roñongatu. Ehecha ne ñanduti.', "Kokue ha mandi'oty", 'Yvyramáta ho\\\'áva',
        ] as $texto) {
            $this->assertStringContainsString($texto, $js, $texto);
        }

        // Restos del borrador anterior
        foreach (['Embojoaju', 'Ñande rape henyhẽ', "Oĩ mba\\'e ojokóva ñande rape", 'Ñemyesakã: \'+eq.join(\' · \')}'] as $viejo) {
            $this->assertStringNotContainsString($viejo, $js, $viejo);
        }
    }

    public function test_la_vista_lleva_los_textos_revisados()
    {
        $blade = $this->leer('resources/views/estudiante/laberinto-tablero.blade.php');

        foreach ([
            "Héẽ, aiporúta pe hu'y", 'Nahániri, aháta che año', '🧭 ¿Moõrupi?', '💡 Pytyvõrã', "🔢 Ta'anga", '🔊 Ehendu', 'Eseguí ➜',
            "Eha'ã jey", "Jaha ñapytu'u", '¡Opa ko ára pegua ñembosarái!', "Roñongatu nde rape. Epytu'u sapy'ami ha emaña mombyry.",
            'Kyta', 'id="arr-gn"',
        ] as $texto) {
            $this->assertStringContainsString($texto, $blade, $texto);
        }

        $resumen = $this->leer('resources/views/estudiante/resumen.blade.php');
        $this->assertStringContainsString("¡Tape iporãitereíva!", $resumen);
        $this->assertStringContainsString("¡Reguahẽma mbo'ehaópe!", $resumen);
    }
}
