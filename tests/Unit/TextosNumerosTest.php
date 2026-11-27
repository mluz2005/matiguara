<?php

namespace Tests\Unit;

use App\Support\AyudaJuegos;
use App\Support\Numeros\TextosNumeros as T;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de «Mati y los números» (planilla Guarani_MatiYLosNumeros_revisado,
 * IDs MN-JUE, MN-UI, MN-PRE, MN-AYU, MN-VIA, MN-PAR, MN-FIG).
 */
class TextosNumerosTest extends TestCase
{
    private function leer(string $ruta): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $ruta);
    }

    public function test_nombre_e_instrucciones_del_juego()
    {
        $this->assertSame('Mati ha papapykuéra', T::NOMBRE_GN);
        $this->assertSame(
            "Eguata Mati ndive ka'aguýre. Peteĩteĩ tendápe oĩ papapy ha hetakue: epoko mbohovái ndéve iporãvéva. ¡Ndaipóri kyta ni jopói, ñembosarái añónte!",
            T::INSTRUCCIONES_GN
        );
    }

    public function test_los_tres_viajes()
    {
        $this->assertSame(
            ['Umi techaukaha', 'Umi hetakue', "Umi mbojuaju ha jepe'a rechaukaha"],
            array_column(T::VIAJES, 'gn')
        );
    }

    public function test_titulos_de_las_diez_partes()
    {
        $this->assertSame([
            "Ñembosako'i", "Papapy ha yvyra'i", "Yvyra'i ha papapy", '¿Moõpa oĩ hetave kyta?', '¿Mávapa tuichave?',
            "Kyta pya'eterei", 'Papapy tysýi', 'Ofalta peteĩ techaukaha', "Papapy apo yvyra'i ndive", 'Ñemohenda papapykuéra',
        ], array_column(T::PARTES, 'gn'));
    }

    public function test_instrucciones_de_mati_de_las_diez_partes()
    {
        $this->assertCount(10, T::PARTES);
        $this->assertSame("¡Mba'éichapa! Che ha'e Mati. Epoko muãre ojehecha jave.", T::PARTES[1]['ign']);
        $this->assertSame("Emaña papapýre. Epoko yvyra'i atýre oguerekóva upe hetakue.", T::PARTES[2]['ign']);
        $this->assertSame('Natekotevẽi reipapa. Epoko pe yke oguerekóva hetave kyta.', T::PARTES[4]['ign']);
        $this->assertSame("Umi kyta ojehecha sapy'ami ha okañy. ¿Mboýpa rehecha? Epoko papapýre.", T::PARTES[6]['ign']);
        $this->assertSame("Papapy apópe ofalta peteĩ techaukaha. Epoko techaukaha oúvare: mbojuaju térã jepe'a.", T::PARTES[8]['ign']);
        $this->assertSame('Umi papapy oho hendápe porã. ¿Mávapa ofalta? Epoko hese.', T::PARTES[10]['ign']);
    }

    public function test_figuritas()
    {
        $this->assertSame(
            ['jaguarete', "kapi'yva", 'tatu', 'ñandu', 'jakare', 'tukã', 'mborevi', 'mainumby', 'karumbe'],
            array_column(T::FIGURITAS, 'gn')
        );
    }

    public function test_pantallas_y_preguntas_llevan_es_y_gn()
    {
        foreach (T::UI as $k => $t) {
            $this->assertNotSame('', $t['es'], "UI $k es");
            $this->assertNotSame('', $t['gn'], "UI $k gn");
        }
        foreach (T::PREGUNTAS as $k => $t) {
            $this->assertNotSame('', $t['es'], "PRE $k es");
            $this->assertNotSame('', $t['gn'], "PRE $k gn");
        }
        $this->assertSame('¡Epoko tesapépe!', T::PREGUNTAS['luz']['gn']);
        $this->assertSame("¿Mba'e techaukahápa ofalta?", T::PREGUNTAS['signo']['gn']);
        $this->assertSame("¡Ñapytu'u!", T::UI['descTitulo']['gn']);
    }

    public function test_los_marcadores_se_rellenan_en_cada_lengua()
    {
        $v = T::VIAJES[2];
        $this->assertSame(
            "Ko árape jaha jeguatápe ka'aguýre: Umi hetakue. Epoko pe ndéve iporãvéva. ¡Ndaipóri kyta!",
            T::rellenar(T::UI['intro']['gn'], ['viaje' => $v['gn']])
        );
        $this->assertSame(
            'Hoy vamos de viaje por la selva: Las cantidades. Tocá lo que te parezca. ¡No hay puntos!',
            T::rellenar(T::UI['intro']['es'], ['viaje' => $v['es']])
        );
        $this->assertSame("Reraha peteĩ ta'anga ka'aguypegua: tatu.", T::rellenar(T::UI['finTexto']['gn'], ['figurita' => 'tatu']));
        $this->assertSame('🦜 Jere 2 · Ko árape oĩ: Umi hetakue', T::rellenar(T::UI['ciclo']['gn'], ['n' => 2, 'viaje' => 'Umi hetakue']));
    }

    public function test_para_js_lleva_ui_y_preguntas()
    {
        $this->assertSame(['ui', 'pre'], array_keys(T::paraJs()));
    }

    public function test_la_ayuda_lleva_el_guarani_revisado()
    {
        $gn = array_column(AyudaJuegos::pasosDe('Mati y los números'), 'gn');
        $this->assertSame([
            "Eguata Mati ndive ka'aguýre ha emaña umi papapýre.",
            'Epoko mbohovái ndéve iporãvéva. Ndaipóri kyta.',
            'Ejapo ikatúva. ¡Iporã ndereikuaáiramo peteĩ!',
            "Opa vove jeguata reraha peteĩ ta'anga ka'aguypegua.",
        ], $gn);
    }

    public function test_numeros_js_no_trae_guarani_escrito_a_mano_y_usa_los_textos_del_servidor()
    {
        $js = $this->leer('public/js/numeros.js');
        foreach (["Maitei", "Aguyje", "Ereraha", "Opa ko árape", "Epoko ndépe"] as $viejo) {
            $this->assertStringNotContainsString($viejo, $js, $viejo);
        }
        $this->assertStringContainsString('C.tx', $js);
        $this->assertStringContainsString("U('vamos')", $js);
        $this->assertStringContainsString("P('luz')", $js);
    }

    public function test_el_controlador_manda_los_textos_al_js()
    {
        $this->assertStringContainsString("'tx'          => TextosNumeros::paraJs()", $this->leer('app/Http/Controllers/Dashboard/NumerosController.php'));
    }

    public function test_la_migracion_y_el_seeder_usan_la_misma_fuente()
    {
        $this->assertStringContainsString('TextosNumeros::NOMBRE_GN', $this->leer('database/seeders/MatiNumerosSeeder.php'));
        $this->assertStringContainsString('TextosNumeros::INSTRUCCIONES_GN', $this->leer('database/seeders/MatiNumerosSeeder.php'));
        $this->assertStringContainsString('TextosNumeros::NOMBRE_GN', $this->leer('database/migrations/2026_11_19_000006_aplicar_guarani_revisado_mati_y_los_numeros.php'));
    }
}
