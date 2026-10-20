<?php

namespace Tests\Feature\Repositories;

use App\Models\Estudiante;
use App\Models\Padre;
use App\Models\Usuario;
use App\Repositories\LogroRepository;
use App\Models\CatalogoLogro;

/**
 * Pruebas de comportamiento del panel de familias (rutas /padre/...).
 */
class PadreControllerTest extends RepositorioTestCase
{
    private Usuario $padre;
    private Estudiante $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->padre = Usuario::where('email', 'padre@correo.com')->firstOrFail();
        $this->ana   = Estudiante::where('codigo_acceso', 'SAN-1A-001')->firstOrFail();
        $this->actingAs($this->padre);
    }

    private function familiaSinVinculo(string $correo = 'sinvinculo@familia.com'): Usuario
    {
        return Usuario::create([
            'nombre' => 'Fam', 'apellido' => 'Sola', 'email' => $correo,
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => true,
        ]);
    }

    public function test_panel_muestra_metricas_logros_y_diagnostico_del_hijo()
    {
        $e = $this->escenario();
        $sesion = $this->nuevaSesion($e, ['estado' => 'completada', 'aciertos' => 3, 'errores' => 1]);
        $this->nuevaSesion($e, ['estado' => 'completada', 'aciertos' => 1, 'errores' => 1]);
        app(LogroRepository::class)->otorgar($this->ana->id, CatalogoLogro::first()->id, $sesion->id);
        \App\Models\Diagnostico::create([
            'estudiante_id' => $this->ana->id, 'docente_id' => \App\Models\Docente::first()->id,
            'id_anonimizado' => $this->ana->id_anonimizado, 'fecha_generacion' => now(), 'estado' => 'exitoso',
        ]);

        $r = $this->get(route('padre.panel'))->assertOk();

        $this->assertSame($this->ana->id, $r->viewData('estudiante')->id);
        $this->assertSame(2, $r->viewData('totalSesiones'));
        $this->assertCount(2, $r->viewData('ultimasSesiones'));
        $this->assertEquals(67, $r->viewData('porcentajeAciertos'));   // 4 de 6
        $this->assertSame(0, (int) $r->viewData('rachaMax'));
        $this->assertCount(1, $r->viewData('logros'));
        $this->assertNotNull($r->viewData('ultimoDiagnostico'));
        $this->assertNotNull($r->viewData('matricula'));
    }

    public function test_panel_sin_actividad_muestra_ceros()
    {
        $r = $this->get(route('padre.panel'))->assertOk();

        $this->assertSame(0, $r->viewData('totalSesiones'));
        $this->assertEquals(0, $r->viewData('porcentajeAciertos'));
        $this->assertNull($r->viewData('ultimoDiagnostico'));
    }

    public function test_hermanos_se_eligen_con_hijo_pero_nunca_un_nino_ajeno()
    {
        $juan = Estudiante::where('codigo_acceso', 'SAN-1A-002')->firstOrFail();
        $diego = Estudiante::where('codigo_acceso', 'SAN-2B-001')->firstOrFail();
        Padre::create(['usuario_id' => $this->padre->id, 'estudiante_id' => $juan->id, 'relacion' => 'padre']);

        $this->assertSame($this->ana->id, $this->get(route('padre.panel'))->viewData('estudiante')->id, 'Orden por nombre: Ana primero.');
        $this->assertSame($juan->id, $this->get(route('padre.panel', ['hijo' => $juan->id]))->viewData('estudiante')->id);
        $this->assertCount(2, $this->get(route('padre.panel'))->viewData('hijos'));

        // Pedir un niño que no es de la familia devuelve el primero propio
        $this->assertSame($this->ana->id, $this->get(route('padre.panel', ['hijo' => $diego->id]))->viewData('estudiante')->id);
    }

    public function test_sin_vinculo_el_panel_y_los_logros_redirigen_a_vincular()
    {
        $this->actingAs($this->familiaSinVinculo());

        $this->get(route('padre.panel'))->assertRedirect(route('padre.vincular'));
        $this->get(route('padre.logros'))->assertRedirect(route('padre.vincular'));
        $this->get(route('padre.vincular'))->assertOk();
    }

    public function test_con_vinculo_el_formulario_de_vincular_redirige_al_panel()
    {
        $this->get(route('padre.vincular'))->assertRedirect(route('padre.panel'));
    }

    public function test_vincular_con_un_codigo_valido_en_minusculas()
    {
        $sinVinculo = $this->familiaSinVinculo();
        $this->actingAs($sinVinculo);
        $juan = Estudiante::where('codigo_acceso', 'SAN-1A-002')->firstOrFail();

        $this->post(route('padre.vincular.post'), ['codigo_acceso' => ' san-1a-002 ', 'relacion' => 'madre'])
            ->assertRedirect(route('padre.panel'))->assertSessionHas('bienvenida');

        $v = Padre::where('usuario_id', $sinVinculo->id)->firstOrFail();
        $this->assertSame($juan->id, (int) $v->estudiante_id);
        $this->assertSame('madre', $v->relacion);
    }

    public function test_vincular_rechaza_codigo_inexistente_inactivo_o_cuenta_ya_vinculada()
    {
        $sinVinculo = $this->familiaSinVinculo();
        $this->actingAs($sinVinculo);

        $this->post(route('padre.vincular.post'), ['codigo_acceso' => 'NO-EXISTE', 'relacion' => 'padre'])
            ->assertSessionHasErrors('codigo_acceso');

        Estudiante::where('codigo_acceso', 'SAN-1A-002')->update(['activo' => false]);
        $this->post(route('padre.vincular.post'), ['codigo_acceso' => 'SAN-1A-002', 'relacion' => 'padre'])
            ->assertSessionHasErrors('codigo_acceso');
        $this->assertFalse(Padre::where('usuario_id', $sinVinculo->id)->exists());

        // La cuenta sembrada ya está vinculada: no puede vincular otro niño
        $this->actingAs($this->padre);
        $this->post(route('padre.vincular.post'), ['codigo_acceso' => 'SAN-1A-003', 'relacion' => 'padre'])
            ->assertSessionHasErrors('codigo_acceso');
        $this->assertSame(1, Padre::where('usuario_id', $this->padre->id)->count());
    }

    public function test_logros_del_hijo_ordenados_del_mas_reciente()
    {
        $e = $this->escenario();
        $sesion = $this->nuevaSesion($e);
        $repo = app(LogroRepository::class);
        [$a, $b] = CatalogoLogro::take(2)->get()->all();
        $repo->otorgar($this->ana->id, $a->id, $sesion->id);
        $repo->otorgar($this->ana->id, $b->id, $sesion->id);
        \App\Models\LogroObtenido::where('logro_id', $a->id)->update(['fecha_obtenido' => now()->subDays(5)]);
        \App\Models\LogroObtenido::where('logro_id', $b->id)->update(['fecha_obtenido' => now()]);

        $r = $this->get(route('padre.logros'))->assertOk();

        $this->assertSame([$b->id, $a->id], $r->viewData('logros')->pluck('logro_id')->all());
        $this->assertSame($this->ana->id, $r->viewData('estudiante')->id);
    }
}
