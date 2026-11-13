<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Usuario;

/**
 * Cuentas de alumnos: formato único de nombre, aviso de posible duplicado al crear
 * y eliminación (solo el administrador, solo cuentas sin actividad).
 */
class CuentasDuplicadosTest extends RepositorioTestCase
{
    private Usuario $docenteUsuario;
    private Docente $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docenteUsuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente        = Docente::where('usuario_id', $this->docenteUsuario->id)->firstOrFail();

        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        \App\Models\AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);
    }

    private function comoDocente(): void { $this->actingAs($this->docenteUsuario); }
    private function comoAdmin(): void   { $this->actingAs(Usuario::where('email', 'admin@unae.edu.py')->firstOrFail()); }

    private function crear(array $extra = [])
    {
        return $this->post(route('docente.cuentas.crear'), $extra + [
            'nombre' => 'ANA MARÍA', 'apellido' => 'zárate', 'pin' => '4821', 'pin_confirmation' => '4821',
        ]);
    }

    // ── Formato de nombres ───────────────────────────────────────────────

    public function test_crear_cuenta_guarda_nombre_y_apellido_capitalizados()
    {
        $this->comoDocente();

        $this->crear()->assertRedirect(route('docente.cuentas'));

        $e = Estudiante::where('apellido', 'Zárate')->firstOrFail();
        $this->assertSame('Ana María', $e->nombre);
    }

    public function test_editar_cuenta_tambien_unifica_el_formato()
    {
        $this->comoDocente();
        $propio = Estudiante::findOrFail(Matricula::where('docente_id', $this->docente->id)->orderBy('id')->value('estudiante_id'));

        $this->put(route('docente.cuentas.editar', $propio->id), [
            'accion' => 'guardar', 'nombre' => 'JUAN  CARLOS', 'apellido' => 'de la CRUZ',
        ])->assertSessionHas('mensaje');

        $propio->refresh();
        $this->assertSame('Juan Carlos', $propio->nombre);
        $this->assertSame('De la Cruz', $propio->apellido);
    }

    public function test_el_administrador_tambien_crea_docentes_con_formato()
    {
        $this->comoAdmin();
        $inst = \App\Models\Institucion::firstOrFail();

        $this->post(route('admin.docentes.crear'), [
            'nombre' => 'LAURA', 'apellido' => 'vega', 'email' => 'laura.vega@escuela.edu.py',
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1',
            'institucion_id' => $inst->id, 'grado' => 1, 'turno' => 'mañana',
        ])->assertRedirect(route('admin.docentes'));

        $u = Usuario::where('email', 'laura.vega@escuela.edu.py')->firstOrFail();
        $this->assertSame('Laura', $u->nombre);
        $this->assertSame('Vega', $u->apellido);
    }

    // ── Aviso de duplicado ───────────────────────────────────────────────

    public function test_un_alumno_repetido_no_se_crea_y_avisa()
    {
        $this->comoDocente();
        $this->crear()->assertRedirect(route('docente.cuentas'));
        $antes = Estudiante::count();

        // Mismo nombre escrito distinto (mayúsculas, tildes y espacios de más)
        $r = $this->crear(['nombre' => ' ana   maria ', 'apellido' => 'ZARATE']);

        $r->assertSessionHas('duplicados');
        $this->assertCount(1, session('duplicados'));
        $this->assertSame($antes, Estudiante::count(), 'No se creó otra cuenta.');
    }

    public function test_confirmando_que_es_otro_nino_se_crea_igual()
    {
        $this->comoDocente();
        $this->crear();
        $antes = Estudiante::count();

        $this->crear(['confirmar_duplicado' => '1'])
            ->assertRedirect(route('docente.cuentas'))
            ->assertSessionMissing('duplicados');

        $this->assertSame($antes + 1, Estudiante::count());
    }

    public function test_un_duplicado_inactivo_no_genera_aviso()
    {
        $this->comoDocente();
        $this->crear();
        Estudiante::where('apellido', 'Zárate')->update(['activo' => false]);
        $antes = Estudiante::count();

        $this->crear()->assertSessionMissing('duplicados');

        $this->assertSame($antes + 1, Estudiante::count());
    }

    public function test_la_pantalla_muestra_el_aviso_de_duplicado_con_la_casilla()
    {
        $this->comoDocente();
        $this->crear();

        $this->from(route('docente.cuentas'))->followingRedirects()->crear()
            ->assertOk()
            ->assertSee('Ya hay un alumno con ese nombre')
            ->assertSee('name="confirmar_duplicado"', false);
    }

    // ── Eliminar (administrador, sin actividad) ──────────────────────────

    public function test_un_docente_no_entra_al_listado_de_alumnos_del_administrador()
    {
        $this->comoDocente();

        $this->get(route('admin.alumnos'))->assertRedirect(route('login'));
    }

    public function test_el_administrador_ve_el_listado_y_marca_los_repetidos()
    {
        $this->comoDocente();
        $this->crear();
        $this->crear(['confirmar_duplicado' => '1']);

        $this->comoAdmin();
        $this->get(route('admin.alumnos'))
            ->assertOk()
            ->assertSee('Zárate, Ana María')
            ->assertSee('¿Repetido?');
    }

    public function test_el_administrador_elimina_una_cuenta_sin_actividad()
    {
        $this->comoDocente();
        $this->crear();
        $e = Estudiante::where('apellido', 'Zárate')->firstOrFail();

        $this->comoAdmin();
        $this->delete(route('admin.alumnos.eliminar', $e->id))
            ->assertRedirect(route('admin.alumnos'))
            ->assertSessionHas('mensaje');

        $this->assertNull(Estudiante::find($e->id));
        $this->assertFalse(Matricula::where('estudiante_id', $e->id)->exists());
    }

    public function test_una_cuenta_con_partidas_no_se_puede_eliminar()
    {
        $esc = $this->escenario();
        $this->nuevaSesion($esc, ['estado' => 'completada']);
        $conActividad = $esc['estudiante'];

        $this->comoAdmin();
        $this->delete(route('admin.alumnos.eliminar', $conActividad->id))
            ->assertRedirect(route('admin.alumnos'))
            ->assertSessionHasErrors('error');

        $this->assertNotNull(Estudiante::find($conActividad->id));
    }

    public function test_un_docente_no_puede_eliminar_alumnos()
    {
        $this->comoDocente();
        $this->crear();
        $e = Estudiante::where('apellido', 'Zárate')->firstOrFail();

        $this->delete(route('admin.alumnos.eliminar', $e->id))->assertRedirect(route('login'));

        $this->assertNotNull(Estudiante::find($e->id));
    }
}
