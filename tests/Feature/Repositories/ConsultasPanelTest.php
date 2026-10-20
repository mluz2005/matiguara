<?php

namespace Tests\Feature\Repositories;

use App\Models\CatalogoLogro;
use App\Models\Diagnostico;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Padre;
use App\Models\Usuario;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\LogroRepository;
use App\Repositories\PadreRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRepository;

/**
 * Consultas agregadas en la Etapa 3a para los paneles de docente y familia.
 */
class ConsultasPanelTest extends RepositorioTestCase
{
    // ── SesionRepository ────────────────────────────────────────────────

    public function test_resumen_completadas_de_uno_o_varios_estudiantes()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $otro = Estudiante::where('id', '!=', $e['estudiante']->id)->firstOrFail();

        $this->nuevaSesion($e, ['estado' => 'completada', 'aciertos' => 3, 'errores' => 1]);
        $this->nuevaSesion($e, ['estado' => 'completada', 'aciertos' => 5, 'errores' => 0]);
        $this->nuevaSesion($e, ['estado' => 'abandonada', 'aciertos' => 9, 'errores' => 9]);
        $this->nuevaSesion(['estudiante' => $otro] + $e, ['estado' => 'completada', 'aciertos' => 1, 'errores' => 1]);

        $uno = $repo->resumenCompletadas($e['estudiante']->id);
        $this->assertSame(['sesiones' => 2, 'aciertos' => 8, 'respuestas' => 9], $uno);

        $ambos = $repo->resumenCompletadas([$e['estudiante']->id, $otro->id]);
        $this->assertSame(['sesiones' => 3, 'aciertos' => 9, 'respuestas' => 11], $ambos);

        $this->assertSame(['sesiones' => 0, 'aciertos' => 0, 'respuestas' => 0], $repo->resumenCompletadas(collect()));
        $this->assertSame(['sesiones' => 0, 'aciertos' => 0, 'respuestas' => 0], $repo->resumenCompletadas(999999));
    }

    public function test_ultima_y_recientes_completadas()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $id   = $e['estudiante']->id;

        $this->assertNull($repo->ultimaCompletada($id));

        $a = $this->nuevaSesion($e, ['estado' => 'completada', 'fecha_inicio' => now()->subDays(3)]);
        $b = $this->nuevaSesion($e, ['estado' => 'completada', 'fecha_inicio' => now()->subDay()]);
        $this->nuevaSesion($e, ['estado' => 'en_curso',   'fecha_inicio' => now()]);

        $this->assertSame($b->id, $repo->ultimaCompletada($id)->id);

        $rec = $repo->recientesCompletadas($id, 10);
        $this->assertSame([$b->id, $a->id], $rec->pluck('id')->all());
        $this->assertTrue($rec->first()->relationLoaded('juego'));
        $this->assertTrue($rec->first()->relationLoaded('nivel'));
        $this->assertCount(1, $repo->recientesCompletadas($id, 1));
    }

    public function test_contar_estudiantes_activos_hoy()
    {
        $e    = $this->escenario();
        $repo = app(SesionRepository::class);
        $otro = Estudiante::where('id', '!=', $e['estudiante']->id)->firstOrFail();
        $ids  = [$e['estudiante']->id, $otro->id];

        $this->assertSame(0, $repo->contarEstudiantesActivosHoy($ids));

        $this->nuevaSesion($e, ['fecha_inicio' => now()]);
        $this->nuevaSesion($e, ['fecha_inicio' => now()]);                 // mismo estudiante: cuenta una vez
        $this->nuevaSesion(['estudiante' => $otro] + $e, ['fecha_inicio' => now()->subDays(2)]);

        $this->assertSame(1, $repo->contarEstudiantesActivosHoy($ids));
    }

    // ── LogroRepository ─────────────────────────────────────────────────

    public function test_logros_recientes_ordenados_y_con_limite()
    {
        $e      = $this->escenario();
        $repo   = app(LogroRepository::class);
        $sesion = $this->nuevaSesion($e);
        $catalogo = CatalogoLogro::where('activo', true)->take(3)->get();

        foreach ($catalogo as $i => $logro) {
            $repo->otorgar($e['estudiante']->id, $logro->id, $sesion->id);
            \App\Models\LogroObtenido::where('logro_id', $logro->id)
                ->where('estudiante_id', $e['estudiante']->id)
                ->update(['fecha_obtenido' => now()->subDays(10 - $i)]);
        }

        $todos = $repo->recientesDe($e['estudiante']->id);
        $this->assertCount(3, $todos);
        $this->assertSame($catalogo->last()->id, $todos->first()->logro_id, 'El más reciente primero.');
        $this->assertTrue($todos->first()->relationLoaded('logro'));

        $this->assertCount(2, $repo->recientesDe($e['estudiante']->id, 2));
        $this->assertCount(0, $repo->recientesDe(999999));
    }

    // ── DiagnosticoRepository ───────────────────────────────────────────

    private function diag(array $e, string $estado, int $diasAtras): Diagnostico
    {
        $d = app(DiagnosticoRepository::class)->crear([
            'estudiante_id'    => $e['estudiante']->id,
            'docente_id'       => \App\Models\Docente::first()->id,
            'fecha_generacion' => now()->subDays($diasAtras),
            'estado'           => $estado,
            'id_anonimizado'   => $e['estudiante']->id_anonimizado,
        ]);
        return $d;
    }

    public function test_diagnosticos_listados_historial_y_exitosos()
    {
        $e    = $this->escenario();
        $repo = app(DiagnosticoRepository::class);
        $id   = $e['estudiante']->id;

        $d1 = $this->diag($e, 'exitoso', 5);
        $d2 = $this->diag($e, 'fallido', 3);
        $d3 = $this->diag($e, 'exitoso', 1);

        $this->assertSame($d2->id, $repo->buscar($d2->id)->id);

        $this->assertSame([$d3->id, $d2->id, $d1->id], $repo->delEstudiante($id)->pluck('id')->all());
        $this->assertSame(3, $repo->delEstudiantePaginado($id, 2)->total());
        $this->assertCount(2, $repo->delEstudiantePaginado($id, 2)->items());

        $this->assertSame([$d2->id, $d1->id], $repo->anterioresA($id, $d3->id, 4)->pluck('id')->all());
        $this->assertCount(1, $repo->anterioresA($id, $d3->id, 1));

        $this->assertSame([$d3->id, $d1->id], $repo->exitososDelEstudiante($id, 3)->pluck('id')->all());
        $this->assertSame($d3->id, $repo->ultimoExitoso($id)->id);
        $this->assertNull($repo->ultimoExitoso(999999));
    }

    // ── EstudianteRepository ────────────────────────────────────────────

    public function test_estudiante_buscar_o_fallar()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $this->assertSame($e['estudiante']->id, $repo->buscarOFallar($e['estudiante']->id)->id);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $repo->buscarOFallar(999999);
    }

    public function test_estudiante_crear_actualizar_contar_y_codigos()
    {
        $repo  = app(EstudianteRepository::class);
        $antes = $repo->contar();

        $nuevo = $repo->crear([
            'nombre' => 'Ana', 'apellido' => 'Prueba', 'codigo_acceso' => 'TST-1A-099',
            'pin_hash' => Estudiante::hashearPin('1234'),
            'id_anonimizado' => Estudiante::generarIdAnonimizado(), 'activo' => true,
        ]);

        $this->assertSame($antes + 1, $repo->contar());
        $this->assertTrue($repo->existeCodigo('TST-1A-099'));
        $this->assertFalse($repo->existeCodigo('TST-1A-100'));
        $this->assertTrue($repo->codigosDeAcceso()->contains('TST-1A-099'));

        $repo->actualizar($nuevo, ['nombre' => 'Anita', 'activo' => false]);
        $this->assertSame('Anita', $nuevo->fresh()->nombre);
        $this->assertFalse((bool) $nuevo->fresh()->activo);
    }

    // ── JuegoRepository ─────────────────────────────────────────────────

    public function test_juego_activos_ids_y_niveles_del_grado()
    {
        $e    = $this->escenario();
        $repo = app(JuegoRepository::class);

        $this->assertTrue($repo->activos()->contains('id', $e['juego']->id));
        $this->assertTrue($repo->activosConMecanica()->first()->relationLoaded('tipoMecanica'));
        $this->assertTrue($repo->idsActivos()->contains($e['juego']->id));

        Juego::where('id', $e['juego']->id)->update(['activo' => false]);
        $this->assertFalse($repo->activos()->contains('id', $e['juego']->id));
        $this->assertFalse($repo->idsActivos()->contains($e['juego']->id));

        $niveles = $repo->nivelesDelGrado($e['juego'], (int) $e['nivel1']->grado);
        $this->assertTrue($niveles->contains('id', $e['nivel1']->id));
        $this->assertTrue($niveles->every(fn ($n) => (int) $n->grado === (int) $e['nivel1']->grado));
    }

    // ── UsuarioRepository / PadreRepository ─────────────────────────────

    public function test_usuario_correo_de_personal_y_cuentas_de_familia()
    {
        $repo = app(UsuarioRepository::class);

        $this->assertTrue($repo->existeCorreoDePersonal('docente1@unae.edu.py'));
        $this->assertTrue($repo->existeCorreoDePersonal('admin@unae.edu.py'));
        $this->assertFalse($repo->existeCorreoDePersonal('padre@correo.com'), 'Una cuenta de familia no es personal.');
        $this->assertFalse($repo->existeCorreoDePersonal('nadie@correo.com'));

        $ids = $repo->idsDeFamiliaPorCorreo('padre@correo.com');
        $this->assertCount(1, $ids);
        $this->assertSame(Usuario::where('email', 'padre@correo.com')->value('id'), $ids->first());
        $this->assertCount(0, $repo->idsDeFamiliaPorCorreo('docente1@unae.edu.py'));
    }

    public function test_padre_eliminar_vinculos_solo_de_las_cuentas_indicadas()
    {
        $repo  = app(PadreRepository::class);
        $est   = Estudiante::first();
        $extra = Usuario::create([
            'nombre' => 'Fam', 'apellido' => 'Dos', 'email' => 'otra@familia.com',
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => true,
        ]);
        Padre::create(['usuario_id' => $extra->id, 'estudiante_id' => $est->id, 'relacion' => 'tutor']);
        $antes = Padre::where('estudiante_id', $est->id)->count();

        $repo->eliminarVinculos($est->id, [$extra->id]);

        $this->assertSame($antes - 1, Padre::where('estudiante_id', $est->id)->count());
        $this->assertFalse(Padre::where('usuario_id', $extra->id)->exists());
    }
}
