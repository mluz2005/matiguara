<?php

namespace Tests\Feature\Repositories;

use App\Models\AsignacionDocente;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Models\Usuario;
use App\Repositories\EstudianteRepository;
use App\Services\MatriculaService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;

/**
 * RF-18 y RF-17 vistos desde las pantallas: el docente cierra el año de un
 * alumno, y el estudiante egresado o con grados anteriores ve lo que corresponde.
 */
class CierreAnioControllerTest extends RepositorioTestCase
{
    private Docente $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente = Docente::where('usuario_id', $usuario->id)->firstOrFail();

        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);

        $this->actingAs($usuario);
    }

    private function ana(): Estudiante
    {
        return Estudiante::where('codigo_acceso', 'SAN-1A-001')->firstOrFail();
    }

    private function cerrar(Estudiante $e)
    {
        return $this->put(route('docente.cuentas.editar', $e->id), ['accion' => 'cerrar_matricula']);
    }

    private function cargarNivelDelGrado(int $grado): void
    {
        $n = Nivel::orderBy('id')->firstOrFail()->replicate();
        $n->grado = $grado;
        $n->numero_nivel = 1;
        $n->desbloqueado_por_defecto = true;
        $n->save();
    }

    public function test_el_docente_cierra_el_año_de_un_alumno_de_tercero_y_queda_la_baja_programada()
    {
        $ana = $this->ana();
        Matricula::where('estudiante_id', $ana->id)->update(['grado' => 3]);

        $r = $this->cerrar($ana);

        $r->assertSessionHas('mensaje');
        $this->assertStringContainsString('egresó de 3.er grado', session('mensaje'));
        $this->assertNull($ana->fresh()->matriculaActiva);
        $esperada = now()->startOfDay()->addMonthsNoOverflow(4)->toDateString();
        $this->assertSame($esperada, $ana->fresh()->fecha_baja_programada->toDateString());
    }

    public function test_el_docente_pasa_de_grado_a_un_alumno_de_primero()
    {
        $this->cargarNivelDelGrado(2);
        $ana = $this->ana();

        $r = $this->cerrar($ana);

        $r->assertSessionHas('mensaje');
        $this->assertSame(2, (int) $ana->fresh()->matriculaActiva->grado);
        $this->assertSame(2, Matricula::where('estudiante_id', $ana->id)->count());
    }

    public function test_el_alumno_que_pasa_de_grado_sigue_en_la_lista_del_docente()
    {
        $this->cargarNivelDelGrado(2);
        $ana = $this->ana();
        $this->cerrar($ana);   // la nueva matrícula puede ser del año siguiente

        $r = $this->get(route('docente.cuentas'));

        $ids = $r->viewData('matriculas')->pluck('estudiante_id')->all();
        $this->assertContains($ana->id, $ids);
    }

    public function test_sin_niveles_del_grado_siguiente_el_pase_se_rechaza_con_un_aviso()
    {
        $ana = $this->ana();

        $r = $this->cerrar($ana);

        $r->assertSessionHasErrors('error');
        $this->assertSame(1, (int) $ana->fresh()->matriculaActiva->grado);
        $this->assertSame(1, Matricula::where('estudiante_id', $ana->id)->count());
    }

    public function test_el_docente_no_puede_cerrar_el_año_de_un_alumno_ajeno()
    {
        $ajeno = Estudiante::where('codigo_acceso', 'SAN-2B-001')->firstOrFail();
        Matricula::where('estudiante_id', $ajeno->id)->update(['grado' => 3]);

        $r = $this->cerrar($ajeno);

        $r->assertSessionHasErrors('error');
        $this->assertNotNull($ajeno->fresh()->matriculaActiva);
        $this->assertNull($ajeno->fresh()->fecha_baja_programada);
    }

    public function test_cadena_completa_cierre_baja_programada_y_tarea_diaria()
    {
        // El año se cerró hace mucho: la baja ya venció cuando corre la tarea.
        $ana = $this->ana();
        $m = Matricula::where('estudiante_id', $ana->id)->firstOrFail();
        $m->update(['grado' => 3]);
        app(MatriculaService::class)->cerrarMatricula($m->fresh(), Carbon::parse('2025-01-15'));

        $this->assertSame('2025-05-15', $ana->fresh()->fecha_baja_programada->toDateString());

        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($ev) => $ev->description === 'egresados-cleanup');
        $evento->run($this->app);

        $this->assertFalse((bool) $ana->fresh()->activo);
        $this->assertTrue((bool) Estudiante::where('codigo_acceso', 'SAN-1A-002')->first()->activo, 'Los demás siguen activos.');
    }

    public function test_el_egresado_no_puede_jugar_pero_ve_su_progreso()
    {
        $e = $this->escenario();
        $ana = $this->ana();
        $m = Matricula::where('estudiante_id', $ana->id)->firstOrFail();
        $m->update(['grado' => 3]);
        app(MatriculaService::class)->cerrarMatricula($m->fresh());
        $this->actingAs($ana->fresh(), 'estudiante');

        $this->get(route('estudiante.juegos'))->assertRedirect(route('estudiante.progreso'));

        $antes = SesionJuego::count();
        $this->post(route('estudiante.sesion.iniciar', [$e['juego']->id, $e['nivel1']->id]))
            ->assertRedirect(route('estudiante.progreso'));
        $this->assertSame($antes, SesionJuego::count(), 'No se crea ninguna sesión de juego.');

        $r = $this->get(route('estudiante.progreso'));
        $r->assertOk();
        $this->assertSame(3, $r->viewData('grado'));
        $this->assertTrue($r->viewData('egresado'));
    }

    public function test_rf17_el_estudiante_ve_grados_anteriores_solo_si_la_institucion_lo_habilita()
    {
        $this->cargarNivelDelGrado(2);
        $ana = $this->ana();
        $this->cerrar($ana);
        $ana = $ana->fresh();
        $this->actingAs($ana, 'estudiante');

        $r = $this->get(route('estudiante.progreso', ['grado' => 1]));
        $this->assertSame(2, $r->viewData('grado'), 'Sin permiso se ignora el grado pedido.');
        $this->assertSame([], $r->viewData('anteriores'));

        $config = app(\App\Repositories\InstitucionRepository::class)->configOCrearPorDefecto($ana->matriculaActiva->institucion);
        $config->update(['permitir_acceso_grados_ant' => true]);

        $r = $this->get(route('estudiante.progreso', ['grado' => 1]));
        $this->assertSame(1, $r->viewData('grado'));
        $this->assertSame([1], $r->viewData('anteriores'));
    }

    public function test_rf17_el_docente_ve_todo_el_historial_del_alumno()
    {
        $this->cargarNivelDelGrado(2);
        $ana = $this->ana();
        $this->cerrar($ana);

        $r = $this->get(route('docente.estudiante.progreso', $ana->id));
        $this->assertSame(2, $r->viewData('grado'));
        $this->assertSame([1, 2], $r->viewData('grados'));

        $r = $this->get(route('docente.estudiante.progreso', ['id' => $ana->id, 'grado' => 1]));
        $this->assertSame(1, $r->viewData('grado'));
    }
}
