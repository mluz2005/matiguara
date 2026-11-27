<?php

namespace Tests\Feature\Repositories;

use App\Models\Estudiante;
use App\Support\TextosComunes as T;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Guaraní revisado de las pantallas comunes, tal como se ve en las páginas
 * (planilla Guarani_PantallasComunes_revisado).
 */
class GuaraniPantallasComunesTest extends RepositorioTestCase
{
    public function test_el_ingreso_de_estudiantes_muestra_castellano_y_guarani()
    {
        $r = $this->get(route('estudiante.login'))->assertOk();

        foreach (['titulo', 'subtitulo', 'etCodigo', 'ayudaCodigo', 'etPin', 'botonEntrar', 'burbujaInicio', 'quienSos'] as $k) {
            $r->assertSee(T::INGRESO[$k]['es']);
            $r->assertSee(T::INGRESO[$k]['gn']);
        }
        foreach (T::ROLES as $rol) {
            $r->assertSee($rol['nombre']['gn']);
        }
    }

    public function test_un_error_de_ingreso_se_ve_en_las_dos_lenguas()
    {
        RateLimiter::clear('estudiante|NOEXISTE|127.0.0.1');

        $r = $this->from(route('estudiante.login'))
            ->followingRedirects()
            ->post(route('estudiante.login.post'), ['codigo_acceso' => 'NOEXISTE', 'pin' => '0000'])
            ->assertOk();

        $es = 'El código o el PIN no son correctos. Pedile ayuda a tu maestra.';
        $r->assertSee($es);
        $r->assertSee(T::errorIngresoGn($es));
    }

    public function test_un_pin_incompleto_se_muestra_aunque_el_error_sea_del_pin()
    {
        $r = $this->from(route('estudiante.login'))
            ->followingRedirects()
            ->post(route('estudiante.login.post'), ['codigo_acceso' => 'SAN-1A-002', 'pin' => '12'])
            ->assertOk();

        $r->assertSee('El PIN debe tener exactamente 4 números.');
        $r->assertSee(T::errorIngresoGn('El PIN debe tener exactamente 4 números.'));
    }

    public function test_el_acceso_de_familias_lleva_el_guarani_revisado()
    {
        $r = $this->get(route('familia.login'))->assertOk();
        $r->assertSee(T::FAMILIA['titulo']['gn']);
        $r->assertSee(T::FAMILIA['subtitulo']['gn']);
        $r->assertSee(T::INGRESO['quienSos']['gn']);
    }

    public function test_el_selector_de_perfiles_tambien_lo_lleva_en_el_ingreso_de_docentes()
    {
        $this->get(route('login'))->assertOk()->assertSee(T::ROLES['familia']['nombre']['gn']);
    }

    public function test_el_saludo_de_cumpleanos_lleva_guarani_y_frase()
    {
        $e = Estudiante::first();
        Estudiante::whereKey($e->id)->update(['fecha_nacimiento' => now()->subYears(7)->toDateString()]);

        $r = $this->actingAs($e->fresh(), 'estudiante')->get(route('estudiante.juegos'))->assertOk();
        $r->assertSee(\App\Support\Cumpleanos::saludoGn($e->nombre));
        $r->assertSee(\App\Support\Cumpleanos::FRASE_GN);
    }
}
