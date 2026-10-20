<?php

namespace Tests\Feature\Repositories;

use App\Models\ConfigDocenteJuego;
use App\Models\Docente;
use App\Models\Juego;
use App\Repositories\JuegoRepository;

class JuegoRepositoryTest extends RepositorioTestCase
{
    public function test_ids_deshabilitados_por_docente_y_grado()
    {
        $e       = $this->escenario();
        $repo    = app(JuegoRepository::class);
        $docente = Docente::first();
        $grado   = $e['nivel1']->grado;

        $this->assertSame([], $repo->idsDeshabilitadosPorDocente($docente->id, $grado));

        ConfigDocenteJuego::create(['docente_id' => $docente->id, 'juego_id' => $e['juego']->id, 'grado' => $grado, 'habilitado' => false]);

        $this->assertEquals([$e['juego']->id], $repo->idsDeshabilitadosPorDocente($docente->id, $grado));
        $this->assertSame([], $repo->idsDeshabilitadosPorDocente($docente->id, $grado + 1), 'Otro grado no se ve afectado.');

        ConfigDocenteJuego::where('docente_id', $docente->id)->update(['habilitado' => true]);
        $this->assertSame([], $repo->idsDeshabilitadosPorDocente($docente->id, $grado));
    }

    public function test_activos_excepto_excluye_inactivos_y_los_ids_indicados()
    {
        $e    = $this->escenario();
        $repo = app(JuegoRepository::class);

        $todos = $repo->activosExcepto([]);
        $this->assertTrue($todos->contains('id', $e['juego']->id));
        $this->assertTrue($todos->first()->relationLoaded('tipoMecanica'));

        $this->assertFalse($repo->activosExcepto([$e['juego']->id])->contains('id', $e['juego']->id));

        Juego::where('id', $e['juego']->id)->update(['activo' => false]);
        $this->assertFalse($repo->activosExcepto([])->contains('id', $e['juego']->id));
    }

    public function test_activos_con_niveles_del_grado_filtra_y_ordena_los_niveles()
    {
        $e    = $this->escenario();
        $repo = app(JuegoRepository::class);

        $juegos = $repo->activosConNivelesDelGrado($e['nivel1']->grado);
        $juego  = $juegos->firstWhere('id', $e['juego']->id);

        $this->assertNotNull($juego);
        $numeros = $juego->niveles->pluck('numero_nivel')->all();
        $sorted  = $numeros; sort($sorted);
        $this->assertEquals($sorted, $numeros, 'Los niveles vienen ordenados por número.');
        $this->assertTrue($juego->niveles->every(fn ($n) => (int) $n->grado === (int) $e['nivel1']->grado));
    }

    public function test_hay_niveles_del_grado_solo_para_grados_con_contenido_en_un_juego_activo()
    {
        $repo = app(JuegoRepository::class);

        $this->assertTrue($repo->hayNivelesDelGrado(1));
        $this->assertFalse($repo->hayNivelesDelGrado(2), 'Los sembradores solo cargan niveles de 1.° grado.');

        $n = \App\Models\Nivel::orderBy('id')->firstOrFail()->replicate();
        $n->grado = 2;
        $n->numero_nivel = 1;
        $n->save();
        $this->assertTrue($repo->hayNivelesDelGrado(2));

        \App\Models\Juego::query()->update(['activo' => false]);
        $this->assertFalse($repo->hayNivelesDelGrado(2), 'Un juego inactivo no cuenta.');
    }
}
