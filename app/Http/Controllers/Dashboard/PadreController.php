<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\LogroRepository;
use App\Repositories\PadreRepository;
use App\Repositories\SesionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: PadreController — Panel parental completo
 * =========================================================================
 * Implementa el acceso restringido del padre al progreso de su hijo.
 *
 * ACCESO ESTRICTAMENTE RESTRINGIDO (RN-04):
 * El padre SOLO puede ver información de su hijo vinculado.
 * No puede ver otros estudiantes, no puede modificar nada,
 * no puede acceder a diagnósticos IA.
 *
 * FLUJO DE VINCULACIÓN (CU-17):
 * 1. El docente entrega el código del estudiante al padre
 * 2. El padre se registra con email + password
 * 3. El padre vincula su cuenta al código del niño (una sola vez)
 * 4. A partir de ahí, accede al panel con el progreso de su hijo
 *
 * CU: CU-16 (Ver panel del hijo), CU-17 (Vincular), CU-18 (Ver logros)
 * RF: RF-09 (panel parental)
 * RN: RN-04 (acceso restringido)
 * =========================================================================
 */
class PadreController extends Controller
{
    public function __construct(
        private PadreRepository $padres,
        private EstudianteRepository $estudiantes,
        private SesionRepository $sesiones,
        private LogroRepository $logros,
        private DiagnosticoRepository $diagnosticos
    ) {
    }

    /**
     * Panel principal del padre.
     * Si no tiene hijo vinculado, redirige al formulario de vinculación.
     * CU-16.
     */
    public function panel(): View|RedirectResponse
    {
        $usuario = Auth::user();

        // Una familia puede tener varios hijos/as (hermanos con el mismo correo).
        // Se muestra uno por vez; ?hijo=ID elige cuál.
        $hijos   = $this->vinculosDelUsuario($usuario->id);
        $vinculo = $this->vinculoElegido($hijos, request('hijo'));

        // Si no tiene hijo vinculado todavía → mostrar formulario de vinculación
        if (!$vinculo || !$vinculo->estudiante) {
            return redirect()->route('padre.vincular');
        }

        $estudiante = $vinculo->estudiante;
        $matricula  = $estudiante->matriculaActiva;

        // ── Métricas del hijo ─────────────────────────────────────────────
        $resumen         = $this->sesiones->resumenCompletadas($estudiante->id);
        $totalSesiones   = $resumen['sesiones'];
        $ultimasSesiones = $this->sesiones->recientesCompletadas($estudiante->id, 5);

        $totalAciertos   = $resumen['aciertos'];
        $totalRespuestas = $resumen['respuestas'];

        $porcentajeAciertos = $totalRespuestas > 0
            ? round(($totalAciertos / $totalRespuestas) * 100)
            : 0;

        // Racha actual (el mejor de todos sus juegos)
        $rachaMax = $this->estudiantes->rachaActualMaximaEnDias($estudiante);

        // Logros recientes
        $logros = $this->logros->recientesDe($estudiante->id, 6);

        // Mensaje contextual del docente (último diagnóstico si existe)
        $ultimoDiagnostico = $this->diagnosticos->ultimoExitoso($estudiante->id);

        return view('padre.panel', [
            'usuario'            => $usuario,
            'estudiante'         => $estudiante,
            'matricula'          => $matricula,
            'totalSesiones'      => $totalSesiones,
            'ultimasSesiones'    => $ultimasSesiones,
            'porcentajeAciertos' => $porcentajeAciertos,
            'rachaMax'           => $rachaMax,
            'logros'             => $logros,
            'ultimoDiagnostico'  => $ultimoDiagnostico,
            'vinculo'            => $vinculo,
            'hijos'              => $hijos,
        ]);
    }

    /**
     * Todos los vínculos de esta cuenta con estudiantes (hermanos incluidos),
     * ordenados por nombre.
     */
    private function vinculosDelUsuario(int $usuarioId)
    {
        return $this->padres->vinculosDelUsuario($usuarioId)
            ->filter(fn ($v) => $v->estudiante !== null)
            ->sortBy(fn ($v) => $v->estudiante->nombre)
            ->values();
    }

    /**
     * Elige el vínculo pedido por ?hijo=ID. Solo entre los de esta cuenta
     * (nunca se puede ver a un niño/a ajeno cambiando el número). Si no se
     * pide ninguno, o no corresponde, el primero.
     */
    private function vinculoElegido($vinculos, $hijoId)
    {
        if ($hijoId !== null) {
            $elegido = $vinculos->firstWhere('estudiante_id', (int) $hijoId);
            if ($elegido) {
                return $elegido;
            }
        }
        return $vinculos->first();
    }

    /**
     * Muestra el formulario de vinculación con el hijo.
     * CU-17 (paso 1).
     */
    public function mostrarVincular(): View|RedirectResponse
    {
        // Si ya tiene hijo vinculado, ir al panel directamente
        if ($this->padres->tieneVinculo(Auth::id())) {
            return redirect()->route('padre.panel');
        }

        return view('padre.vincular');
    }

    /**
     * Procesa la vinculación entre el padre y el estudiante.
     * CU-17: el padre ingresa el código de acceso de su hijo.
     */
    public function vincular(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo_acceso' => ['required', 'string', 'max:20'],
            'relacion'      => ['required', 'in:padre,madre,tutor'],
        ], [
            'codigo_acceso.required' => 'Ingresá el código de acceso de tu hijo/a.',
        ]);

        $codigo     = strtoupper(trim($datos['codigo_acceso']));
        $estudiante = $this->estudiantes->buscarActivoPorCodigo($codigo);

        if (!$estudiante) {
            return back()->withErrors([
                'codigo_acceso' => 'No encontramos un estudiante activo con ese código. Verificá con el docente.',
            ]);
        }

        // Verificar que este padre no esté ya vinculado a otro estudiante
        if ($this->padres->tieneVinculo(Auth::id())) {
            return back()->withErrors([
                'codigo_acceso' => 'Ya tenés una cuenta vinculada. Contactá al docente si necesitás cambiarla.',
            ]);
        }

        $this->padres->crearVinculo([
            'usuario_id'    => Auth::id(),
            'estudiante_id' => $estudiante->id,
            'relacion'      => $datos['relacion'],
        ]);

        return redirect()
            ->route('padre.panel')
            ->with('bienvenida', "¡Cuenta vinculada! Ya podés ver el progreso de {$estudiante->nombre} 🎉");
    }

    /**
     * Vista de logros del hijo con detalle.
     * CU-18.
     */
    public function logrosHijo(): View|RedirectResponse
    {
        $hijos      = $this->vinculosDelUsuario(Auth::id());
        $vinculo    = $this->vinculoElegido($hijos, request('hijo'));
        $estudiante = $vinculo?->estudiante;

        if (!$estudiante) {
            return redirect()->route('padre.vincular');
        }

        $logros = $this->logros->recientesDe($estudiante->id);

        return view('padre.logros', compact('estudiante', 'logros', 'hijos'));
    }
}
