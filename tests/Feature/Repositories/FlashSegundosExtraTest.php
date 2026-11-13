<?php

namespace Tests\Feature\Repositories;

use App\Models\ConfigInstitucion;
use App\Models\Estudiante;
use App\Models\Institucion;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\Usuario;
use App\Services\SesionService;

/**
 * Segundos extra de Flash Matemático: la administración los define por institución
 * (config_institucion.segundos_extra_flash) y se suman al tiempo de cada nivel.
 */
class FlashSegundosExtraTest extends RepositorioTestCase
{
    private function comoAdmin(): void
    {
        $this->actingAs(Usuario::where('email', 'admin@unae.edu.py')->firstOrFail());
    }

    private function institucionDelAlumno(Estudiante $e): int
    {
        return (int) $e->matriculaActiva->institucion_id;
    }

    private function fijarExtra(Estudiante $e, int $segundos): void
    {
        ConfigInstitucion::updateOrCreate(
            ['institucion_id' => $this->institucionDelAlumno($e)],
            ['segundos_extra_flash' => $segundos]
        );
    }

    private function nivelFlash(Estudiante $e): Nivel
    {
        return Nivel::has('ejercicios')->whereNotNull('tiempo_mostrar_ms')
            ->whereHas('juego', fn ($q) => $q->where('nombre_es', 'Flash Matemático'))
            ->where('grado', (int) $e->matriculaActiva->grado)
            ->orderBy('numero_nivel')->firstOrFail();
    }

    // ── Guardado (solo el administrador) ─────────────────────────────────

    public function test_por_defecto_no_se_suma_nada()
    {
        $inst = Institucion::orderBy('id')->firstOrFail();
        $config = ConfigInstitucion::firstOrCreate(['institucion_id' => $inst->id]);

        $this->assertSame(0, (int) $config->fresh()->segundos_extra_flash);
    }

    public function test_el_administrador_guarda_los_segundos_extra()
    {
        $this->comoAdmin();
        $inst = Institucion::orderBy('id')->firstOrFail();
        ConfigInstitucion::firstOrCreate(['institucion_id' => $inst->id]);

        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4, 'segundos_extra_flash' => 7,
        ])->assertSessionHas('mensaje');

        $this->assertSame(7, (int) ConfigInstitucion::where('institucion_id', $inst->id)->value('segundos_extra_flash'));

        // Valores negativos permitidos hasta -5
        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4, 'segundos_extra_flash' => -5,
        ])->assertSessionHas('mensaje');
        $this->assertSame(-5, (int) ConfigInstitucion::where('institucion_id', $inst->id)->value('segundos_extra_flash'));
    }

    public function test_se_rechazan_valores_fuera_de_rango_y_no_enteros()
    {
        $this->comoAdmin();
        $inst = Institucion::orderBy('id')->firstOrFail();
        ConfigInstitucion::firstOrCreate(['institucion_id' => $inst->id])->update(['segundos_extra_flash' => 4]);

        foreach ([21, -6, 'abc', '2.5'] as $malo) {
            $this->put(route('admin.instituciones.config.guardar', $inst->id), [
                'dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4, 'segundos_extra_flash' => $malo,
            ])->assertSessionHasErrors('segundos_extra_flash');
        }

        $this->assertSame(4, (int) ConfigInstitucion::where('institucion_id', $inst->id)->value('segundos_extra_flash'));
    }

    public function test_si_el_formulario_no_trae_el_valor_no_se_modifica()
    {
        $this->comoAdmin();
        $inst = Institucion::orderBy('id')->firstOrFail();
        ConfigInstitucion::firstOrCreate(['institucion_id' => $inst->id])->update(['segundos_extra_flash' => 6]);

        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4,
        ])->assertSessionHas('mensaje');

        $this->assertSame(6, (int) ConfigInstitucion::where('institucion_id', $inst->id)->value('segundos_extra_flash'));
    }

    public function test_la_pantalla_de_configuracion_muestra_el_campo()
    {
        $this->comoAdmin();
        $inst = Institucion::orderBy('id')->firstOrFail();

        $this->get(route('admin.instituciones.config', $inst->id))
            ->assertOk()
            ->assertSee('Segundos extra en Flash Matemático')
            ->assertSee('name="segundos_extra_flash"', false);
    }

    public function test_un_docente_no_puede_cambiar_los_segundos_extra()
    {
        $this->actingAs(Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail());
        $inst = Institucion::orderBy('id')->firstOrFail();
        ConfigInstitucion::firstOrCreate(['institucion_id' => $inst->id]);

        $this->put(route('admin.instituciones.config.guardar', $inst->id), [
            'dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4, 'segundos_extra_flash' => 9,
        ])->assertRedirect(route('login'));

        $this->assertSame(0, (int) ConfigInstitucion::where('institucion_id', $inst->id)->value('segundos_extra_flash'));
    }

    public function test_el_docente_ve_el_valor_pero_sin_poder_editarlo()
    {
        $docente = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $asig = \App\Models\Docente::where('usuario_id', $docente->id)->firstOrFail()->asignacionActiva;
        ConfigInstitucion::updateOrCreate(['institucion_id' => $asig->institucion_id], ['segundos_extra_flash' => 3]);

        $this->actingAs($docente);
        $this->get(route('docente.juegos'))
            ->assertOk()
            ->assertSee('Flash Matemático:')
            ->assertSee('+3 s')
            ->assertDontSee('name="segundos_extra_flash"', false);
    }

    // ── Efecto en el juego ───────────────────────────────────────────────

    public function test_el_ejercicio_de_flash_suma_los_segundos_al_tiempo_del_nivel()
    {
        $e     = Estudiante::first();
        $nivel = $this->nivelFlash($e);
        $base  = (int) $nivel->tiempo_mostrar_ms;

        $this->fijarExtra($e, 5);
        $sesion = app(SesionService::class)->iniciar($e->id, $nivel->juego_id, $nivel->id);

        $this->actingAs($e, 'estudiante');
        $this->get(route('estudiante.sesion.ejercicio', $sesion->id))
            ->assertOk()
            ->assertSee('const totalMs   = ' . ($base + 5000) . ';', false);
    }

    public function test_sin_ajuste_el_tiempo_es_el_del_nivel()
    {
        $e     = Estudiante::first();
        $nivel = $this->nivelFlash($e);
        $this->fijarExtra($e, 0);
        $sesion = app(SesionService::class)->iniciar($e->id, $nivel->juego_id, $nivel->id);

        $this->actingAs($e, 'estudiante');
        $this->get(route('estudiante.sesion.ejercicio', $sesion->id))
            ->assertOk()
            ->assertSee('const totalMs   = ' . (int) $nivel->tiempo_mostrar_ms . ';', false);
    }

    public function test_un_ajuste_negativo_no_baja_de_dos_segundos()
    {
        $e     = Estudiante::first();
        $nivel = $this->nivelFlash($e);
        $nivel->update(['tiempo_mostrar_ms' => 4000]);

        $this->fijarExtra($e, -5);
        $sesion = app(SesionService::class)->iniciar($e->id, $nivel->juego_id, $nivel->id);

        $this->actingAs($e, 'estudiante');
        $this->get(route('estudiante.sesion.ejercicio', $sesion->id))
            ->assertOk()
            ->assertSee('const totalMs   = 2000;', false);
    }

    public function test_el_menu_muestra_los_segundos_con_el_ajuste()
    {
        $e     = Estudiante::first();
        $nivel = $this->nivelFlash($e);
        $esperado = intdiv((int) $nivel->tiempo_mostrar_ms, 1000) + 4;

        $this->fijarExtra($e, 4);
        $this->actingAs($e, 'estudiante');

        $r = $this->get(route('estudiante.juegos'))->assertOk();

        $this->assertSame(4, $r->viewData('segundosExtraFlash'));
        $r->assertSee("{$esperado} segundos para memorizar");
    }

    public function test_el_ajuste_no_afecta_a_suma_de_frutas()
    {
        $esc = $this->escenario();
        $this->fijarExtra($esc['estudiante'], 15);

        $sesion = app(SesionService::class)->iniciar($esc['estudiante']->id, $esc['juego']->id, $esc['nivel1']->id);

        $this->actingAs($esc['estudiante'], 'estudiante');
        $this->get(route('estudiante.sesion.ejercicio', $sesion->id))
            ->assertOk()
            ->assertDontSee('const totalMs', false)
            ->assertDontSee('countdownRing', false);
    }

    public function test_el_ajuste_de_una_institucion_no_afecta_a_otra()
    {
        $e = Estudiante::first();
        $otra = Institucion::create([
            'nombre' => 'Otra escuela', 'codigo' => 'OTRA-01', 'ciudad' => 'Asunción', 'tipo' => 'publica', 'activo' => true,
        ]);
        ConfigInstitucion::updateOrCreate(['institucion_id' => $otra->id], ['segundos_extra_flash' => 12]);

        $this->assertSame(0, \App\Support\TiempoFlash::extraDe($e));
    }
}
