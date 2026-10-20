<?php

namespace Tests\Feature\Repositories;

use App\Models\CatalogoLogro;
use App\Repositories\LogroRepository;

class LogroRepositoryTest extends RepositorioTestCase
{
    public function test_otorgar_registra_el_logro_y_deja_de_estar_pendiente()
    {
        $e     = $this->escenario();
        $repo  = app(LogroRepository::class);
        $sesion = $this->nuevaSesion($e);
        $logro = CatalogoLogro::where('activo', true)->firstOrFail();

        $this->assertSame([], $repo->idsObtenidos($e['estudiante']->id));
        $this->assertTrue($repo->pendientes([])->contains('id', $logro->id));

        $repo->otorgar($e['estudiante']->id, $logro->id, $sesion->id);

        $this->assertEquals([$logro->id], $repo->idsObtenidos($e['estudiante']->id));
        $this->assertFalse($repo->pendientes($repo->idsObtenidos($e['estudiante']->id))->contains('id', $logro->id));
        $this->assertDatabaseHas('logros_obtenidos', [
            'estudiante_id' => $e['estudiante']->id,
            'logro_id'      => $logro->id,
            'sesion_id'     => $sesion->id,
        ]);
    }

    public function test_pendientes_solo_incluye_logros_activos()
    {
        $repo  = app(LogroRepository::class);
        $total = $repo->contarActivos();
        $this->assertGreaterThan(1, $total);

        $uno = CatalogoLogro::where('activo', true)->firstOrFail();
        $uno->update(['activo' => false]);

        $this->assertSame($total - 1, $repo->contarActivos());
        $this->assertFalse($repo->pendientes([])->contains('id', $uno->id));
        $this->assertCount($total - 1, $repo->pendientes([]));
    }

    public function test_obtenidos_de_trae_el_catalogo_y_obtenidos_por_ids_filtra()
    {
        $e      = $this->escenario();
        $repo   = app(LogroRepository::class);
        $sesion = $this->nuevaSesion($e);
        [$a, $b] = CatalogoLogro::where('activo', true)->take(2)->get()->all();

        $repo->otorgar($e['estudiante']->id, $a->id, $sesion->id);
        $repo->otorgar($e['estudiante']->id, $b->id, $sesion->id);

        $todos = $repo->obtenidosDe($e['estudiante']->id);
        $this->assertCount(2, $todos);
        $this->assertTrue($todos->first()->relationLoaded('logro'));

        $solo = $repo->obtenidosPorIds($e['estudiante']->id, [$a->id]);
        $this->assertCount(1, $solo);
        $this->assertEquals($a->id, $solo->first()->logro_id);
        $this->assertCount(0, $repo->obtenidosPorIds($e['estudiante']->id, []));
    }
}
