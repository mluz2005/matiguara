<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: LoginController
 * =========================================================================
 * Gestiona el login y logout de usuarios ADULTOS (docentes, padres, admin).
 * Usa el guard 'web' estándar de Laravel con email + password.
 *
 * Después del login, redirige según el perfil del usuario:
 *   'docente' → /docente/panel
 *   'padre'   → /padre/panel
 *   'admin'   → /admin/panel
 *
 * CU: CU-01 (Iniciar sesión — adulto)
 * RF: RF-01 (autenticación por perfil)
 * =========================================================================
 */
class LoginController extends Controller
{
    /**
     * Muestra el formulario de login para adultos.
     * Si el usuario ya está autenticado, lo redirige directamente a su panel.
     */
    public function mostrarFormulario(): View|RedirectResponse
    {
        // Si ya está logueado, redirigir al dashboard correspondiente
        if (Auth::check()) {
            return $this->redirigirSegunPerfil(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Procesa el formulario de login de adultos.
     *
     * Valida las credenciales, verifica que la cuenta esté activa
     * y redirige al panel correspondiente según el perfil del usuario.
     */
    public function autenticar(Request $request): RedirectResponse
    {
        // ── 1. Validar la entrada del formulario ──────────────────────────
        $credenciales = $request->validate([
            'email'    => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            // Mensajes de error en español
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresá un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        // ── 2. Intentar autenticación con guard 'web' ─────────────────────
        // Auth::attempt() llama a:
        //   → EloquentUserProvider::retrieveByCredentials() → busca por email
        //   → EloquentUserProvider::validateCredentials()   → verifica password
        //   → getAuthPassword() del modelo → devuelve 'password_hash'
        $recuerdo = $request->boolean('recuerdar', false);

        if (!Auth::attempt(['email' => $credenciales['email'], 'password' => $credenciales['password']], $recuerdo)) {
            // Credenciales incorrectas: devolver con error genérico
            // (no especificar qué campo falló — seguridad básica)
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Las credenciales no son correctas. Verificá tu correo y contraseña.',
                ]);
        }

        $usuario = Auth::user();

        // ── 3. Verificar que la cuenta esté activa ────────────────────────
        if (!$usuario->activo) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Tu cuenta está desactivada. Contactá a la administradora.',
            ]);
        }

        // ── 4. Regenerar el token de sesión (prevenir Session Fixation) ───
        $request->session()->regenerate();

        // ── 5. Redirigir según el perfil del usuario ──────────────────────
        return $this->redirigirSegunPerfil($usuario);
    }

    /**
     * Cierra la sesión del usuario adulto.
     * Invalida la sesión y el token CSRF para seguridad.
     */
    public function cerrarSesion(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('mensaje', 'Sesión cerrada correctamente.');
    }

    /**
     * Determina a qué ruta redirigir según el perfil del usuario.
     * Implementa la lógica de RF-01 (autenticación por perfil).
     *
     * @param  \App\Models\Usuario  $usuario
     * @return RedirectResponse
     */
    private function redirigirSegunPerfil($usuario): RedirectResponse
    {
        return match ($usuario->perfil) {
            'docente' => redirect()->route('docente.panel'),
            'padre'   => redirect()->route('padre.panel'),
            'admin'   => redirect()->route('admin.panel'),
            default   => redirect()->route('login')->withErrors([
                'email' => 'Perfil de usuario no reconocido.',
            ]),
        };
    }
}
