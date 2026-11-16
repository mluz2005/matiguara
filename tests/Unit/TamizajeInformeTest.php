<?php

namespace Tests\Unit;

use App\Services\TamizajeInformeService as S;
use App\Services\TamizajeService;
use App\Support\Numeros\FiltroFrases;
use App\Support\Numeros\PlantillaInforme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Etapa 3 — informe con ayuda de la IA. Todo lo que se puede probar sin base de datos ni red:
 * el filtro de frases, la plantilla de respaldo, la lectura de la respuesta de la IA,
 * el JSON anónimo que sale hacia la IA y el prompt.
 */
class TamizajeInformeTest extends TestCase
{
    private const LECTURA = 'En los últimos ciclos los aciertos en «Sentido de la cantidad» quedaron algo por debajo de lo esperado. Conviene mirar con más atención cómo compara cantidades. Esto no es un diagnóstico: un resultado puede deberse al cansancio.';
    private const ACTIV   = "1) Mostrar dos montoncitos de semillas y preguntar cuál tiene más antes de contar.\n2) Ubicar números en una cinta numérica dibujada en el piso.";
    private const FAMILIA = 'Hola, familia de {nombre}. En la escuela está jugando a «Mati y los números». Notamos que a veces le cuesta un poco comparar cantidades. En casa pueden jugar con semillas a ver dónde hay más. No es un diagnóstico ni una evaluación. Los invito a conversar.';

    private function resultado(string $color = 'amarillo', array $senales = []): array
    {
        return [
            'semaforo' => $color, 'motivo' => null, 'ciclos_completos' => 3, 'ciclos_considerados' => 3,
            'areas' => [
                'numeros_cantidades' => ['pct' => 88, 'ciclos' => 3, 'color' => 'verde'],
                'sentido_cantidad'   => ['pct' => 68, 'ciclos' => 3, 'color' => 'amarillo'],
                'signos_calculo'     => ['pct' => 55, 'ciclos' => 3, 'color' => 'rojo'],
            ],
            'senales' => $senales, 'subio_por_senales' => false,
        ];
    }

    // ── Filtro de frases ─────────────────────────────────────────────────

    #[DataProvider('frasesProhibidas')]
    public function test_el_filtro_rechaza_etiquetas_y_alarma(string $frase)
    {
        $texto = 'Esta es una frase de prueba bastante larga para pasar el largo mínimo. ' . $frase;
        $this->assertNotSame([], FiltroFrases::problemas($texto), "Debía rechazar: {$frase}");
    }

    public static function frasesProhibidas(): array
    {
        return [
            ['Parece que tiene discalculia.'],
            ['Puede ser Dislexia o algo parecido.'],
            ['Sería un trastorno del aprendizaje.'],
            ['Tiene TDAH.'],
            ['Hay que hacer un diagnóstico cuanto antes.'],
            ['Conviene llevarlo al psicopedagogo.'],
            ['Habría que derivarlo a un especialista.'],
            ['Es un caso urgente y preocupante.'],
            ['La situación es grave.'],
            ['Es un niño lento para las cuentas.'],
            ['Tiene un problema con los números.'],
            ['Presenta una dificultad importante.'],
            ['Sufre con los números.'],
            ['Está muy por debajo de sus compañeros.'],
            ['Hay riesgo de que se atrase.'],
            ['Es un niño con retraso.'],
            ['Necesita tratamiento.'],
            ['Escribinos a maria@correo.com para más datos.'],
            ['Más información en https://ejemplo.com'],
            ['Llamá al 0981123456 hoy mismo.'],
            ['Quedó en el nivel <b>bajo</b>.'],
        ];
    }

    public function test_el_filtro_deja_pasar_el_no_es_un_diagnostico()
    {
        $this->assertSame([], FiltroFrases::problemas(self::LECTURA));
        $this->assertSame([], FiltroFrases::problemas(self::FAMILIA, true));
    }

    public function test_el_filtro_rechaza_texto_vacio_corto_o_larguisimo()
    {
        $this->assertContains('texto demasiado corto', FiltroFrases::problemas('Hola'));
        $this->assertContains('texto demasiado largo', FiltroFrases::problemas(str_repeat('palabra ', 400)));
    }

    public function test_el_texto_de_la_familia_no_puede_hablar_de_colores_ni_cifras()
    {
        $base = 'Hola, familia de {nombre}. Estamos jugando en la escuela a un juego de números y queremos acompañarlo entre todos. ';
        $this->assertSame([], FiltroFrases::problemas($base, true));
        $this->assertNotSame([], FiltroFrases::problemas($base . 'Está en amarillo.', true));
        $this->assertNotSame([], FiltroFrases::problemas($base . 'Acertó el 60 % de las preguntas.', true));
        $this->assertNotSame([], FiltroFrases::problemas($base . 'Su semáforo cambió.', true));
        // En el texto de la docente sí pueden ir cifras.
        $this->assertSame([], FiltroFrases::problemas($base . 'Acertó el 60 % de las preguntas.', false));
    }

    public function test_el_filtro_solo_acepta_el_marcador_nombre()
    {
        $base = 'Hola, familia de {nombre}. Estamos jugando en la escuela a un juego de números y queremos acompañarlo. ';
        $this->assertSame([], FiltroFrases::problemas($base));
        $this->assertNotSame([], FiltroFrases::problemas($base . 'Atentamente {docente}.'));
        $this->assertNotSame([], FiltroFrases::problemas($base . 'Atentamente [Nombre de la docente].'));
    }

    // ── Plantilla de respaldo ────────────────────────────────────────────

    public function test_la_plantilla_siempre_pasa_el_propio_filtro()
    {
        $combinaciones = [];
        foreach (['verde', 'amarillo', 'rojo'] as $color) {
            foreach ([[], ['cero'], ['nivel_bajo'], ['cero', 'nivel_bajo']] as $senales) {
                $combinaciones[] = $this->resultado($color, $senales);
            }
        }
        // Una sola área con atención, otra sin datos, y todo en verde.
        $solo = $this->resultado('amarillo');
        $solo['areas']['numeros_cantidades']['color'] = 'verde';
        $solo['areas']['signos_calculo'] = ['pct' => null, 'ciclos' => 1, 'color' => null];
        $combinaciones[] = $solo;
        $verde = $this->resultado('verde');
        foreach ($verde['areas'] as $k => $a) {
            $verde['areas'][$k]['color'] = 'verde';
        }
        $combinaciones[] = $verde;

        foreach ($combinaciones as $r) {
            $t = PlantillaInforme::generar($r);
            $this->assertSame([], FiltroFrases::problemas($t['lectura']), 'lectura: ' . $t['lectura']);
            $this->assertSame([], FiltroFrases::problemas($t['actividades']), 'actividades: ' . $t['actividades']);
            $this->assertSame([], FiltroFrases::problemas($t['texto_familia'], true), 'familia: ' . $t['texto_familia']);
            $this->assertStringContainsString('{nombre}', $t['texto_familia']);
        }
    }

    public function test_la_plantilla_apunta_a_la_area_mas_baja_y_aclara_que_no_es_diagnostico()
    {
        $t = PlantillaInforme::generar($this->resultado('rojo'));

        $this->assertStringContainsString('Signos y cálculo', $t['lectura']);
        $this->assertStringContainsString('no es un diagnóstico', $t['lectura']);
        $this->assertStringContainsString('palillos', $t['actividades']);
        $this->assertStringContainsString('sumar o restar', $t['texto_familia']);
    }

    public function test_la_plantilla_en_verde_no_inventa_dificultades()
    {
        $r = $this->resultado('verde');
        foreach ($r['areas'] as $k => $a) {
            $r['areas'][$k]['color'] = 'verde';
        }
        $t = PlantillaInforme::generar($r);

        $this->assertStringContainsString('no se ven señales', $t['lectura']);
        $this->assertStringNotContainsString('le cuesta', $t['texto_familia']);
    }

    // ── Lectura de la respuesta de la IA ─────────────────────────────────

    private function json(array $over = []): string
    {
        return json_encode(array_merge([
            'lectura' => self::LECTURA, 'actividades' => self::ACTIV, 'texto_familia' => self::FAMILIA,
        ], $over), JSON_UNESCAPED_UNICODE);
    }

    public function test_una_respuesta_buena_se_acepta()
    {
        [$t, $motivo] = S::interpretarRespuesta($this->json());

        $this->assertNull($motivo);
        $this->assertSame(self::LECTURA, $t['lectura']);
        $this->assertSame(self::FAMILIA, $t['texto_familia']);
    }

    public function test_acepta_la_respuesta_envuelta_en_bloque_de_codigo_o_con_texto_alrededor()
    {
        [$t1, ] = S::interpretarRespuesta("```json\n" . $this->json() . "\n```");
        [$t2, ] = S::interpretarRespuesta("Acá está el informe:\n" . $this->json() . "\nEspero que sirva.");

        $this->assertNotNull($t1);
        $this->assertNotNull($t2);
    }

    public function test_actividades_puede_venir_como_lista()
    {
        [$t, ] = S::interpretarRespuesta($this->json(['actividades' => ['Primera idea con semillas.', 'Segunda idea con tarjetas.']]));

        $this->assertSame("Primera idea con semillas.\nSegunda idea con tarjetas.", $t['actividades']);
    }

    public function test_json_roto_o_incompleto_pide_la_plantilla()
    {
        [$t, $m] = S::interpretarRespuesta('esto no es json');
        $this->assertNull($t);
        $this->assertStringContainsString('JSON', $m);

        [$t, $m] = S::interpretarRespuesta(json_encode(['lectura' => self::LECTURA, 'actividades' => self::ACTIV]));
        $this->assertNull($t);
        $this->assertStringContainsString('texto_familia', $m);

        [$t, ] = S::interpretarRespuesta($this->json(['lectura' => '   ']));
        $this->assertNull($t);
    }

    public function test_una_frase_prohibida_en_cualquier_campo_descarta_todo_el_informe()
    {
        foreach (['lectura', 'actividades', 'texto_familia'] as $campo) {
            [$t, $m] = S::interpretarRespuesta($this->json([
                $campo => 'Esto parece discalculia y conviene llevarlo a un especialista cuanto antes, sin esperar más tiempo.',
            ]));
            $this->assertNull($t, "Debía descartar por {$campo}");
            $this->assertStringContainsString('Frase no permitida', $m);
        }
    }

    public function test_si_la_ia_olvida_el_marcador_se_agrega_el_saludo()
    {
        $sin = str_replace('{nombre}', 'su hijo', self::FAMILIA);
        [$t, ] = S::interpretarRespuesta($this->json(['texto_familia' => $sin]));

        $this->assertStringStartsWith('Hola, familia de {nombre}.', $t['texto_familia']);
    }

    // ── Datos anónimos y prompt ──────────────────────────────────────────

    private function datos(): array
    {
        return S::datosAnonimos('uuid-1234', 2, $this->resultado('amarillo', ['cero']),
            ['tendencia' => [70, 66, 62], 'errores' => ['lejano' => 5, 'cero' => 7, 'azar' => 9, 'inventado' => 3, 'parecido' => 0]],
            TamizajeService::CORTE_VERDE_DEFECTO, TamizajeService::CORTE_AMARILLO_DEFECTO);
    }

    public function test_los_datos_anonimos_llevan_solo_lo_permitido()
    {
        $d = $this->datos();

        $this->assertEqualsCanonicalizing(
            ['id_anonimizado', 'grado', 'semaforo', 'cortes', 'ciclos_completos', 'ciclos_considerados',
             'areas', 'senales', 'tendencia_por_ciclo_pct', 'errores_frecuentes'],
            array_keys($d)
        );
        $this->assertSame('uuid-1234', $d['id_anonimizado']);
        $this->assertSame(68, $d['areas']['sentido_cantidad']['porcentaje_aciertos']);
        $this->assertSame([70, 66, 62], $d['tendencia_por_ciclo_pct']);
        $this->assertSame(['cero'], $d['senales']);
    }

    public function test_los_errores_se_filtran_y_se_ordenan()
    {
        // 'azar' no es un error de comprensión, 'inventado' no existe y 0 no cuenta.
        $this->assertSame(['cero' => 7, 'lejano' => 5], $this->datos()['errores_frecuentes']);
    }

    public function test_el_prompt_lleva_los_datos_y_las_prohibiciones()
    {
        $p = S::construirPrompt($this->datos());

        $this->assertStringContainsString('uuid-1234', $p);
        $this->assertStringContainsString('"sentido_cantidad"', $p);
        $this->assertStringContainsString('NO es una evaluación ni un diagnóstico', $p);
        $this->assertStringContainsString('NO recomiendes derivar', $p);
        $this->assertStringContainsString('{nombre}', $p);
        foreach (['lectura', 'actividades', 'texto_familia'] as $clave) {
            $this->assertStringContainsString("\"{$clave}\"", $p);
        }
    }
}
