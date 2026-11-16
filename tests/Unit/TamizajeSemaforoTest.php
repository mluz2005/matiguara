<?php

namespace Tests\Unit;

use App\Services\TamizajeService as T;
use PHPUnit\Framework\TestCase;

/**
 * El semáforo de «Mati y los números» es un cálculo puro (arreglos de entrada, arreglo de salida),
 * así que se prueba sin base de datos. Reglas: mínimo 2 ciclos completos, últimos 3 ciclos,
 * promedio por área, color del área peor, señales extra, y lo que NO cuenta (calentamiento, toques
 * al azar, viajes poco confiables, viajes no válidos o incompletos).
 */
class TamizajeSemaforoTest extends TestCase
{
    private const PARTES = [
        1 => [2 => 'numeros_cantidades', 3 => 'numeros_cantidades', 5 => 'numeros_cantidades'],
        2 => [4 => 'sentido_cantidad', 6 => 'sentido_cantidad', 7 => 'sentido_cantidad'],
        3 => [8 => 'signos_calculo', 9 => 'signos_calculo', 10 => 'numeros_cantidades'],
    ];

    /** Un viaje con 10 preguntas por parte; $pct = % de aciertos por área (100 si no se indica). */
    private function viaje(int $ciclo, int $viaje, array $pct = [], array $o = []): array
    {
        $resp = [['parte' => 1, 'area' => 'velocidad', 'es_correcto' => true, 'cero' => false, 'fija' => true, 'nivel' => 2, 'azar' => false]];
        foreach (self::PARTES[$viaje] as $parte => $area) {
            $p = $pct[$area] ?? 100;
            for ($i = 0; $i < 10; $i++) {
                $resp[] = [
                    'parte' => $parte, 'area' => $area, 'es_correcto' => ($i * 10) < $p,
                    'cero' => !empty($o['cero']) && $i < 2, 'fija' => $i < 5, 'nivel' => $o['nivel'] ?? 2, 'azar' => false,
                ];
            }
        }
        return [
            'ciclo' => $ciclo, 'viaje' => $viaje,
            'completa' => $o['completa'] ?? true, 'valida' => $o['valida'] ?? true, 'confiable' => $o['confiable'] ?? true,
            'respuestas' => $resp,
        ];
    }

    private function ciclo(int $c, array $pct = [], array $o = []): array
    {
        return [$this->viaje($c, 1, $pct, $o), $this->viaje($c, 2, $pct, $o), $this->viaje($c, 3, $pct, $o)];
    }

    private function conCerosMal(array $viajes): array
    {
        foreach ($viajes as &$v) {
            foreach ($v['respuestas'] as &$r) {
                if ($r['cero']) {
                    $r['es_correcto'] = false;
                }
            }
        }
        return $viajes;
    }

    public function test_con_menos_de_dos_ciclos_completos_no_hay_semaforo()
    {
        $r = T::evaluar($this->ciclo(1));
        $this->assertNull($r['semaforo']);
        $this->assertSame('faltan_ciclos', $r['motivo']);
        $this->assertSame(1, $r['ciclos_completos']);

        $this->assertNull(T::evaluar([])['semaforo']);
    }

    public function test_dos_ciclos_con_todo_bien_dan_verde()
    {
        $r = T::evaluar(array_merge($this->ciclo(1), $this->ciclo(2)));
        $this->assertSame('verde', $r['semaforo']);
        $this->assertSame(2, $r['ciclos_considerados']);
        $this->assertSame(100, $r['areas']['numeros_cantidades']['pct']);
        $this->assertSame(2, $r['areas']['numeros_cantidades']['ciclos']);
    }

    public function test_el_color_es_el_del_area_peor()
    {
        $pct = ['sentido_cantidad' => 50, 'signos_calculo' => 70];
        $r = T::evaluar(array_merge($this->ciclo(1, $pct), $this->ciclo(2, $pct)));

        $this->assertSame('rojo', $r['areas']['sentido_cantidad']['color']);
        $this->assertSame('amarillo', $r['areas']['signos_calculo']['color']);
        $this->assertSame('verde', $r['areas']['numeros_cantidades']['color']);
        $this->assertSame('rojo', $r['semaforo']);
    }

    public function test_los_cortes_son_exactos_y_se_pueden_cambiar()
    {
        $dos = fn (int $a, int $b) => array_merge($this->ciclo(1, ['sentido_cantidad' => $a]), $this->ciclo(2, ['sentido_cantidad' => $b]));

        $r = T::evaluar($dos(70, 80), 75, 60);
        $this->assertSame(75, $r['areas']['sentido_cantidad']['pct']);
        $this->assertSame('verde', $r['areas']['sentido_cantidad']['color'], '75 justo es verde');

        $this->assertSame('amarillo', T::evaluar($dos(60, 60), 75, 60)['areas']['sentido_cantidad']['color'], '60 justo es amarillo');
        $this->assertSame('rojo', T::evaluar($dos(50, 60), 75, 60)['areas']['sentido_cantidad']['color']);

        $this->assertSame('rojo', T::evaluar($dos(70, 70), 90, 80)['semaforo'], 'con cortes más exigentes, 70 es rojo');
    }

    public function test_cortes_invalidos_vuelven_a_los_de_siempre()
    {
        $this->assertSame([75, 60], T::cortesValidos(80, 80));
        $this->assertSame([75, 60], T::cortesValidos(101, 50));
        $this->assertSame([75, 60], T::cortesValidos(50, 0));
        $this->assertSame([90, 70], T::cortesValidos(90, 70));
    }

    public function test_confundir_el_cero_pasa_un_verde_a_amarillo_pero_nunca_a_rojo()
    {
        // Sin errores en los ceros no hay señal.
        $bien = T::evaluar(array_merge($this->ciclo(1, [], ['cero' => true]), $this->ciclo(2, [], ['cero' => true])));
        $this->assertSame([], $bien['senales']);
        $this->assertSame('verde', $bien['semaforo']);

        $mal = T::evaluar($this->conCerosMal(array_merge($this->ciclo(1, [], ['cero' => true]), $this->ciclo(2, [], ['cero' => true]))));
        $this->assertContains('cero', $mal['senales']);
        $this->assertSame('amarillo', $mal['semaforo']);
        $this->assertTrue($mal['subio_por_senales']);

        // Un área en rojo sigue en rojo; una en amarillo no sube a rojo por las señales.
        $rojo = T::evaluar($this->conCerosMal(array_merge(
            $this->ciclo(1, ['sentido_cantidad' => 50], ['cero' => true]),
            $this->ciclo(2, ['sentido_cantidad' => 50], ['cero' => true])
        )));
        $this->assertSame('rojo', $rojo['semaforo']);
        $this->assertFalse($rojo['subio_por_senales']);

        $amarillo = T::evaluar($this->conCerosMal(array_merge(
            $this->ciclo(1, ['sentido_cantidad' => 85], ['cero' => true]),
            $this->ciclo(2, ['sentido_cantidad' => 85], ['cero' => true])
        )));
        $this->assertSame('amarillo', $amarillo['semaforo']);
    }

    public function test_necesitar_casi_siempre_el_nivel_mas_facil_es_una_senal()
    {
        $r = T::evaluar(array_merge($this->ciclo(1, [], ['nivel' => 1]), $this->ciclo(2, [], ['nivel' => 1])));
        $this->assertContains('nivel_bajo', $r['senales']);
        $this->assertSame('amarillo', $r['semaforo']);

        $r = T::evaluar(array_merge($this->ciclo(1, [], ['nivel' => 3]), $this->ciclo(2, [], ['nivel' => 3])));
        $this->assertSame([], $r['senales']);
        $this->assertSame('verde', $r['semaforo']);
    }

    public function test_los_toques_al_azar_no_cuentan()
    {
        $c = array_merge($this->ciclo(1), $this->ciclo(2));
        foreach ($c as &$v) {
            foreach ($v['respuestas'] as &$x) {
                if ($x['area'] === 'sentido_cantidad') {
                    $x['azar'] = true;
                    $x['es_correcto'] = false;
                }
            }
        }
        unset($v, $x);

        $r = T::evaluar($c);
        $this->assertNull($r['areas']['sentido_cantidad']['pct'], 'sin respuestas válidas, el área no se califica');
        $this->assertSame('verde', $r['semaforo']);
    }

    public function test_los_viajes_poco_confiables_no_cuentan()
    {
        $r = T::evaluar(array_merge($this->ciclo(1, [], ['confiable' => false]), $this->ciclo(2, [], ['confiable' => false])));
        $this->assertNull($r['semaforo']);
        $this->assertSame('sin_datos_confiables', $r['motivo']);

        // Un solo viaje poco confiable: esa área queda con un ciclo y no se califica; las demás siguen.
        $c = array_merge($this->ciclo(1), $this->ciclo(2));
        $c[1]['confiable'] = false;
        $r = T::evaluar($c);
        $this->assertSame(1, $r['areas']['sentido_cantidad']['ciclos']);
        $this->assertNull($r['areas']['sentido_cantidad']['pct']);
        $this->assertSame(2, $r['areas']['numeros_cantidades']['ciclos']);
    }

    public function test_un_viaje_no_valido_o_incompleto_rompe_el_ciclo()
    {
        $c = array_merge($this->ciclo(1), $this->ciclo(2));
        $c[1]['valida'] = false;
        $r = T::evaluar($c);
        $this->assertSame(1, $r['ciclos_completos']);
        $this->assertNull($r['semaforo']);

        $c = array_merge($this->ciclo(1), $this->ciclo(2));
        $c[5]['completa'] = false;
        $this->assertSame(1, T::evaluar($c)['ciclos_completos']);

        $c = array_merge($this->ciclo(1), [$this->viaje(2, 1), $this->viaje(2, 2)]);
        $this->assertSame(1, T::evaluar($c)['ciclos_completos'], 'un ciclo a medias no cuenta');
    }

    public function test_solo_se_miran_los_ultimos_tres_ciclos()
    {
        $c = array_merge($this->ciclo(1, ['sentido_cantidad' => 0]), $this->ciclo(2), $this->ciclo(3), $this->ciclo(4));
        $r = T::evaluar($c);

        $this->assertSame(4, $r['ciclos_completos']);
        $this->assertSame(3, $r['ciclos_considerados']);
        $this->assertSame(100, $r['areas']['sentido_cantidad']['pct'], 'el ciclo viejo y malo queda fuera');
        $this->assertSame('verde', $r['semaforo']);
    }

    public function test_un_area_sin_respuestas_suficientes_no_se_califica()
    {
        $c = array_merge($this->ciclo(1), $this->ciclo(2));
        foreach ($c as &$v) {
            $v['respuestas'] = array_values(array_filter($v['respuestas'], fn ($x) => $x['area'] !== 'signos_calculo'));
        }
        unset($v);

        $r = T::evaluar($c);
        $this->assertNull($r['areas']['signos_calculo']['pct']);
        $this->assertSame('verde', $r['semaforo']);
    }

    public function test_cuando_el_color_empeora()
    {
        $this->assertTrue(T::empeoro(null, 'amarillo'));
        $this->assertTrue(T::empeoro('verde', 'amarillo'));
        $this->assertTrue(T::empeoro('amarillo', 'rojo'));
        $this->assertTrue(T::empeoro(null, 'rojo'));

        $this->assertFalse(T::empeoro('rojo', 'amarillo'));
        $this->assertFalse(T::empeoro('amarillo', 'amarillo'));
        $this->assertFalse(T::empeoro('verde', 'verde'));
        $this->assertFalse(T::empeoro('verde', null));
        $this->assertFalse(T::empeoro(null, null));
    }
}
