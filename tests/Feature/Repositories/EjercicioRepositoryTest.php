<?php

namespace Tests\Feature\Repositories;

use App\Repositories\EjercicioRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EjercicioRepositoryTest extends RepositorioTestCase
{
    public function test_buscar_devuelve_el_ejercicio_o_falla()
    {
        $e    = $this->escenario();
        $repo = app(EjercicioRepository::class);

        $this->assertSame($e['ejercicios'][0]->id, $repo->buscar($e['ejercicios'][0]->id)->id);

        $this->expectException(ModelNotFoundException::class);
        $repo->buscar(999999);
    }

    public function test_contar_del_nivel()
    {
        $e = $this->escenario();

        $this->assertSame(
            $e['ejercicios']->count(),
            app(EjercicioRepository::class)->contarDelNivel($e['nivel1']->id)
        );
        $this->assertSame(0, app(EjercicioRepository::class)->contarDelNivel(999999));
    }

    public function test_siguiente_sin_semilla_respeta_el_orden_y_excluye_respondidos()
    {
        $e    = $this->escenario();
        $repo = app(EjercicioRepository::class);
        $orden = $e['ejercicios']->sortBy('orden')->values();

        $this->assertSame($orden[0]->id, $repo->siguienteNoRespondido($e['nivel1']->id, [])->id);
        $this->assertSame($orden[1]->id, $repo->siguienteNoRespondido($e['nivel1']->id, [$orden[0]->id])->id);
        $this->assertNull($repo->siguienteNoRespondido($e['nivel1']->id, $orden->pluck('id')->all()));
    }

    public function test_siguiente_con_semilla_es_estable_y_nunca_devuelve_un_excluido()
    {
        $e    = $this->escenario();
        $repo = app(EjercicioRepository::class);
        $ids  = $e['ejercicios']->pluck('id')->all();

        $primero  = $repo->siguienteNoRespondido($e['nivel1']->id, [], '123');
        $repetido = $repo->siguienteNoRespondido($e['nivel1']->id, [], '123');
        $this->assertSame($primero->id, $repetido->id, 'Con la misma semilla el orden debe ser estable.');
        $this->assertContains($primero->id, $ids);

        $siguiente = $repo->siguienteNoRespondido($e['nivel1']->id, [$primero->id], '123');
        $this->assertNotSame($primero->id, $siguiente->id);

        $this->assertNull($repo->siguienteNoRespondido($e['nivel1']->id, $ids, '123'));
    }

    public function test_nivel_anterior()
    {
        $e    = $this->escenario();
        $repo = app(EjercicioRepository::class);

        $this->assertSame($e['nivel1']->id, $repo->nivelAnterior($e['nivel2'])->id);
        $this->assertNull($repo->nivelAnterior($e['nivel1']));
    }
}
