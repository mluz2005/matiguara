<?php

namespace Tests\Unit;

use App\Support\Numeros\Aleatorio;
use App\Support\Numeros\GeneradorItems as G;
use PHPUnit\Framework\TestCase;

/**
 * «Mati y los números»: el generador de preguntas es una función pura, así que se prueba
 * sin base de datos. Verifica que cada pregunta esté bien armada y que las fijas sean estables.
 */
class GeneradorItemsTest extends TestCase
{
    private const SEMILLAS = [11, 222, 3333, 44444, 555555];

    public function test_el_aleatorio_con_la_misma_semilla_da_siempre_la_misma_secuencia()
    {
        $a = new Aleatorio(123);
        $b = new Aleatorio(123);
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame($a->entre(0, 1000), $b->entre(0, 1000));
        }
        $this->assertNotSame((new Aleatorio(1))->entre(0, 1000000), (new Aleatorio(2))->entre(0, 1000000));
    }

    public function test_los_tres_viajes_tienen_26_24_y_24_preguntas_y_suman_las_62_de_contenido_mas_el_calentamiento()
    {
        $this->assertSame(26, G::totalDelViaje(1));
        $this->assertSame(24, G::totalDelViaje(2));
        $this->assertSame(24, G::totalDelViaje(3));

        $contenido = 0;
        foreach (G::PARTES as $p => $cfg) {
            if ($p > 1) {
                $contenido += $cfg['fijas'] + $cfg['adaptativas'];
            }
        }
        $this->assertSame(62, $contenido);
    }

    public function test_cada_parte_trae_la_cantidad_de_preguntas_con_sus_fijas_y_sin_claves_repetidas()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach (G::PARTES as $parte => $cfg) {
                foreach ([1, 2, 3] as $nivel) {
                    foreach (self::SEMILLAS as $semilla) {
                        $items = G::parte($grado, $parte, 1, $nivel, $semilla);
                        $this->assertCount($cfg['fijas'] + $cfg['adaptativas'], $items);
                        $this->assertCount($cfg['fijas'], array_filter($items, fn ($i) => $i['fija']));
                        $claves = array_column($items, 'clave');
                        $this->assertSame(count($claves), count(array_unique($claves)), "Claves repetidas: grado $grado parte $parte");
                    }
                }
            }
        }
    }

    public function test_toda_parte_con_contenido_tiene_al_menos_una_pregunta_fija_con_cero()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach (G::PARTES as $parte => $cfg) {
                if ($parte === 1 || $parte === 4) {
                    continue;   // calentamiento y «más puntos» (comparar cantidades sin contar) no llevan cero
                }
                foreach ([1, 2, 3] as $ciclo) {
                    $conCero = array_filter(G::fijas($grado, $parte, $ciclo), fn ($i) => $i['cero']);
                    $this->assertNotEmpty($conCero, "Sin cero: grado $grado parte $parte ciclo $ciclo");
                }
            }
        }
    }

    public function test_las_preguntas_fijas_son_iguales_para_todos_y_cambian_de_un_ciclo_al_siguiente()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach (range(2, 10) as $parte) {
                $this->assertSame(G::fijas($grado, $parte, 1), G::fijas($grado, $parte, 1));

                $c1 = array_column(array_filter(G::fijas($grado, $parte, 1), fn ($i) => !$i['cero']), 'clave');
                $c2 = array_column(array_filter(G::fijas($grado, $parte, 2), fn ($i) => !$i['cero']), 'clave');
                $this->assertEmpty(array_intersect($c1, $c2), "Se repite entre ciclos: grado $grado parte $parte");
            }
        }
    }

    public function test_una_misma_semilla_rearma_la_misma_parte_y_otra_semilla_la_mezcla_distinto()
    {
        $this->assertSame(G::parte(2, 5, 1, 2, 777), G::parte(2, 5, 1, 2, 777));
        $this->assertNotSame(
            array_column(G::parte(2, 5, 1, 2, 777), 'clave'),
            array_column(G::parte(2, 5, 1, 2, 778), 'clave')
        );
    }

    public function test_las_opciones_son_tres_distintas_con_la_correcta_y_el_tipo_de_cada_error()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach ([2, 3, 9, 10] as $parte) {
                foreach ([1, 2, 3] as $nivel) {
                    foreach (G::parte($grado, $parte, 1, $nivel, 9876) as $it) {
                        $valores = array_map(fn ($o) => is_array($o) ? $o['valor'] : $o, $it['opciones']);
                        $this->assertCount(3, $valores);
                        $this->assertCount(3, array_unique($valores));
                        $this->assertContains((int) $it['correcta'], $valores);
                        foreach ($valores as $v) {
                            $this->assertGreaterThanOrEqual(0, $v);
                            if ($v !== (int) $it['correcta']) {
                                $this->assertArrayHasKey((string) $v, $it['tipos'], "Falta el tipo de error de $v");
                            }
                        }
                    }
                }
            }
        }
    }

    public function test_las_comparaciones_y_los_signos_tienen_siempre_una_sola_respuesta_correcta()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach ([1, 2, 3] as $nivel) {
                foreach (G::parte($grado, 5, 1, $nivel, 31) as $it) {
                    $this->assertNotSame($it['datos']['izq'], $it['datos']['der']);
                    $this->assertSame($it['datos']['izq'] > $it['datos']['der'] ? 'izq' : 'der', $it['correcta']);
                }
                foreach (G::parte($grado, 4, 1, $nivel, 31) as $it) {
                    $this->assertNotSame($it['datos']['izq'], $it['datos']['der']);
                    $this->assertSame($it['datos']['izq'] > $it['datos']['der'] ? 'izq' : 'der', $it['correcta']);
                }
                foreach (G::parte($grado, 8, 1, $nivel, 31) as $it) {
                    $d = $it['datos'];
                    $this->assertGreaterThanOrEqual(0, $d['c']);
                    $this->assertTrue($it['correcta'] === '+' ? $d['a'] + $d['b'] === $d['c'] : $d['a'] - $d['b'] === $d['c']);
                    $this->assertFalse($d['b'] === 0 && $d['a'] === $d['c'], 'Cuenta con las dos respuestas posibles');
                }
            }
        }
    }

    public function test_las_cuentas_con_palillos_dan_el_resultado_correcto_y_nunca_negativo()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach ([1, 2, 3] as $nivel) {
                foreach (self::SEMILLAS as $semilla) {
                    foreach (G::parte($grado, 9, 1, $nivel, $semilla) as $it) {
                        $d = $it['datos'];
                        $res = $d['op'] === '+' ? $d['a'] + $d['b'] : $d['a'] - $d['b'];
                        $this->assertSame($res, (int) $it['correcta']);
                        $this->assertGreaterThanOrEqual(0, $res);
                    }
                }
            }
        }
    }

    public function test_la_representacion_con_palillos_vuelve_al_mismo_numero()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach ([0, 1, 7, 10, 15, 42, 99, 305, 350, 500, 999] as $n) {
                if ($grado === 1 && $n > 15) {
                    continue;
                }
                if ($grado === 2 && $n > 99) {
                    continue;
                }
                $r = G::representar($grado, $n);
                $this->assertSame($n, $r['c'] * 100 + $r['d'] * 10 + $r['u'], "grado $grado, número $n");
            }
        }
        $this->assertSame(['c' => 0, 'd' => 0, 'u' => 0], G::representar(1, 0), 'El 0 se dibuja como nido vacío');
    }

    public function test_la_linea_de_numeros_y_las_secuencias_estan_bien_armadas()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach ([1, 2, 3] as $nivel) {
                foreach (G::parte($grado, 7, 1, $nivel, 55) as $it) {
                    $this->assertGreaterThanOrEqual(0, $it['datos']['objetivo']);
                    $this->assertLessThanOrEqual($it['datos']['max'], $it['datos']['objetivo']);
                }
                foreach (G::parte($grado, 10, 1, $nivel, 55) as $it) {
                    $huecos = array_filter($it['datos']['seq'], fn ($x) => $x === null);
                    $this->assertCount(1, $huecos);
                }
            }
        }
    }

    public function test_la_demostracion_trae_la_respuesta_y_es_estable()
    {
        foreach ([1, 2, 3] as $grado) {
            foreach (array_keys(G::PARTES) as $parte) {
                $d = G::demostracion($grado, $parte);
                $this->assertSame($parte, $d['parte']);
                $this->assertArrayHasKey('correcta', $d);
                $this->assertSame($d, G::demostracion($grado, $parte));
            }
        }
    }

    public function test_los_puntos_rapidos_se_ven_mas_tiempo_en_los_grados_menores()
    {
        $esperado = [1 => 2000, 2 => 1500, 3 => 1200];

        foreach ($esperado as $grado => $ms) {
            foreach ([1, 2, 3] as $nivel) {
                $items = G::parte($grado, 6, 1, $nivel, 12345);
                $this->assertNotEmpty($items);
                foreach ($items as $item) {
                    $this->assertSame($ms, $item['datos']['ms'], "grado {$grado}, nivel {$nivel}");
                }
            }
        }
    }
}
