<?php

namespace Tests\Feature\Repositories;

use App\Models\Estudiante;
use App\Models\Padre;
use App\Models\Usuario;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pruebas del acceso para familias (correo del tutor + código del niño/a).
 */
class LoginFamiliaControllerTest extends RepositorioTestCase
{
    private function alumno(string $codigo, array $cambios = []): Estudiante
    {
        $e = Estudiante::where('codigo_acceso', $codigo)->firstOrFail();
        if ($cambios) $e->update($cambios);
        return $e->fresh();
    }

    public function test_formulario_para_invitados()
    {
        $this->get(route('familia.login'))->assertOk();
    }

    public function test_ingreso_correcto_crea_la_cuenta_vincula_hermanos_activos_y_abre_sesion()
    {
        $juan  = $this->alumno('SAN-1A-002', ['email_tutor' => 'Familia.Perez@Correo.com']);
        $sofia = $this->alumno('SAN-1A-003', ['email_tutor' => 'familia.perez@correo.com']);
        $diego = $this->alumno('SAN-2B-001', ['email_tutor' => 'familia.perez@correo.com', 'activo' => false]);

        $this->post(route('familia.login.post'), ['email' => ' FAMILIA.PEREZ@correo.com ', 'codigo_acceso' => ' san-1a-002 '])
            ->assertRedirect(route('padre.panel'));

        $usuario = Usuario::where('email', 'familia.perez@correo.com')->firstOrFail();
        $this->assertSame('padre', $usuario->perfil);
        $this->assertTrue((bool) $usuario->activo);
        $this->assertAuthenticatedAs($usuario);

        $vinculados = Padre::where('usuario_id', $usuario->id)->pluck('estudiante_id')->all();
        sort($vinculados);
        $esperados = [$juan->id, $sofia->id]; sort($esperados);
        $this->assertEquals($esperados, $vinculados, 'Vincula a los hermanos activos y no al inactivo.');
        $this->assertNotContains($diego->id, $vinculados);
    }

    public function test_un_segundo_ingreso_reutiliza_la_cuenta_y_no_duplica_vinculos()
    {
        $this->alumno('SAN-1A-002', ['email_tutor' => 'repetida@correo.com']);
        $datos = ['email' => 'repetida@correo.com', 'codigo_acceso' => 'SAN-1A-002'];

        $this->post(route('familia.login.post'), $datos);
        $this->post(route('logout'));
        $this->post(route('familia.login.post'), $datos)->assertRedirect(route('padre.panel'));

        $this->assertSame(1, Usuario::where('email', 'repetida@correo.com')->count());
        $this->assertSame(1, Padre::where('usuario_id', Usuario::where('email', 'repetida@correo.com')->value('id'))->count());
    }

    public function test_codigo_o_correo_incorrectos_dan_el_mismo_mensaje_generico()
    {
        $this->alumno('SAN-1A-002', ['email_tutor' => 'bien@correo.com']);

        $this->post(route('familia.login.post'), ['email' => 'bien@correo.com', 'codigo_acceso' => 'SAN-1A-003'])
            ->assertSessionHasErrors(['email' => 'El correo o el código no son correctos. Revisalos o consultá a la docente.']);
        $this->post(route('familia.login.post'), ['email' => 'mal@correo.com', 'codigo_acceso' => 'SAN-1A-002'])
            ->assertSessionHasErrors(['email' => 'El correo o el código no son correctos. Revisalos o consultá a la docente.']);

        $this->assertGuest();
        $this->assertFalse(Usuario::where('email', 'bien@correo.com')->exists());
    }

    public function test_un_correo_de_docente_o_administracion_nunca_abre_sesion_por_aqui()
    {
        $this->alumno('SAN-1A-002', ['email_tutor' => 'docente1@unae.edu.py']);

        $this->post(route('familia.login.post'), ['email' => 'docente1@unae.edu.py', 'codigo_acceso' => 'SAN-1A-002'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_estudiante_inactivo_o_cuenta_de_familia_desactivada_no_ingresan()
    {
        $this->alumno('SAN-1A-002', ['email_tutor' => 'inactivo@correo.com', 'activo' => false]);
        $this->post(route('familia.login.post'), ['email' => 'inactivo@correo.com', 'codigo_acceso' => 'SAN-1A-002'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->alumno('SAN-1A-003', ['email_tutor' => 'desactivada@correo.com']);
        Usuario::create([
            'nombre' => 'F', 'apellido' => 'D', 'email' => 'desactivada@correo.com',
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => false,
        ]);
        $this->post(route('familia.login.post'), ['email' => 'desactivada@correo.com', 'codigo_acceso' => 'SAN-1A-003'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_demasiados_intentos_fallidos_bloquean_temporalmente()
    {
        $correo = 'bloqueo.' . uniqid() . '@correo.com';
        $this->alumno('SAN-1A-002', ['email_tutor' => $correo]);
        $clave = 'familia|' . $correo . '|127.0.0.1';
        RateLimiter::clear($clave);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('familia.login.post'), ['email' => $correo, 'codigo_acceso' => 'MALO-' . $i]);
        }

        $r = $this->post(route('familia.login.post'), ['email' => $correo, 'codigo_acceso' => 'SAN-1A-002']);
        $r->assertSessionHasErrors('email');
        $this->assertStringContainsString('demasiadas veces', session('errors')->first('email'));
        $this->assertGuest();

        RateLimiter::clear($clave);
    }
}
