<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Repositories\EstudianteRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * CONTROLADOR: LoginEstudianteController
 * =========================================================================
 * Gestiona el acceso de estudiantes de 6-9 años al sistema.
 *
 * DISEÑO DE UX INFANTIL:
 * El formulario de acceso fue diseñado pensando en niños de 1° a 3° grado:
 *   - Código de acceso: el docente lo imprime y entrega (SAN-1A-007)
 *   - PIN: 4 dígitos numéricos elegidos con el docente
 *   - Sin email, sin contraseñas complejas, sin "olvidé mi contraseña"
 *   - Letras grandes, botones grandes, colores amigables
 *
 * El guard 'estudiante' usa EstudianteProvider (código_acceso + PIN).
 *
 * CU: CU-02 (Iniciar sesión — Estudiante)
 * RF: RF-01 (autenticación por perfil)
 * RN: RN-01 (privacidad — id_anonimizado)
 * =========================================================================
 */
class LoginEstudianteController extends Controller
{
    public function __construct(private EstudianteRepository $estudiantes)
    {
    }

    /**
     * Muestra el formulario de acceso para estudiantes.
     * Es una vista diferente a la del adulto: más visual y amigable.
     */
    public function mostrarFormulario(): View|RedirectResponse
    {
        // Si ya está logueado como estudiante, ir directo a los juegos
        if (Auth::guard('estudiante')->check()) {
            return redirect()->route('estudiante.juegos');
        }

        return view('auth.login-estudiante');
    }

    /**
     * Procesa el acceso del estudiante con código + PIN.
     *
     * FLUJO:
     * 1. Validar formato del código y PIN
     * 2. EstudianteProvider::retrieveByCredentials() busca por codigo_acceso
     * 3. EstudianteProvider::validateCredentials() verifica el PIN con bcrypt
     * 4. Si todo ok, crear sesión con guard 'estudiante'
     * 5. Redirigir al selector de juegos
     */
    public function autenticar(Request $request): RedirectResponse
    {
        // ── 1. Validar la entrada ─────────────────────────────────────────
        $datos = $request->validate([
            'codigo_acceso' => ['required', 'string', 'max:20'],
            'pin'           => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ], [
            'codigo_acceso.required' => 'Ingresá tu código de acceso.',
            'pin.required'           => 'Ingresá tu PIN de 4 números.',
            'pin.size'               => 'El PIN debe tener exactamente 4 números.',
            'pin.regex'              => 'El PIN debe tener solo números.',
        ]);

        // ── 2. Convertir el código a mayúsculas (evitar errores tipográficos) ──
        $datos['codigo_acceso'] = strtoupper(trim($datos['codigo_acceso']));

        // ── 3. Intentar autenticación con guard 'estudiante' ──────────────
        // Auth::guard('estudiante')->attempt() usa EstudianteProvider:
        //   → retrieveByCredentials() busca WHERE codigo_acceso = ? AND activo = 1
        //   → validateCredentials() hace Hash::check($pin, $estudiante->pin_hash)
        // Límite de intentos: el PIN tiene solo 4 números, así que sin límite
        // se podría adivinar probando uno por uno. Se cuenta por código + equipo,
        // y un salón con la misma red no se bloquea entre sí.
        $claveLimite = 'estudiante|' . $datos['codigo_acceso'] . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($claveLimite, 8)) {
            return back()
                ->withInput($request->only('codigo_acceso'))
                ->withErrors([
                    'codigo_acceso' => 'Probaste muchas veces. Esperá un minuto y volvé a intentar, o pedile ayuda a tu maestra.',
                ]);
        }

        // Cuenta desactivada: no puede entrar, y se le explica por qué.
        // El aviso se muestra solo si el PIN es el correcto, para que nadie
        // pueda averiguar qué códigos existen o están inactivos probando.
        $candidato = $this->estudiantes->buscarPorCodigo($datos['codigo_acceso']);
        if ($candidato && !$candidato->activo && $candidato->verificarPin($datos['pin'])) {
            return back()
                ->withInput($request->only('codigo_acceso'))
                ->withErrors([
                    'codigo_acceso' => 'Tu cuenta no está activa por ahora. Pedile ayuda a tu maestra.',
                ]);
        }

        if (!Auth::guard('estudiante')->attempt($datos)) {
            RateLimiter::hit($claveLimite, 60);
            return back()
                ->withInput($request->only('codigo_acceso'))
                ->withErrors([
                    'codigo_acceso' => 'El código o el PIN no son correctos. Pedile ayuda a tu maestra.',
                ]);
        }

        // ── 4. Regenerar sesión por seguridad ─────────────────────────────
        RateLimiter::clear($claveLimite);
        $request->session()->regenerate();

        // ── 5. Redirigir al selector de juegos ───────────────────────────
        // El nombre del estudiante se usa en la vista de bienvenida
        $estudiante = Auth::guard('estudiante')->user();

        // Respaldo: si por algún motivo se logró entrar con una cuenta inactiva, se cierra la sesión.
        if (!$estudiante->activo) {
            Auth::guard('estudiante')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('estudiante.login')->withErrors([
                'codigo_acceso' => 'Tu cuenta no está activa por ahora. Pedile ayuda a tu maestra.',
            ]);
        }

        return redirect()
            ->route('estudiante.juegos')
            ->with('bienvenida', "¡Hola, {$estudiante->nombre}! 🎮");
    }

    /**
     * Cierra la sesión del estudiante.
     * Vuelve al formulario de acceso.
     */
    public function cerrarSesion(Request $request): RedirectResponse
    {
        Auth::guard('estudiante')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('estudiante.login');
    }
}
