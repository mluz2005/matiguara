<?php

namespace Tests\Feature\Repositories;

use App\Models\ContenidoNivel;
use App\Repositories\SesionRepository;
use App\Services\SesionService;
use App\Services\SistemaAdaptativoService;
use App\Support\FrutasJuego;
use App\Support\PistasSumaFrutas;

/**
 * Mejoras pedagógicas de los juegos (docente de 1.er ciclo):
 *   1) bonus de velocidad solo en Flash Matemático (ver FlujoSesionTest y MemoriaServiceTest);
 *   2) segunda oportunidad con pista en Suma de Frutas;
 *   4) frutas del contexto paraguayo.
 */
class SegundaOportunidadTest extends RepositorioTestCase
{
    private function ejercicioFalso(int $a, string $op, int $b): ContenidoNivel
    {
        return new ContenidoNivel(['valor_a' => $a, 'operador' => $op, 'valor_b' => $b]);
    }

    private function mov(array $e, $sesion, $ejercicio, int $intento, bool $ok)
    {
        return app(SesionRepository::class)->registrarMovimiento([
            'sesion_id'           => $sesion->id,
            'contenido_id'        => $ejercicio->id,
            'numero_intento'      => $intento,
            'respuesta_dada'      => $ok ? $ejercicio->respuesta_correcta : '999',
            'es_correcto'         => $ok,
            'tiempo_respuesta_ms' => 2000,
            'timestamp_mov'       => now(),
        ]);
    }

    // ── Repositorio ──────────────────────────────────────────────────────

    public function test_reintento_pendiente_solo_si_lo_ultimo_fue_un_primer_fallo()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->nuevaSesion($e);
        [$x, $y] = [$e['ejercicios'][0], $e['ejercicios'][1]];

        $this->assertNull($repo->contenidoPendienteDeReintento($s), 'Sin movimientos no hay nada pendiente.');

        $this->mov($e, $s, $x, 1, false);
        $this->assertSame($x->id, $repo->contenidoPendienteDeReintento($s));
        $this->assertSame('999', $repo->respuestaDelPrimerIntento($s, $x->id));

        $this->mov($e, $s, $x, 2, true);
        $this->assertNull($repo->contenidoPendienteDeReintento($s), 'Con el segundo intento ya no está pendiente.');

        $this->mov($e, $s, $y, 1, true);
        $this->assertNull($repo->contenidoPendienteDeReintento($s), 'Un acierto al primer intento no deja nada pendiente.');
        $this->assertNull($repo->respuestaDelPrimerIntento($s, $e['ejercicios'][2]->id));
    }

    public function test_ultimos_resultados_toman_el_ultimo_intento_de_cada_ejercicio()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->nuevaSesion($e);
        [$x, $y, $z] = [$e['ejercicios'][0], $e['ejercicios'][1], $e['ejercicios'][2]];

        $this->mov($e, $s, $x, 1, false);
        $this->mov($e, $s, $x, 2, true);    // x: falló y luego acertó -> cuenta como acierto
        $this->mov($e, $s, $y, 1, true);
        $this->mov($e, $s, $z, 1, false);
        $this->mov($e, $s, $z, 2, false);   // z: falló dos veces -> un solo error

        $res = $repo->ultimosResultadosPorEjercicio($s, 10);

        $this->assertCount(3, $res, 'Un resultado por ejercicio, no uno por intento.');
        $this->assertEquals([$z->id, $y->id, $x->id], $res->pluck('contenido_id')->all());
        $this->assertEquals([false, true, true], $res->map(fn ($m) => (bool) $m->es_correcto)->all());
        $this->assertCount(2, $repo->ultimosResultadosPorEjercicio($s, 2));
    }

    public function test_regla_3_2_cuenta_un_ejercicio_recuperado_en_la_segunda_oportunidad_como_acierto()
    {
        $e       = $this->escenario();
        $adapt   = app(SistemaAdaptativoService::class);
        $s       = $this->nuevaSesion($e);

        foreach ($e['ejercicios']->take(3) as $ej) {
            $this->mov($e, $s, $ej, 1, false);
            $this->mov($e, $s, $ej, 2, true);
        }

        $ev = $adapt->evaluar($s);
        $this->assertSame('sube', $ev['tipo_cambio'], '3 ejercicios resueltos (aunque con pista) cuentan como 3 aciertos.');
    }

    public function test_el_servicio_solo_ofrece_reintento_en_suma_de_frutas()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);

        $this->assertSame(['Suma de Frutas'], SesionService::JUEGOS_CON_REINTENTO);
        $this->assertTrue($sesiones->permiteReintento($this->nuevaSesion($e)));
    }

    // ── Pistas ───────────────────────────────────────────────────────────

    public function test_pista_hasta_10_cuenta_hacia_adelante_sin_decir_el_resultado()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(5, '+', 3));

        $this->assertSame('contar', $p['tipo']);
        $this->assertStringContainsString('Empezá en 5', $p['es']);
        $this->assertStringContainsString('6, 7', $p['es']);
        $this->assertDoesNotMatchRegularExpression('/(?<!\d)8(?!\d)/', $p['es'], 'No debe regalar el resultado.');

        // Con b = 1 o 2 la serie nunca llega al resultado
        for ($a = 1; $a <= 9; $a++) {
            foreach ([1, 2] as $b) {
                $r = $a + $b;
                if ($r === $a || $r === $b) continue;
                $texto = PistasSumaFrutas::para($this->ejercicioFalso($a, '+', $b))['es'];
                $this->assertDoesNotMatchRegularExpression('/(?<!\d)' . $r . '(?!\d)/', $texto, "$a + $b: la pista regala el $r.");
            }
        }
    }

    public function test_pista_de_resta_hasta_10_cuenta_hacia_atras()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(9, '-', 4));

        $this->assertSame('quitar', $p['tipo']);
        $this->assertStringContainsString('9', $p['es']);
        $this->assertStringContainsString('para atrás', $p['es']);
    }

    public function test_pista_de_suma_con_llevada_explica_las_unidades_y_la_decena()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(27, '+', 15));

        $this->assertSame('llevada', $p['tipo']);
        $this->assertStringContainsString('7 + 5 = 12', $p['es']);
        $this->assertStringContainsString('llevá la decena', $p['es']);
    }

    public function test_pista_de_suma_sin_llevada_suma_por_partes()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(24, '+', 35));

        $this->assertSame('partes', $p['tipo']);
        $this->assertStringContainsString('20 + 30', $p['es']);
        $this->assertStringContainsString('4 + 5', $p['es']);
    }

    public function test_pista_de_resta_con_prestamo_pide_prestada_una_decena()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(52, '-', 27));

        $this->assertSame('prestamo', $p['tipo']);
        $this->assertStringContainsString('pedí prestada 1 decena', $p['es']);
        $this->assertStringContainsString('12', $p['es']);
    }

    public function test_pista_de_resta_con_prestamo_a_traves_de_un_cero_no_da_numeros_enganosos()
    {
        $p = PistasSumaFrutas::para($this->ejercicioFalso(205, '-', 38));

        $this->assertSame('prestamo', $p['tipo']);
        $this->assertStringContainsString('Si ahí hay un 0', $p['es']);
    }

    public function test_pista_siempre_trae_castellano_y_guarani_y_null_si_no_es_suma_ni_resta()
    {
        foreach ([[5, '+', 3], [9, '-', 4], [27, '+', 15], [52, '-', 27], [240, '+', 130], [500, '-', 200]] as [$a, $op, $b]) {
            $p = PistasSumaFrutas::para($this->ejercicioFalso($a, $op, $b));
            $this->assertNotEmpty($p['es'], "$a $op $b sin pista en castellano");
            $this->assertNotEmpty($p['gn'], "$a $op $b sin pista en guaraní");
        }

        $this->assertNull(PistasSumaFrutas::para($this->ejercicioFalso(3, '×', 4)));
        $this->assertNull(PistasSumaFrutas::para(new ContenidoNivel(['operador' => '+'])));
        $this->assertNull(PistasSumaFrutas::para($this->ejercicioFalso(3, '-', 8)), 'Nunca se explican restas negativas.');
    }

    public function test_todos_los_ejercicios_sembrados_de_suma_de_frutas_tienen_pista()
    {
        $this->seed(\Database\Seeders\MemoriaJuegoSeeder::class);
        $this->seed(\Database\Seeders\ContenidoGrados23Seeder::class);

        $ejercicios = ContenidoNivel::whereHas('nivel.juego', fn ($q) => $q->where('nombre_es', 'Suma de Frutas'))->get();
        $this->assertGreaterThanOrEqual(192, $ejercicios->count(), 'Al menos los 96 + 96 ejercicios de 2.° y 3.°.');

        foreach ($ejercicios as $ej) {
            $this->assertNotNull(PistasSumaFrutas::para($ej), "El ejercicio {$ej->id} ({$ej->valor_a} {$ej->operador} {$ej->valor_b}) no tiene pista.");
        }
    }

    // ── Frutas paraguayas ────────────────────────────────────────────────

    public function test_catalogo_de_frutas_es_del_contexto_paraguayo()
    {
        $nombres = array_column(FrutasJuego::catalogo(), 'es');

        foreach (['mango', 'coco', 'guayaba', 'banana', 'naranja', 'sandía', 'piña', 'limón'] as $fruta) {
            $this->assertContains($fruta, $nombres);
        }
        foreach (['uva', 'frutilla', 'durazno'] as $fruta) {
            $this->assertNotContains($fruta, $nombres, "$fruta ya no se usa.");
        }
    }

    public function test_catalogo_de_frutas_esta_completo_y_rota_sin_repetir_en_una_partida()
    {
        $catalogo = FrutasJuego::catalogo();
        $this->assertCount(10, $catalogo);

        foreach ($catalogo as $f) {
            foreach (['emoji', 'es', 'es_pl', 'genero', 'gn'] as $campo) {
                $this->assertNotEmpty($f[$campo], "Falta '$campo' en {$f['es']}.");
            }
            $this->assertContains($f['genero'], ['f', 'm']);
        }
        $this->assertSame(10, count(array_unique(array_column($catalogo, 'emoji'))), 'Cada fruta tiene su propio emoji.');

        $e      = $this->escenario();
        $sesion = $this->nuevaSesion($e);
        $vistas = [];
        foreach ($e['ejercicios']->take(5) as $i => $ej) {
            $escena = FrutasJuego::escena($ej, $sesion, $i);
            $this->assertNotNull($escena);
            $vistas[] = $escena['fruta']['es'];
        }
        $this->assertSame(5, count(array_unique($vistas)), 'Las 5 preguntas de una partida llevan 5 frutas distintas.');
    }

    // ── Controlador: el flujo completo vista + redirecciones ─────────────

    public function test_flujo_web_primer_error_vuelve_con_pista_y_sin_la_opcion_equivocada()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);
        $sesion   = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $this->actingAs($e['estudiante'], 'estudiante');

        // Primera vista: sin pista
        $r1 = $this->get(route('estudiante.sesion.ejercicio', $sesion->id))->assertOk();
        $this->assertFalse($r1->viewData('esReintento'));
        $this->assertNull($r1->viewData('pista'));
        $ejercicio = $r1->viewData('ejercicio');
        $r1->assertDontSee('mv2-pista', false);

        // Responde mal con una opción que existe
        $mala = collect($ejercicio->opciones_json)->first(fn ($o) => (string) $o !== (string) $ejercicio->respuesta_correcta);
        $this->assertNotNull($mala, 'El ejercicio necesita al menos una opción incorrecta.');

        $this->post(route('estudiante.sesion.responder', $sesion->id), [
            'contenido_id' => $ejercicio->id, 'respuesta' => (string) $mala, 'tiempo_ms' => 3000,
        ])->assertRedirect(route('estudiante.sesion.ejercicio', $sesion->id))
          ->assertSessionHas('ultimo_resultado.reintento', true);

        // Segunda vista: mismo ejercicio, con pista, sin la opción que ya eligió
        $r2 = $this->get(route('estudiante.sesion.ejercicio', $sesion->id))->assertOk();
        $this->assertTrue($r2->viewData('esReintento'));
        $this->assertNotNull($r2->viewData('pista'));
        $this->assertSame($ejercicio->id, $r2->viewData('ejercicio')->id);
        $this->assertNotContains((string) $mala, $r2->viewData('opciones')->map(fn ($o) => (string) $o)->all());
        $this->assertContains((string) $ejercicio->respuesta_correcta, $r2->viewData('opciones')->map(fn ($o) => (string) $o)->all());
        $r2->assertSee('mv2-pista', false);
        $this->assertSame(0, (int) $sesion->fresh()->errores, 'El primer error todavía no cuenta.');

        // Acierta en la segunda oportunidad: avanza, y 5 puntos
        $this->post(route('estudiante.sesion.responder', $sesion->id), [
            'contenido_id' => $ejercicio->id, 'respuesta' => (string) $ejercicio->respuesta_correcta, 'tiempo_ms' => 4000,
        ])->assertSessionHas('ultimo_resultado.puntos', 5);

        $this->assertSame(5, (int) $sesion->fresh()->puntaje_obtenido);
        $r3 = $this->get(route('estudiante.sesion.ejercicio', $sesion->id))->assertOk();
        $this->assertFalse($r3->viewData('esReintento'));
        $this->assertNotSame($ejercicio->id, $r3->viewData('ejercicio')->id);
    }
}
