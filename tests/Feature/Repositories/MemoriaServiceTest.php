<?php

namespace Tests\Feature\Repositories;

use App\Repositories\MemoriaRepository;
use App\Repositories\SesionRepository;
use App\Services\MemoriaService;

class MemoriaServiceTest extends RepositorioTestCase
{
    private function servicio(): MemoriaService
    {
        return app(MemoriaService::class);
    }

    private function mov($sesion, $e, bool $acierto)
    {
        [$a, $b] = $e['tarjetas']->take(2)->all();
        app(MemoriaRepository::class)->registrarMovimiento([
            'sesion_id'    => $sesion->id,
            'tarjeta_a_id' => $a->id,
            'tarjeta_b_id' => $acierto ? $a->id : $b->id,
            'es_acierto'   => $acierto,
        ]);
    }

    public function test_generar_mazo_arma_dos_cartas_por_par_con_posiciones_consecutivas()
    {
        $e     = $this->escenarioMemoria();
        $mazo  = $this->servicio()->generarMazo($e['nivel1']);
        $pares = min($e['tarjetas']->count(), (int) $e['nivel1']->total_ejercicios);

        $this->assertCount($pares * 2, $mazo);
        $this->assertSame(range(0, count($mazo) - 1), array_column($mazo, 'posicion'));

        foreach (array_count_values(array_column($mazo, 'tarjeta_id')) as $veces) {
            $this->assertSame(2, $veces, 'Cada tarjeta aparece exactamente dos veces.');
        }
        $idsPool = $e['tarjetas']->pluck('id')->all();
        foreach ($mazo as $carta) {
            $this->assertContains($carta['tarjeta_id'], $idsPool);
        }
    }

    public function test_registrar_movimiento_calcula_puntos_con_bonus_y_acumula_en_la_sesion()
    {
        $e      = $this->escenarioMemoria();
        $srv    = $this->servicio();
        $sesion = $this->nuevaSesion($e);
        [$a, $b] = $e['tarjetas']->take(2)->all();

        $r = $srv->registrarMovimiento($sesion, $a->id, $a->id, 2500);   // <= 3 s
        $this->assertTrue($r['es_acierto']);
        $this->assertSame(15, $r['puntos']);

        $r = $srv->registrarMovimiento($sesion, $a->id, $a->id, 6000);   // <= 8 s
        $this->assertSame(12, $r['puntos']);

        $r = $srv->registrarMovimiento($sesion, $a->id, $a->id, 9000);   // lento
        $this->assertSame(10, $r['puntos']);

        $r = $srv->registrarMovimiento($sesion, $a->id, $b->id, 1000);   // error
        $this->assertFalse($r['es_acierto']);
        $this->assertSame(0, $r['puntos']);

        $sesion = $sesion->fresh();
        $this->assertSame(37, (int) $sesion->puntaje_obtenido);
        $this->assertSame(3, (int) $sesion->aciertos);
        $this->assertSame(1, (int) $sesion->errores);
        $this->assertDatabaseCount('movimientos_memoria', 4);
    }

    public function test_evaluar_3y2_sube_con_tres_aciertos_y_baja_con_dos_errores()
    {
        $e   = $this->escenarioMemoria();
        $srv = $this->servicio();

        $s = $this->nuevaSesion($e);
        $this->assertNull($srv->evaluar3y2($s), 'Sin movimientos no hay cambio.');
        $this->mov($s, $e, true); $this->mov($s, $e, true);
        $this->assertNull($srv->evaluar3y2($s));
        $this->mov($s, $e, true);
        $this->assertSame('sube', $srv->evaluar3y2($s));

        $s2 = $this->nuevaSesion($e);
        $this->mov($s2, $e, false);
        $this->assertNull($srv->evaluar3y2($s2));
        $this->mov($s2, $e, false);
        $this->assertSame('baja', $srv->evaluar3y2($s2));

        $s3 = $this->nuevaSesion($e);
        $this->mov($s3, $e, false); $this->mov($s3, $e, true); $this->mov($s3, $e, true);
        $this->assertNull($srv->evaluar3y2($s3), 'Racha interrumpida: ni sube ni baja.');
    }

    private function partida(array $e, bool $gano)
    {
        return $this->nuevaSesion($e, [
            'estado'         => 'completada',
            'nivel_superado' => $gano,
            'fecha_fin'      => now(),
        ]);
    }

    public function test_racha_por_partida_sube_a_la_tercera_ganada_seguida()
    {
        $e = $this->escenarioMemoria();
        $srv = $this->servicio();

        $r = $srv->evaluarRachaPorPartida($this->partida($e, true), true);
        $this->assertNull($r['direccion']);
        $this->assertSame(1, $r['consecutivas']);
        $this->assertSame(3, $r['meta']);

        $r = $srv->evaluarRachaPorPartida($this->partida($e, true), true);
        $this->assertNull($r['direccion']);
        $this->assertSame(2, $r['consecutivas']);

        $r = $srv->evaluarRachaPorPartida($this->partida($e, true), true);
        $this->assertSame('sube', $r['direccion']);
        $this->assertSame(3, $r['consecutivas']);
    }

    public function test_racha_por_partida_baja_a_la_segunda_perdida_seguida_y_una_ganada_corta_la_racha()
    {
        $e = $this->escenarioMemoria();
        $srv = $this->servicio();

        $r = $srv->evaluarRachaPorPartida($this->partida($e, false), false);
        $this->assertNull($r['direccion']);
        $this->assertSame(2, $r['meta']);

        $r = $srv->evaluarRachaPorPartida($this->partida($e, false), false);
        $this->assertSame('baja', $r['direccion']);
        $this->assertSame(2, $r['consecutivas']);

        $r = $srv->evaluarRachaPorPartida($this->partida($e, true), true);
        $this->assertNull($r['direccion']);
        $this->assertSame(1, $r['consecutivas'], 'Una ganada después de perdidas reinicia la cuenta.');
    }

    public function test_racha_por_partida_es_estable_al_reevaluar_una_sesion_anterior()
    {
        $e = $this->escenarioMemoria();
        $srv = $this->servicio();

        $primera = $this->partida($e, true);
        $this->partida($e, true);
        $this->partida($e, true);

        // Evaluar la primera de nuevo no debe "ver" las partidas posteriores.
        $r = $srv->evaluarRachaPorPartida($primera, true);
        $this->assertSame(1, $r['consecutivas']);
        $this->assertNull($r['direccion']);
    }

    public function test_racha_por_partida_ignora_otros_niveles_y_sesiones_no_completadas()
    {
        $e = $this->escenarioMemoria();
        $srv = $this->servicio();

        $this->nuevaSesion($e, ['nivel_id' => $e['nivel2']->id, 'estado' => 'completada', 'nivel_superado' => true]);
        $this->nuevaSesion($e, ['estado' => 'abandonada', 'nivel_superado' => true]);
        $actual = $this->partida($e, true);

        $r = $srv->evaluarRachaPorPartida($actual, true);
        $this->assertSame(1, $r['consecutivas']);
    }

    public function test_buscar_nivel_adyacente_y_parejas_encontradas()
    {
        $e = $this->escenarioMemoria();
        $srv = $this->servicio();

        $this->assertSame($e['nivel2']->id, $srv->buscarNivelAdyacente($e['nivel1'], 'sube')->id);
        $this->assertNull($srv->buscarNivelAdyacente($e['nivel1'], 'baja'), 'El nivel 1 no tiene anterior.');
        $this->assertSame($e['nivel1']->id, $srv->buscarNivelAdyacente($e['nivel2'], 'baja')->id);

        $sesion = $this->nuevaSesion($e, ['aciertos' => 3]);
        $this->assertTrue($srv->todasLasParejasEncontradas($sesion, 3));
        $this->assertFalse($srv->todasLasParejasEncontradas($sesion, 4));
    }
}
