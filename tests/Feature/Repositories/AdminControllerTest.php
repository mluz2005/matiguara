<?php

namespace Tests\Feature\Repositories;

use App\Models\AsignacionDocente;
use App\Models\ConfigInstitucion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Institucion;
use App\Models\MensajeRetro;
use App\Models\Usuario;

/**
 * Pruebas de comportamiento del panel de administración (rutas /admin/...).
 */
class AdminControllerTest extends RepositorioTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::where('email', 'admin@unae.edu.py')->firstOrFail());
    }

    public function test_un_docente_no_entra_al_panel_de_administracion()
    {
        $this->actingAs(Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail());

        $this->get(route('admin.panel'))->assertRedirect(route('login'));
    }

    public function test_panel_muestra_los_contadores()
    {
        $e = $this->escenario();
        $this->nuevaSesion($e, ['estado' => 'completada']);

        $stats = $this->get(route('admin.panel'))->assertOk()->viewData('stats');

        $this->assertSame(Institucion::where('activo', true)->count(), $stats['instituciones']);
        $this->assertSame(Docente::where('activo', true)->count(), $stats['docentes']);
        $this->assertSame(Estudiante::where('activo', true)->count(), $stats['estudiantes']);
        $this->assertSame(1, $stats['sesiones']);
        $this->assertSame(0, $stats['diagnosticos']);
    }

    public function test_listado_y_formulario_de_alta_de_instituciones()
    {
        $r = $this->get(route('admin.instituciones.index'))->assertOk();
        $this->assertSame('lista', $r->viewData('modo'));
        $this->assertSame(Institucion::count(), $r->viewData('instituciones')->total());

        $this->assertSame('crear', $this->get(route('admin.instituciones.create'))->assertOk()->viewData('modo'));
    }

    public function test_crear_institucion_crea_tambien_su_configuracion_por_defecto()
    {
        $this->post(route('admin.instituciones.store'), [
            'nombre' => 'Escuela Prueba', 'codigo' => 'EEB-PRU-001', 'ciudad' => 'Encarnación', 'tipo' => 'EEB',
        ])->assertRedirect(route('admin.instituciones.index'))->assertSessionHas('mensaje');

        $inst = Institucion::where('codigo', 'EEB-PRU-001')->firstOrFail();
        $this->assertTrue((bool) $inst->activo);
        $config = ConfigInstitucion::where('institucion_id', $inst->id)->firstOrFail();
        $this->assertSame(3, (int) $config->dias_reinicio_racha_default);
    }

    public function test_crear_institucion_con_codigo_repetido_es_rechazado()
    {
        $this->post(route('admin.instituciones.store'), [
            'nombre' => 'Otra', 'codigo' => 'EEB-ENC-001', 'ciudad' => 'X', 'tipo' => 'EEB',
        ])->assertSessionHasErrors('codigo');

        $this->assertSame(1, Institucion::where('codigo', 'EEB-ENC-001')->count());
    }

    public function test_editar_y_actualizar_institucion()
    {
        $inst = Institucion::orderBy('id')->firstOrFail();

        $r = $this->get(route('admin.instituciones.edit', $inst->id))->assertOk();
        $this->assertSame($inst->id, $r->viewData('inst')->id);
        $this->assertNotNull($r->viewData('config'));

        $this->put(route('admin.instituciones.update', $inst->id), [
            'nombre' => 'Nombre Nuevo', 'ciudad' => 'Hohenau', 'tipo' => 'Colegio',
        ])->assertRedirect(route('admin.instituciones.index'));

        $inst->refresh();
        $this->assertSame('Nombre Nuevo', $inst->nombre);
        $this->assertSame('Hohenau', $inst->ciudad);
        $this->assertSame('Colegio', $inst->tipo);

        $this->get(route('admin.instituciones.edit', 999999))->assertNotFound();
    }

    public function test_editar_una_institucion_sin_config_la_crea_con_valores_por_defecto()
    {
        $inst = Institucion::create(['nombre' => 'Sin Config', 'codigo' => 'EEB-SIN-009', 'ciudad' => 'X', 'tipo' => 'EEB', 'activo' => true]);
        $this->assertFalse(ConfigInstitucion::where('institucion_id', $inst->id)->exists());

        $this->get(route('admin.instituciones.config', $inst->id))->assertOk();

        $this->assertSame(1, ConfigInstitucion::where('institucion_id', $inst->id)->count());
    }

    public function test_guardar_configuracion_institucional_y_validacion_de_rangos()
    {
        $inst = Institucion::orderBy('id')->firstOrFail();

        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 5, 'permitir_acceso_grados_ant' => 1, 'meses_conservacion_egresados' => 6,
        ])->assertRedirect(route('admin.instituciones.config', $inst->id))->assertSessionHas('mensaje');

        $c = ConfigInstitucion::where('institucion_id', $inst->id)->firstOrFail();
        $this->assertSame(5, (int) $c->dias_reinicio_racha_default);
        $this->assertTrue((bool) $c->permitir_acceso_grados_ant);
        $this->assertSame(6, (int) $c->meses_conservacion_egresados);

        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 40, 'meses_conservacion_egresados' => 6,
        ])->assertSessionHasErrors('dias_reinicio_racha_default');
        $this->assertSame(5, (int) $c->fresh()->dias_reinicio_racha_default);

        // Sin marcar el casillero, el permiso vuelve a falso
        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 5, 'meses_conservacion_egresados' => 6,
        ]);
        $this->assertFalse((bool) $c->fresh()->permitir_acceso_grados_ant);
    }

    public function test_listado_de_docentes_ordenado_por_apellido()
    {
        $r = $this->get(route('admin.docentes'))->assertOk();

        $docentes = $r->viewData('docentes');
        $this->assertCount(Docente::where('activo', true)->count(), $docentes);
        $apellidos = $docentes->map(fn ($d) => $d->usuario->apellido)->values()->all();
        $ordenados = $apellidos; sort($ordenados);
        $this->assertSame($ordenados, $apellidos);
        $this->assertSame(Institucion::where('activo', true)->count(), $r->viewData('instituciones')->count());
    }

    public function test_crear_docente_crea_usuario_perfil_y_asignacion()
    {
        $inst = Institucion::orderBy('id')->firstOrFail();

        $this->post(route('admin.docentes.crear'), [
            'nombre' => 'Marta', 'apellido' => 'Ríos', 'email' => 'marta@unae.edu.py',
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1',
            'institucion_id' => $inst->id, 'grado' => 3, 'turno' => 'tarde',
        ])->assertRedirect(route('admin.docentes'))->assertSessionHas('mensaje');

        $usuario = Usuario::where('email', 'marta@unae.edu.py')->firstOrFail();
        $this->assertSame('docente', $usuario->perfil);
        $this->assertTrue(password_verify('clave-segura-1', $usuario->password_hash));
        $docente = Docente::where('usuario_id', $usuario->id)->firstOrFail();
        $this->assertTrue((bool) $docente->activo);
        $asig = AsignacionDocente::where('docente_id', $docente->id)->firstOrFail();
        $this->assertSame($inst->id, (int) $asig->institucion_id);
        $this->assertSame(3, (int) $asig->grado);
        $this->assertSame('tarde', $asig->turno);
        $this->assertTrue((bool) $asig->activo);
    }

    public function test_crear_docente_con_correo_repetido_o_clave_corta_es_rechazado()
    {
        $inst = Institucion::orderBy('id')->firstOrFail();
        $base = ['nombre' => 'A', 'apellido' => 'B', 'institucion_id' => $inst->id, 'grado' => 1, 'turno' => 'mañana',
                 'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1'];

        $this->post(route('admin.docentes.crear'), $base + ['email' => 'docente1@unae.edu.py'])
            ->assertSessionHasErrors('email');
        $this->post(route('admin.docentes.crear'), ['password' => 'corta', 'password_confirmation' => 'corta'] + $base + ['email' => 'otro@unae.edu.py'])
            ->assertSessionHasErrors('password');
        $this->assertFalse(Usuario::where('email', 'otro@unae.edu.py')->exists());
    }

    public function test_desactivar_docente_conserva_el_historial()
    {
        $docente = Docente::orderBy('id')->firstOrFail();

        $this->put(route('admin.docentes.desactivar', $docente->id))
            ->assertRedirect(route('admin.docentes'))->assertSessionHas('mensaje');

        $this->assertFalse((bool) $docente->fresh()->activo);
        $this->assertFalse((bool) $docente->usuario->fresh()->activo);
        $this->assertTrue(AsignacionDocente::where('docente_id', $docente->id)->exists());
        $this->assertFalse(AsignacionDocente::where('docente_id', $docente->id)->where('activo', true)->exists());

        $this->put(route('admin.docentes.desactivar', 999999))->assertNotFound();
    }

    public function test_mensajes_listar_crear_y_actualizar()
    {
        $r = $this->get(route('admin.mensajes'))->assertOk();
        $this->assertSame(MensajeRetro::count(), $r->viewData('mensajes')->count());

        $antes = MensajeRetro::count();
        $this->post(route('admin.mensajes.guardar'), [
            'id' => '', 'tipo' => 'acierto', 'contexto' => 'general', 'texto_es' => 'Muy bien', 'texto_gn' => 'Iporãiterei',
        ])->assertRedirect(route('admin.mensajes'));
        $this->assertSame($antes + 1, MensajeRetro::count());
        $nuevo = MensajeRetro::where('texto_es', 'Muy bien')->firstOrFail();
        $this->assertTrue((bool) $nuevo->activo);

        $this->post(route('admin.mensajes.guardar'), [
            'id' => $nuevo->id, 'tipo' => 'error', 'contexto' => 'reintento', 'texto_es' => 'Casi', 'texto_gn' => 'Ndaipóri',
        ])->assertRedirect(route('admin.mensajes'));
        $nuevo->refresh();
        $this->assertSame('Casi', $nuevo->texto_es);
        $this->assertSame('error', $nuevo->tipo);
        $this->assertSame($antes + 1, MensajeRetro::count(), 'Actualizar no crea otro mensaje.');
    }

    public function test_mensaje_demasiado_largo_o_con_tipo_invalido_es_rechazado()
    {
        $this->post(route('admin.mensajes.guardar'), [
            'id' => '', 'tipo' => 'acierto', 'contexto' => 'general', 'texto_es' => str_repeat('a', 161), 'texto_gn' => 'ok',
        ])->assertSessionHasErrors('texto_es');
        $this->post(route('admin.mensajes.guardar'), [
            'id' => '', 'tipo' => 'inventado', 'contexto' => 'general', 'texto_es' => 'a', 'texto_gn' => 'b',
        ])->assertSessionHasErrors('tipo');
    }

    public function test_catalogo_de_juegos()
    {
        $e = $this->escenario();

        $r = $this->get(route('admin.juegos'))->assertOk();

        $this->assertTrue($r->viewData('juegos')->contains('id', $e['juego']->id));
    }
}
