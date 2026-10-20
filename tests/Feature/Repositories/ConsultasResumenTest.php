<?php

namespace Tests\Feature\Repositories;

use App\Models\MensajeRetro;
use App\Models\Nivel;
use App\Models\Estudiante;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\SesionRepository;

/**
 * Consultas agregadas en la Etapa 2 a los repositorios de sesión,
 * ejercicio y estudiante.
 */
class ConsultasResumenTest extends RepositorioTestCase
{
    // ── SesionRepository ────────────────────────────────────────────────

    public function test_sesion_consultas_de_resumen()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $id   = $e['estudiante']->id;

        $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => true,  'puntaje_obtenido' => 40]);
        $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => true,  'puntaje_obtenido' => 30]);   // mismo nivel
        $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => false, 'puntaje_obtenido' => 10, 'nivel_id' => $e['nivel2']->id]);
        $this->nuevaSesion($e, ['estado' => 'abandonada', 'nivel_superado' => false, 'puntaje_obtenido' => 99]);

        $this->assertCount(4, $repo->nivelesJugados($id));
        $this->assertEquals([$e['nivel1']->id, $e['nivel1']->id], $repo->idsNivelesSuperados($id));
        $this->assertSame(1, $repo->contarNivelesSuperados($id), 'Cuenta niveles DISTINTOS.');
        $this->assertSame(3, $repo->contarCompletadas($id));
        $this->assertEquals(80, $repo->puntajeTotalCompletadas($id), 'Las abandonadas no suman puntaje.');
    }

    public function test_sesion_consultas_de_resumen_no_mezclan_estudiantes()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $otro = Estudiante::where('id', '!=', $e['estudiante']->id)->firstOrFail();

        $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => true, 'puntaje_obtenido' => 50]);

        $this->assertSame(0, $repo->contarCompletadas($otro->id));
        $this->assertSame(0, $repo->contarNivelesSuperados($otro->id));
        $this->assertEquals(0, $repo->puntajeTotalCompletadas($otro->id));
        $this->assertSame([], $repo->idsNivelesSuperados($otro->id));
    }

    // ── EjercicioRepository ─────────────────────────────────────────────

    public function test_ejercicio_nivel_adyacente_y_todos_los_niveles()
    {
        $e    = $this->escenario();
        $repo = app(EjercicioRepository::class);

        $this->assertSame($e['nivel2']->id, $repo->nivelAdyacente($e['nivel1'], 1)->id);
        $this->assertSame($e['nivel1']->id, $repo->nivelAdyacente($e['nivel2'], -1)->id);
        $this->assertSame($e['nivel1']->id, $repo->nivelAdyacente($e['nivel1'], 0)->id);
        $this->assertNull($repo->nivelAdyacente($e['nivel1'], 99));

        $todos = $repo->todosLosNiveles();
        $this->assertSame(Nivel::count(), $todos->count());
        $primero = $todos->first();
        foreach (['id', 'juego_id', 'grado', 'numero_nivel', 'desbloqueado_por_defecto'] as $col) {
            $this->assertArrayHasKey($col, $primero->getAttributes());
        }
    }

    // ── EstudianteRepository ────────────────────────────────────────────

    public function test_estudiante_rachas_consultas()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);
        $id   = $e['estudiante']->id;

        $this->assertNull($repo->rachaDelJuego($id, $e['juego']->id));

        $racha = $repo->obtenerOCrearRacha($id, $e['juego']->id);
        $this->assertSame($racha->id, $repo->rachaDelJuego($id, $e['juego']->id)->id);

        $porJuego = $repo->rachasPorJuego($id, [$e['juego']->id, 99999]);
        $this->assertSame([$e['juego']->id], $porJuego->keys()->all());
        $this->assertSame($racha->id, $porJuego[$e['juego']->id]->id);
    }

    public function test_estudiante_progreso_consultas_y_ultimo_mensaje()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);
        $id   = $e['estudiante']->id;
        $g    = $e['nivel1']->grado;

        $this->assertCount(0, $repo->idsNivelesDesbloqueados($id));
        $this->assertCount(0, $repo->progresosDelGrado($id, $g));

        $p2 = $repo->obtenerOCrearProgreso($id, $e['juego']->id, $e['nivel2']->id, $g);
        $p1 = $repo->obtenerOCrearProgreso($id, $e['juego']->id, $e['nivel1']->id, $g);
        $this->assertSame($p1->id, $repo->obtenerOCrearProgreso($id, $e['juego']->id, $e['nivel1']->id, $g)->id);

        $repo->desbloquearNivel($id, $e['juego']->id, $e['nivel2']);
        $this->assertEquals([$e['nivel2']->id], $repo->idsNivelesDesbloqueados($id)->all());

        $filas = $repo->progresosDelGrado($id, $g);
        $this->assertCount(2, $filas);
        $this->assertTrue($filas->first()->relationLoaded('juego'));
        $this->assertTrue($filas->first()->relationLoaded('nivel'));
        $this->assertSame([$e['nivel1']->id, $e['nivel2']->id], $filas->pluck('nivel_id')->all(), 'Ordenado por nivel.');
        $this->assertCount(0, $repo->progresosDelGrado($id, $g + 5));

        $msg = MensajeRetro::first();
        $repo->guardarUltimoMensaje($p1, $msg->id);
        $this->assertSame($msg->id, $p1->fresh()->ultimo_mensaje_id);
    }

    public function test_estudiante_buscar_por_codigo()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $this->assertSame($e['estudiante']->id, $repo->buscarPorCodigo($e['estudiante']->codigo_acceso)->id);
        $this->assertNull($repo->buscarPorCodigo('CODIGO-QUE-NO-EXISTE'));
    }
}
