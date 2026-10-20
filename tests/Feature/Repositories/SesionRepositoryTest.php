<?php

namespace Tests\Feature\Repositories;

use App\Repositories\SesionRepository;

class SesionRepositoryTest extends RepositorioTestCase
{
    private function sesion(array $e, array $extra = [])
    {
        return app(SesionRepository::class)->crear(array_merge([
            'estudiante_id' => $e['estudiante']->id,
            'juego_id'      => $e['juego']->id,
            'nivel_id'      => $e['nivel1']->id,
            'fecha_inicio'  => now(),
            'estado'        => 'en_curso',
        ], $extra));
    }

    private function mov(array $e, $sesion, $ejercicio, bool $ok, ?string $tipoError = null, ?int $ms = 2000)
    {
        return app(SesionRepository::class)->registrarMovimiento([
            'sesion_id'           => $sesion->id,
            'contenido_id'        => $ejercicio->id,
            'numero_intento'      => 1,
            'respuesta_dada'      => $ok ? $ejercicio->respuesta_correcta : '99',
            'es_correcto'         => $ok,
            'tiempo_respuesta_ms' => $ms,
            'tipo_error'          => $tipoError,
            'timestamp_mov'       => now(),
        ]);
    }

    public function test_abandonar_en_curso_cierra_solo_las_sesiones_del_mismo_estudiante_y_juego()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $a = $this->sesion($e);
        $b = $this->sesion($e);

        $this->assertSame(2, $repo->abandonarEnCurso($e['estudiante']->id, $e['juego']->id));
        $this->assertSame('abandonada', $a->fresh()->estado);
        $this->assertSame('abandonada', $b->fresh()->estado);
        $this->assertNotNull($a->fresh()->fecha_fin);

        $nueva = $this->sesion($e);
        $this->assertSame(0, $repo->abandonarEnCurso($e['estudiante']->id, 999999));
        $this->assertSame('en_curso', $nueva->fresh()->estado);
    }

    public function test_movimientos_intentos_y_ejercicios_respondidos()
    {
        $e     = $this->escenario();
        $repo  = app(SesionRepository::class);
        $s     = $this->sesion($e);
        [$x, $y] = [$e['ejercicios'][0], $e['ejercicios'][1]];

        $this->mov($e, $s, $x, false);
        $this->mov($e, $s, $x, true);
        $this->mov($e, $s, $y, true);

        $this->assertSame(2, $repo->contarIntentos($s, $x->id));
        $this->assertSame(1, $repo->contarIntentos($s, $y->id));
        $this->assertSame(0, $repo->contarIntentos($s, $e['ejercicios'][2]->id));
        $this->assertEqualsCanonicalizing([$x->id, $y->id], $repo->idsEjerciciosRespondidos($s));
        $this->assertSame(2, $repo->contarEjerciciosRespondidos($s));
    }

    public function test_acumular_resultado_suma_puntos_y_cuenta_aciertos_o_errores()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->sesion($e);

        $repo->acumularResultado($s, 15, true);
        $repo->acumularResultado($s, 0, false);
        $repo->acumularResultado($s, 5, true);

        $this->assertSame(20, (int) $s->puntaje_obtenido);
        $this->assertSame(2, (int) $s->aciertos);
        $this->assertSame(1, (int) $s->errores);
        $this->assertSame(20, (int) $s->fresh()->puntaje_obtenido);
    }

    public function test_ultimos_movimientos_vienen_del_mas_reciente_y_respetan_el_limite()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->sesion($e);
        $m1 = $this->mov($e, $s, $e['ejercicios'][0], true);
        $m2 = $this->mov($e, $s, $e['ejercicios'][1], false);
        $m3 = $this->mov($e, $s, $e['ejercicios'][2], true);

        $ultimos = $repo->ultimosMovimientos($s, 2);

        $this->assertEquals([$m3->id, $m2->id], $ultimos->pluck('id')->all());
    }

    public function test_completar_y_listar_completadas_con_filtro_por_juego()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->sesion($e);
        $otra = $this->sesion($e, ['juego_id' => $e['juego']->id]);

        $repo->completar($s, true);

        $this->assertSame('completada', $s->fresh()->estado);
        $this->assertTrue((bool) $s->fresh()->nivel_superado);
        $this->assertNotNull($s->fresh()->fecha_fin);

        $todas = $repo->completadasDe($e['estudiante']->id);
        $this->assertEquals([$s->id], $todas->pluck('id')->all());
        $this->assertTrue($todas->first()->relationLoaded('nivel'));
        $this->assertTrue($todas->first()->relationLoaded('juego'));
        $this->assertCount(1, $repo->completadasDe($e['estudiante']->id, $e['juego']->id));
        $this->assertCount(0, $repo->completadasDe($e['estudiante']->id, 999999));
        $this->assertSame('en_curso', $otra->fresh()->estado);
    }

    public function test_consultas_del_diagnostico()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $s    = $this->sesion($e);
        $this->mov($e, $s, $e['ejercicios'][0], false, 'calculo', 1000);
        $this->mov($e, $s, $e['ejercicios'][1], false, 'calculo', 3000);
        $this->mov($e, $s, $e['ejercicios'][2], false, 'atencion', null);
        $this->mov($e, $s, $e['ejercicios'][0], true, null, 2000);

        $ids = collect([$s->id]);

        $this->assertEquals(['calculo' => 2, 'atencion' => 1], $repo->erroresPorTipo($ids));
        $this->assertEquals(2000.0, (float) $repo->tiempoRespuestaPromedioMs($ids)); // (1000+3000+2000)/3
        $this->assertNull($repo->tiempoRespuestaPromedioMs(collect([999999])));

        $movs = $repo->movimientosConEjercicio($ids);
        $this->assertCount(4, $movs);
        $this->assertTrue($movs->first()->relationLoaded('ejercicio'));
    }
}
