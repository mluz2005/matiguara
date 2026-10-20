<?php

namespace Tests\Feature\Repositories;

use App\Models\TarjetaNivel;
use App\Repositories\MemoriaRepository;

class MemoriaRepositoryTest extends RepositorioTestCase
{
    public function test_tarjetas_activas_del_nivel_excluye_inactivas_y_de_otros_niveles()
    {
        $e    = $this->escenarioMemoria();
        $repo = app(MemoriaRepository::class);

        $antes = $repo->tarjetasActivasDelNivel($e['nivel1']->id);
        $this->assertGreaterThanOrEqual(2, $antes->count());
        $this->assertTrue($antes->every(fn ($t) => (int) $t->nivel_id === (int) $e['nivel1']->id));

        $una = $antes->first();
        TarjetaNivel::where('id', $una->id)->update(['activo' => false]);

        $despues = $repo->tarjetasActivasDelNivel($e['nivel1']->id);
        $this->assertSame($antes->count() - 1, $despues->count());
        $this->assertFalse($despues->contains('id', $una->id));
    }

    public function test_registrar_movimiento_y_ultimos_movimientos_del_mas_reciente_al_mas_antiguo()
    {
        $e      = $this->escenarioMemoria();
        $repo   = app(MemoriaRepository::class);
        $sesion = $this->nuevaSesion($e);
        [$a, $b] = $e['tarjetas']->take(2)->all();

        $m1 = $repo->registrarMovimiento(['sesion_id' => $sesion->id, 'tarjeta_a_id' => $a->id, 'tarjeta_b_id' => $b->id, 'es_acierto' => false, 'tiempo_respuesta_ms' => 4000]);
        $m2 = $repo->registrarMovimiento(['sesion_id' => $sesion->id, 'tarjeta_a_id' => $a->id, 'tarjeta_b_id' => $a->id, 'es_acierto' => true,  'tiempo_respuesta_ms' => 2000]);
        $m3 = $repo->registrarMovimiento(['sesion_id' => $sesion->id, 'tarjeta_a_id' => $b->id, 'tarjeta_b_id' => $b->id, 'es_acierto' => true,  'tiempo_respuesta_ms' => null]);

        $this->assertDatabaseCount('movimientos_memoria', 3);

        $ultimos = $repo->ultimosMovimientos($sesion, 2);
        $this->assertSame([$m3->id, $m2->id], $ultimos->pluck('id')->all());

        $this->assertCount(3, $repo->ultimosMovimientos($sesion, 10));
    }

    public function test_ultimos_movimientos_no_mezcla_sesiones()
    {
        $e = $this->escenarioMemoria();
        $repo = app(MemoriaRepository::class);
        $s1 = $this->nuevaSesion($e);
        $s2 = $this->nuevaSesion($e);
        [$a] = $e['tarjetas']->all();

        $repo->registrarMovimiento(['sesion_id' => $s1->id, 'tarjeta_a_id' => $a->id, 'tarjeta_b_id' => $a->id, 'es_acierto' => true]);

        $this->assertCount(1, $repo->ultimosMovimientos($s1, 5));
        $this->assertCount(0, $repo->ultimosMovimientos($s2, 5));
    }
}
