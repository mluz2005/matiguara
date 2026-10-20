<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Repositories\EstudianteRepository;
use App\Repositories\PadreRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CONTROLADOR: LoginFamiliaController — "Acceso para familias"
 * =========================================================================
 * El padre, madre o tutor entra con DOS datos:
 *   1. el correo que la docente cargó al registrar al niño/a, y
 *   2. el código de acceso del niño/a (el mismo que usa para jugar).
 * No hay contraseña que recordar.
 *
 * QUÉ HACE AL ENTRAR:
 *   - Busca al estudiante cuyo correo de tutor y código coinciden.
 *   - Crea (la primera vez) la cuenta de familia en "usuarios" con perfil
 *     "padre" y una clave aleatoria que nadie conoce (se entra siempre por
 *     este acceso).
 *   - Vincula a esa cuenta con TODOS los niños/as activos que tengan ese
 *     mismo correo (hermanos), usando la tabla "padres" de siempre.
 *   - Abre la sesión con el guard 'web' → el panel de padres funciona igual.
 *
 * SEGURIDAD:
 *   - Máximo 5 intentos fallidos por minuto (por correo + equipo).
 *   - Mensaje de error genérico: no revela si el correo o el código existen.
 *   - Un correo de docente o administración NUNCA abre sesión por acá.
 *   - El ingreso con correo + contraseña (/login) sigue funcionando igual.
 * =========================================================================
 */
class LoginFamiliaController extends Controller
{
    private const MENSAJE_GENERICO =
        'El correo o el código no son correctos. Revisalos o consultá a la docente.';

    public function __construct(
        private EstudianteRepository $estudiantes,
        private UsuarioRepository $usuarios,
        private PadreRepository $padres
    ) {
    }

    public function mostrarFormulario(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('inicio');
        }

        return view('auth.login-familia');
    }

    public function autenticar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email'         => ['required', 'email', 'max:191'],
            'codigo_acceso' => ['required', 'string', 'max:20'],
        ], [
            'email.required'         => 'Escribí tu correo electrónico.',
            'email.email'            => 'El correo no parece válido. Revisalo.',
            'codigo_acceso.required' => 'Escribí el código de tu hijo/a.',
        ]);

        $correo = mb_strtolower(trim($datos['email']));
        $codigo = strtoupper(trim($datos['codigo_acceso']));

        $claveLimite = 'familia|' . $correo . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($claveLimite, 5)) {
            $segundos = RateLimiter::availableIn($claveLimite);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => "Probaste demasiadas veces. Esperá {$segundos} segundos y volvé a intentar.",
            ]);
        }

        // 1) ¿Existe un estudiante con ESE código y ESE correo de tutor?
        $estudiante = $this->estudiantes->buscarPorCodigoYCorreoTutor($codigo, $correo);

        if (!$estudiante) {
            RateLimiter::hit($claveLimite, 60);
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => self::MENSAJE_GENERICO]);
        }

        // 2) ¿El correo ya pertenece a una cuenta de docente o administración?
        $usuario = $this->usuarios->porCorreo($correo);

        if ($usuario && $usuario->perfil !== 'padre') {
            RateLimiter::hit($claveLimite, 60);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Ese correo no se puede usar en este acceso. Consultá a la docente.',
            ]);
        }

        if ($usuario && !$usuario->activo) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Tu cuenta está desactivada. Consultá a la docente.',
            ]);
        }

        // 3) El niño/a debe estar activo
        if (!$estudiante->activo) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'La cuenta de ' . $estudiante->nombre . ' no está activa por ahora. Consultá a la docente.',
            ]);
        }

        // 4) Primera vez: se crea la cuenta de familia
        if (!$usuario) {
            $usuario = $this->usuarios->crear([
                'nombre'        => Str::limit('Familia ' . $estudiante->apellido, 100, ''),
                'apellido'      => $estudiante->apellido,
                'email'         => $correo,
                'password_hash' => Hash::make(Str::random(40)), // nadie la conoce: se entra por este acceso
                'perfil'        => 'padre',
                'activo'        => true,
            ]);
        }

        // 5) Vincular con todos los hijos/as activos que tengan este correo
        $this->estudiantes->activosPorCorreoTutor($correo)
            ->each(function (Estudiante $hijo) use ($usuario) {
                $this->padres->vincularSiNoExiste($usuario->id, $hijo->id, 'tutor');
            });

        RateLimiter::clear($claveLimite);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('padre.panel');
    }
}
