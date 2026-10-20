<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Padre;
use App\Models\Usuario;
use App\Models\ConfigDocenteJuego;
use App\Models\Juego;
use App\Models\ProgresoEstudiante;

/**
 * Pruebas de comportamiento del panel docente (rutas /docente/...). Sirven
 * para comprobar que, al mover las consultas a los repositorios, las
 * pantallas y acciones siguen respondiendo igual.
 */
class DocenteControllerTest extends RepositorioTestCase
{
    private Usuario $usuario;
    private Docente $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente = Docente::where('usuario_id', $this->usuario->id)->firstOrFail();

        // Las matrículas sembradas son de 2026; el panel filtra por el año en curso.
        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        \App\Models\AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);

        $this->actingAs($this->usuario);
    }

    private function alumnoPropio(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    private function alumnoAjeno(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', '!=', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    public function test_panel_muestra_metricas_de_la_clase()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();
        $this->nuevaSesion(['estudiante' => $propio] + $e, ['estado' => 'completada', 'aciertos' => 3, 'errores' => 1, 'fecha_inicio' => now()]);

        $r = $this->get(route('docente.panel'));

        $r->assertOk();
        $m = $r->viewData('metricas');
        $this->assertSame(3, $m['total_estudiantes']);
        $this->assertSame(1, $m['total_sesiones']);
        $this->assertEquals(75, $m['pct_global']);
        $this->assertSame(1, $m['activos_hoy']);
    }

    public function test_lista_de_estudiantes_con_porcentaje_de_aciertos()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();
        $this->nuevaSesion(['estudiante' => $propio] + $e, ['estado' => 'completada', 'aciertos' => 3, 'errores' => 1]);

        $r = $this->get(route('docente.estudiantes'));

        $r->assertOk();
        $lista = $r->viewData('estudiantes');
        $this->assertCount(3, $lista);
        $fila = $lista->firstWhere('id', $propio->id);
        $this->assertSame(1, $fila->sesiones_total);
        $this->assertEquals(75, $fila->porcentaje_ok);
        $otra = $lista->firstWhere('id', '!=', $propio->id);
        $this->assertSame(0, $otra->sesiones_total);
        $this->assertEquals(0, $otra->porcentaje_ok);
    }

    public function test_progreso_de_un_alumno_propio_y_bloqueo_de_uno_ajeno()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();
        $this->nuevaSesion(['estudiante' => $propio] + $e, ['estado' => 'completada']);

        $r = $this->get(route('docente.estudiante.progreso', $propio->id));
        $r->assertOk();
        $this->assertCount(1, $r->viewData('sesiones'));
        $this->assertTrue($r->viewData('logros')->isEmpty());

        $this->get(route('docente.estudiante.progreso', $this->alumnoAjeno()->id))
            ->assertRedirect(route('docente.estudiantes'));
    }

    public function test_pantalla_de_diagnostico_e_historial()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();

        $r = $this->get(route('docente.estudiante.diagnostico', $propio->id));
        $r->assertOk();
        $this->assertSame(0, $r->viewData('totalSesiones'));
        $this->assertNull($r->viewData('ultimaSesion'));
        $this->assertTrue($r->viewData('juegos')->contains('id', $e['juego']->id));

        $this->get(route('docente.estudiante.diagnosticos', $propio->id))->assertOk();

        $this->get(route('docente.estudiante.diagnostico', $this->alumnoAjeno()->id))
            ->assertRedirect(route('docente.estudiantes'));
    }

    public function test_generar_diagnostico_sin_sesiones_completadas_es_rechazado()
    {
        $propio = $this->alumnoPropio();

        $this->post(route('docente.estudiante.generar-diagnostico', $propio->id), [])
            ->assertRedirect(route('docente.estudiante.diagnostico', $propio->id))
            ->assertSessionHasErrors('error');
    }

    public function test_ver_diagnostico_existente_y_ajeno_al_alumno()
    {
        $propio = $this->alumnoPropio();
        $otro   = Estudiante::where('id', '!=', $propio->id)->firstOrFail();

        $d = \App\Models\Diagnostico::create([
            'estudiante_id' => $propio->id, 'docente_id' => $this->docente->id,
            'id_anonimizado' => $propio->id_anonimizado, 'fecha_generacion' => now(), 'estado' => 'exitoso',
        ]);

        $this->get(route('docente.estudiante.ver-diagnostico', [$propio->id, $d->id]))->assertOk();
        $this->get(route('docente.estudiante.ver-diagnostico', [$otro->id, $d->id]))
            ->assertRedirect(route('docente.estudiante.diagnostico', $otro->id));
    }

    public function test_crear_cuenta_genera_codigo_correlativo_y_matricula()
    {
        $datos = ['nombre' => 'Lucila', 'apellido' => 'Gómez', 'pin' => '4321', 'pin_confirmation' => '4321', 'email_tutor' => 'Familia.Gomez@Correo.com'];

        $this->post(route('docente.cuentas.crear'), $datos)->assertRedirect(route('docente.cuentas'));
        $this->post(route('docente.cuentas.crear'), ['nombre' => 'Mateo', 'apellido' => 'Gómez'] + $datos)->assertRedirect(route('docente.cuentas'));

        $lucila = Estudiante::where('nombre', 'Lucila')->where('apellido', 'Gómez')->firstOrFail();
        $mateo = Estudiante::where('nombre', 'Mateo')->where('apellido', 'Gómez')->firstOrFail();

        $this->assertMatchesRegularExpression('/^EEB-1M-\d{3}$/', $lucila->codigo_acceso);
        $this->assertNotSame($lucila->codigo_acceso, $mateo->codigo_acceso);
        $this->assertSame('familia.gomez@correo.com', $lucila->email_tutor, 'El correo se guarda en minúsculas.');
        $this->assertTrue($lucila->verificarPin('4321'));

        $mat = Matricula::where('estudiante_id', $lucila->id)->firstOrFail();
        $this->assertSame($this->docente->id, (int) $mat->docente_id);
        $this->assertSame((int) date('Y'), (int) $mat->anio_lectivo);
        $this->assertTrue((bool) $mat->activo);
    }

    public function test_crear_cuenta_rechaza_correo_de_personal()
    {
        $this->post(route('docente.cuentas.crear'), [
            'nombre' => 'X', 'apellido' => 'Y', 'pin' => '1111', 'pin_confirmation' => '1111',
            'email_tutor' => 'docente2@unae.edu.py',
        ])->assertSessionHasErrors('email_tutor');

        $this->assertFalse(Estudiante::where('nombre', 'X')->exists());
    }

    public function test_lista_de_cuentas()
    {
        $this->get(route('docente.cuentas'))->assertOk()->assertViewHas('matriculas', fn ($m) => $m->count() === 3);
    }

    public function test_editar_cuenta_guardar_pin_desactivar_y_reactivar()
    {
        $propio = $this->alumnoPropio();

        $this->put(route('docente.cuentas.editar', $propio->id), [
            'accion' => 'guardar', 'nombre' => ' Nuevo ', 'apellido' => 'Nombre', 'nuevo_pin' => '9876',
        ])->assertSessionHas('mensaje');
        $propio->refresh();
        $this->assertSame('Nuevo', $propio->nombre);
        $this->assertTrue($propio->verificarPin('9876'));

        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'reset_pin', 'nuevo_pin' => '1357'])
            ->assertSessionHas('ficha_id', $propio->id);
        $this->assertTrue($propio->fresh()->verificarPin('1357'));

        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'desactivar']);
        $this->assertFalse((bool) $propio->fresh()->activo);
        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'reactivar']);
        $this->assertTrue((bool) $propio->fresh()->activo);
    }

    public function test_editar_cuenta_de_un_alumno_ajeno_es_rechazado()
    {
        $ajeno = $this->alumnoAjeno();

        $this->put(route('docente.cuentas.editar', $ajeno->id), ['accion' => 'desactivar'])
            ->assertSessionHasErrors('error');
        $this->assertTrue((bool) $ajeno->fresh()->activo);
    }

    public function test_cambiar_el_correo_del_tutor_desvincula_a_la_familia_anterior()
    {
        $propio = $this->alumnoPropio();
        $familia = Usuario::create([
            'nombre' => 'Fam', 'apellido' => 'Uno', 'email' => 'vieja@familia.com',
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => true,
        ]);
        $propio->update(['email_tutor' => 'vieja@familia.com']);
        Padre::create(['usuario_id' => $familia->id, 'estudiante_id' => $propio->id, 'relacion' => 'tutor']);

        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'cambiar_correo', 'nuevo_correo' => 'Nueva@Familia.com']);

        $this->assertSame('nueva@familia.com', $propio->fresh()->email_tutor);
        $this->assertFalse(Padre::where('usuario_id', $familia->id)->where('estudiante_id', $propio->id)->exists());

        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'quitar_correo']);
        $this->assertNull($propio->fresh()->email_tutor);
    }

    public function test_cambiar_correo_a_uno_de_personal_es_rechazado()
    {
        $propio = $this->alumnoPropio();

        $this->put(route('docente.cuentas.editar', $propio->id), ['accion' => 'cambiar_correo', 'nuevo_correo' => 'admin@unae.edu.py'])
            ->assertSessionHasErrors('nuevo_correo');
    }

    public function test_fichas_individual_y_del_grado_con_control_de_acceso()
    {
        $propio = $this->alumnoPropio();

        $r = $this->get(route('docente.cuentas.ficha', $propio->id));
        $r->assertOk();
        $this->assertSame($propio->codigo_acceso, $r->viewData('fichas')[0]['codigo']);
        $this->assertNull($r->viewData('fichas')[0]['pin']);

        $this->get(route('docente.cuentas.ficha', $this->alumnoAjeno()->id))
            ->assertRedirect(route('docente.cuentas'));

        $todas = $this->get(route('docente.cuentas.fichas'));
        $todas->assertOk();
        $this->assertCount(3, $todas->viewData('fichas'));

        Matricula::where('docente_id', $this->docente->id)->update(['activo' => false]);
        $this->get(route('docente.cuentas.fichas'))->assertRedirect(route('docente.cuentas'));
    }

    public function test_configuracion_de_juegos_se_guarda_para_todos_los_juegos_activos()
    {
        $e = $this->escenario();
        $this->get(route('docente.juegos'))->assertOk()->assertViewHas('juegos');

        $this->post(route('docente.juegos.guardar'), ['juegos' => [$e['juego']->id]])->assertSessionHas('mensaje');

        $filas = ConfigDocenteJuego::where('docente_id', $this->docente->id)->get();
        $this->assertSame(Juego::activos()->count(), $filas->count());
        $this->assertTrue((bool) $filas->firstWhere('juego_id', $e['juego']->id)->habilitado);
        $this->assertTrue($filas->where('juego_id', '!=', $e['juego']->id)->every(fn ($f) => !$f->habilitado));

        // Repetir no duplica filas
        $this->post(route('docente.juegos.guardar'), ['juegos' => []]);
        $this->assertSame(Juego::activos()->count(), ConfigDocenteJuego::where('docente_id', $this->docente->id)->count());
    }

    // ── DocenteNivelesController ────────────────────────────────────────

    public function test_abrir_un_nivel_a_un_alumno_propio()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();

        $this->post(route('docente.estudiante.nivel.abrir', [$propio->id, $e['nivel2']->id]))
            ->assertRedirect(route('docente.estudiante.progreso', $propio->id))
            ->assertSessionHas('niveles_msg');

        $this->assertTrue(ProgresoEstudiante::where('estudiante_id', $propio->id)
            ->where('nivel_id', $e['nivel2']->id)->where('nivel_desbloqueado', true)->exists());
    }

    public function test_abrir_nivel_de_otro_grado_o_a_un_alumno_ajeno_no_se_permite()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();

        $otroGrado = \App\Models\Nivel::where('juego_id', $e['juego']->id)->where('grado', '!=', $e['nivel1']->grado)->first();
        if ($otroGrado) {
            $this->post(route('docente.estudiante.nivel.abrir', [$propio->id, $otroGrado->id]))
                ->assertSessionHas('niveles_error');
            $this->assertFalse(ProgresoEstudiante::where('estudiante_id', $propio->id)->where('nivel_id', $otroGrado->id)->exists());
        }

        $this->post(route('docente.estudiante.nivel.abrir', [$this->alumnoAjeno()->id, $e['nivel2']->id]))
            ->assertForbidden();
    }

    public function test_abrir_todos_los_niveles_del_juego_para_el_grado_del_alumno()
    {
        $e = $this->escenario();
        $propio = $this->alumnoPropio();
        $grado = (int) Matricula::where('estudiante_id', $propio->id)->value('grado');
        $esperados = \App\Models\Nivel::where('juego_id', $e['juego']->id)->where('grado', $grado)->count();

        $this->post(route('docente.estudiante.juego.abrir-todos', [$propio->id, $e['juego']->id]))
            ->assertSessionHas('niveles_msg');

        $this->assertSame($esperados, ProgresoEstudiante::where('estudiante_id', $propio->id)
            ->where('juego_id', $e['juego']->id)->where('nivel_desbloqueado', true)->count());

        $this->post(route('docente.estudiante.juego.abrir-todos', [$this->alumnoAjeno()->id, $e['juego']->id]))
            ->assertForbidden();
    }
}