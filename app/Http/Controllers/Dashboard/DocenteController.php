<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\LogroRepository;
use App\Repositories\PadreRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRepository;
use App\Services\DiagnosticoIAService;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * CONTROLADOR: DocenteController — Semana 5 (métodos IA actualizados)
 * =========================================================================
 * Agrega funcionalidad completa de diagnóstico pedagógico IA:
 *
 *   solicitudDiagnostico() → Formulario de solicitud + historial
 *   generarDiagnostico()   → Ejecuta DiagnosticoIAService (POST)
 *   verDiagnostico()       → Vista de un diagnóstico específico
 *
 * CU-11: Solicitar diagnóstico pedagógico IA
 * CU-12: Ver diagnóstico generado
 * RF-10: Diagnóstico con IA bajo demanda
 * RN-05: Solo bajo demanda explícita del docente
 * =========================================================================
 */
class DocenteController extends Controller
{
    public function __construct(
        private DocenteRepository $docentes,
        private EstudianteRepository $estudiantes,
        private SesionRepository $sesiones,
        private DiagnosticoRepository $diagnosticos,
        private JuegoRepository $juegos,
        private LogroRepository $logros,
        private UsuarioRepository $usuarios,
        private PadreRepository $padres,
        private MatriculaService $matriculas
    ) {
    }

    /**
     * Muestra el formulario de solicitud de diagnóstico IA
     * y el historial de diagnósticos anteriores del estudiante.
     * CU-11 (pantalla inicial).
     */
    public function solicitudDiagnostico(int $id): View|RedirectResponse
    {
        $docente    = Auth::user()->docente;
        $estudiante = $this->estudiantes->buscarOFallar($id);

        // Verificar que el estudiante pertenece al docente
        if (!$this->esEstudianteDelDocente($id, $docente?->id)) {
            return redirect()->route('docente.estudiantes')
                ->withErrors(['error' => 'No tenés acceso a este estudiante.']);
        }

        // Historial de diagnósticos del estudiante (más reciente primero)
        $diagnosticos = $this->diagnosticos->delEstudiante($id);

        // Juegos disponibles para filtrar el diagnóstico
        $juegos = $this->juegos->activos();

        // Métricas básicas para mostrar en el formulario
        $totalSesiones   = $this->sesiones->contarCompletadas($id);
        $ultimaSesion    = $this->sesiones->ultimaCompletada($id);

        return view('docente.diagnostico-ia', [
            'estudiante'    => $estudiante,
            'diagnosticos'  => $diagnosticos,
            'juegos'        => $juegos,
            'totalSesiones' => $totalSesiones,
            'ultimaSesion'  => $ultimaSesion,
            'docente'       => $docente,
        ]);
    }

    /**
     * Genera un nuevo diagnóstico IA para el estudiante.
     * POST — acción explícita del docente (RN-05).
     * CU-11 (acción principal).
     */
    public function generarDiagnostico(
        Request $request,
        int $id,
        DiagnosticoIAService $iaService
    ): RedirectResponse {
        $docente    = Auth::user()->docente;
        $estudiante = $this->estudiantes->buscarOFallar($id);

        // Verificar acceso
        if (!$this->esEstudianteDelDocente($id, $docente?->id)) {
            return redirect()->route('docente.estudiantes')
                ->withErrors(['error' => 'Acceso denegado.']);
        }

        // Validar input del formulario
        $datos = $request->validate([
            'juego_id' => ['nullable', 'integer', 'exists:juegos,id'],
        ]);

        // Verificar que tiene sesiones suficientes para el diagnóstico
        $totalSesiones = $this->sesiones->contarCompletadas($id);

        if ($totalSesiones < 1) {
            return redirect()
                ->route('docente.estudiante.diagnostico', $id)
                ->withErrors(['error' =>
                    'El estudiante necesita al menos 1 sesión completada para generar un diagnóstico.'
                ]);
        }

        // Generar el diagnóstico (llama a la API Claude)
        $diagnostico = $iaService->generar(
            $estudiante,
            $docente->id,
            $datos['juego_id'] ?? null
        );

        $mensaje = $diagnostico->estado === 'exitoso'
            ? '✅ Diagnóstico generado correctamente.'
            : '⚠️ El diagnóstico se generó con datos locales (sin conexión a la IA).';

        return redirect()
            ->route('docente.estudiante.ver-diagnostico', [
                'id'           => $id,
                'diagnostico'  => $diagnostico->id,
            ])
            ->with('mensaje', $mensaje);
    }

    /**
     * Muestra un diagnóstico específico en detalle.
     * CU-12 (Ver diagnóstico generado).
     */
   public function verDiagnostico(int $id, $diagnostico): View|RedirectResponse
{
    $docente     = Auth::user()->docente;
    $estudiante  = $this->estudiantes->buscarOFallar($id);
    $diagnostico = $this->diagnosticos->buscar((int) $diagnostico);

    if ($diagnostico->estudiante_id !== $id) {
        return redirect()->route('docente.estudiante.diagnostico', $id);
    }

    $historial = $this->diagnosticos->anterioresA($id, $diagnostico->id, 4);

    return view('docente.ver-diagnostico', [
        'estudiante'  => $estudiante,
        'diagnostico' => $diagnostico,
        'historial'   => $historial,
        'docente'     => $docente,
    ]);
}

    /**
     * Historial completo de diagnósticos de un estudiante.
     */
    public function historialDiagnosticos(int $id): View
    {
        $estudiante   = $this->estudiantes->buscarOFallar($id);
        $diagnosticos = $this->diagnosticos->delEstudiantePaginado($id, 10);

        return view('docente.diagnostico-ia', [
            'estudiante'    => $estudiante,
            'diagnosticos'  => $diagnosticos,
            'juegos'        => $this->juegos->activos(),
            'totalSesiones' => $this->sesiones->contarCompletadas($id),
            'ultimaSesion'  => null,
            'docente'       => Auth::user()->docente,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Métodos existentes (sin cambios — copiados de Semana 3)
    // ─────────────────────────────────────────────────────────────────────

    public function panel(): View
    {
        $usuario    = Auth::user();
        $docente    = $usuario->docente;
        $asignacion = $docente?->asignacionActiva;
        $metricas   = $this->calcularMetricas($docente?->id, $asignacion?->grado);

        return view('docente.panel', compact('usuario','docente','asignacion','metricas'));
    }

    public function estudiantes(): View
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;

        $estudiantes = $this->docentes->matriculasActivasDelAnio($docente?->id, date('Y'))
            ->map(fn($m) => $m->estudiante)
            ->filter()
            ->sortBy('apellido')
            ->map(function ($est) {
                $r = $this->sesiones->resumenCompletadas($est->id);
                $est->sesiones_total = $r['sesiones'];
                $est->porcentaje_ok  = $r['respuestas'] > 0
                    ? round(($r['aciertos'] / $r['respuestas']) * 100)
                    : 0;
                return $est;
            });

       $grado = $asignacion?->grado ?? 1;
     return view('docente.estudiantes', compact('estudiantes','asignacion','grado'));
    }

    public function progresoEstudiante(int $id): View|RedirectResponse
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;
        $estudiante = $this->estudiantes->buscarOFallar($id);

        if (!$this->esEstudianteDelDocente($id, $docente?->id)) {
            return redirect()->route('docente.estudiantes')
                ->withErrors(['error' => 'No tenés acceso a este estudiante.']);
        }

        // El docente ve todo el historial (RF-17): por defecto el grado que cursa hoy
        // el alumno, y con ?grado=N cualquiera de los que cursó.
        $matricula   = $this->docentes->matriculaActivaDelAlumno($id, $docente->id);
        $gradoActual = (int) ($matricula?->grado ?? $asignacion?->grado ?? 1);
        $grados      = $this->matriculas->gradosDelHistorial($estudiante);
        $pedido      = (int) request('grado');
        $grado       = in_array($pedido, $grados, true) ? $pedido : $gradoActual;

        $progresos    = $this->estudiantes->progresosDelGrado($id, $grado);
        $sesiones     = $this->sesiones->recientesCompletadas($id, 10);
        $logros       = $this->logros->recientesDe($id, 5);
        $diagnosticos = $this->diagnosticos->exitososDelEstudiante($id, 3);

        return view('docente.progreso-estudiante',
            compact('estudiante','progresos','sesiones','logros','diagnosticos','grado','gradoActual','grados','asignacion'));
    }

    public function cuentas(): View
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;
        $matriculas = $this->docentes->matriculasActivasDelAnio($docente?->id, date('Y'));

        $faltaMigracion = !$this->existeColumnaCorreo();

        return view('docente.cuentas',
            compact('matriculas','asignacion','faltaMigracion'));
    }

    public function crearCuenta(Request $request): RedirectResponse
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;
        if (!$asignacion) return back()->withErrors(['error'=>'Sin asignación activa.']);

        $datos = $request->validate([
            'nombre'   => ['required','string','max:100'],
            'apellido' => ['required','string','max:100'],
            'pin'      => ['required','string','regex:/^\d{4}$/','confirmed'],
            'email_tutor' => ['nullable','email:rfc','max:191', $this->reglaCorreoDeFamilia()],
        ], [
            'email_tutor.email' => 'El correo del padre, madre o tutor no es válido. Revisalo.',
        ]);

        $correoTutor = $this->normalizarCorreo($datos['email_tutor'] ?? null);
        if ($correoTutor !== null && !$this->existeColumnaCorreo()) {
            return back()->withInput()->withErrors(['error' => $this->avisoFaltaMigracion()]);
        }

        $codigoBase   = strtoupper(substr($asignacion->institucion->codigo ?? 'ESC', 0, 3));
        $turnoLetra   = strtoupper(substr($asignacion->turno, 0, 1));
        $prefijo      = "{$codigoBase}-{$asignacion->grado}{$turnoLetra}-";
        $codigoAcceso = $this->siguienteCodigoAcceso($prefijo);

        $datosEstudiante = [
            'nombre'         => $datos['nombre'],
            'apellido'       => $datos['apellido'],
            'codigo_acceso'  => $codigoAcceso,
            'pin_hash'       => Estudiante::hashearPin($datos['pin']),
            'id_anonimizado' => Estudiante::generarIdAnonimizado(),
            'activo'         => true,
        ];
        // Solo si la columna existe (si falta ejecutar la migración, la cuenta se crea igual, sin correo).
        if ($this->existeColumnaCorreo()) {
            $datosEstudiante['email_tutor'] = $correoTutor;
        }
        $estudiante = $this->estudiantes->crear($datosEstudiante);

        $this->docentes->crearMatricula([
            'estudiante_id'  => $estudiante->id,
            'institucion_id' => $asignacion->institucion_id,
            'docente_id'     => $docente->id,
            'grado'          => $asignacion->grado,
            'anio_lectivo'   => date('Y'),
            'activo'         => true,
            'fecha_inicio'   => now()->toDateString(),
        ]);

        $aviso = $estudiante->email_tutor
            ? ' — La familia entra con su correo y este código.'
            : ' — Sin correo de la familia todavía (podés cargarlo después en "Editar").';

        // El PIN solo se conoce ahora (después queda cifrado). Se guarda en la sesión de la
        // docente para poder imprimirlo en la ficha UNA vez, y se borra al usarlo.
        session()->put('ficha_pin.' . $estudiante->id, $datos['pin']);

        return redirect()->route('docente.cuentas')
            ->with('mensaje', "✅ Cuenta creada: {$estudiante->nombre_completo} — Código: {$codigoAcceso}{$aviso}")
            ->with('ficha_id', $estudiante->id);
    }

    public function editarCuenta(Request $request, int $id): RedirectResponse
    {
        $docente    = Auth::user()->docente;
        $estudiante = $this->estudiantes->buscarOFallar($id);

        if (!$this->esEstudianteDelDocente($id, $docente?->id)) {
            return back()->withErrors(['error' => 'No tenés permiso.']);
        }

        $datos = $request->validate([
            'accion'       => ['required','in:guardar,reset_pin,desactivar,reactivar,cambiar_correo,quitar_correo,cerrar_matricula'],
            'nombre'       => ['required_if:accion,guardar','nullable','string','max:100'],
            'apellido'     => ['required_if:accion,guardar','nullable','string','max:100'],
            'nuevo_pin'    => ['required_if:accion,reset_pin','nullable','regex:/^\d{4}$/'],
            'nuevo_correo' => ['required_if:accion,cambiar_correo','nullable','email:rfc','max:191', $this->reglaCorreoDeFamilia()],
        ], [
            'nuevo_correo.required_if' => 'Escribí el correo del padre, madre o tutor.',
            'nuevo_correo.email'       => 'El correo del padre, madre o tutor no es válido. Revisalo.',
        ]);

        // Guardar desde el panel "Datos del alumno" (columna izquierda de Cuentas):
        // nombre, apellido, correo de la familia y, si se escribió, un PIN nuevo.
        if ($datos['accion'] === 'guardar') {
            $correoNuevo = $this->normalizarCorreo($datos['nuevo_correo'] ?? null);
            $correoActual = $this->normalizarCorreo($estudiante->email_tutor);

            if ($correoNuevo !== $correoActual && !$this->existeColumnaCorreo()) {
                return back()->withErrors(['error' => $this->avisoFaltaMigracion()]);
            }

            $cambios = ['nombre' => trim($datos['nombre']), 'apellido' => trim($datos['apellido'])];
            $conPin  = !empty($datos['nuevo_pin']);
            if ($conPin) {
                $cambios['pin_hash'] = Estudiante::hashearPin($datos['nuevo_pin']);
            }
            $this->estudiantes->actualizar($estudiante, $cambios);

            if ($correoNuevo !== $correoActual) {
                $this->guardarCorreoTutor($estudiante, $correoNuevo);
            }

            $respuesta = back()
                ->with('mensaje', $conPin
                    ? "Datos de {$estudiante->nombre} guardados. El PIN fue cambiado."
                    : "Datos de {$estudiante->nombre} guardados.")
                ->with('seleccionado', $estudiante->id);

            if ($conPin) {
                session()->put('ficha_pin.' . $estudiante->id, $datos['nuevo_pin']);
                $respuesta->with('ficha_id', $estudiante->id);
            }
            return $respuesta;
        }

        // Cierre del año lectivo (RF-18 / RN-10): 1.° y 2.° pasan al grado siguiente;
        // 3.° egresa y su cuenta se desactiva sola al cumplirse los meses de conservación.
        if ($datos['accion'] === 'cerrar_matricula') {
            $matricula = $this->docentes->matriculaActivaDelAlumno($id, $docente->id);
            if (!$matricula) {
                return back()->withErrors(['error' => 'El alumno no tiene una matrícula activa en tu clase.']);
            }

            try {
                if ($matricula->grado >= MatriculaService::GRADO_FINAL) {
                    $baja = $this->matriculas->cerrarMatricula($matricula);
                    $mensaje = "{$estudiante->nombre} egresó de 3.er grado. Su cuenta se desactivará sola el "
                             . $baja->format('d/m/Y') . ' y su historial se conserva.';
                } else {
                    $nueva = $this->matriculas->promover($matricula);
                    $mensaje = "{$estudiante->nombre} pasó a {$nueva->grado}.° grado.";
                }
            } catch (\DomainException $e) {
                return back()->withErrors(['error' => $e->getMessage()])->with('seleccionado', $estudiante->id);
            }

            return back()->with('mensaje', $mensaje);
        }

        if (in_array($datos['accion'], ['cambiar_correo', 'quitar_correo'], true) && !$this->existeColumnaCorreo()) {
            return back()->withErrors(['error' => $this->avisoFaltaMigracion()]);
        }

        match ($datos['accion']) {
            'reset_pin'       => $this->estudiantes->actualizar($estudiante, ['pin_hash' => Estudiante::hashearPin($datos['nuevo_pin'])]),
            'desactivar'      => $this->estudiantes->actualizar($estudiante, ['activo' => false]),
            'reactivar'       => $this->estudiantes->actualizar($estudiante, ['activo' => true]),
            'cambiar_correo'  => $this->guardarCorreoTutor($estudiante, $this->normalizarCorreo($datos['nuevo_correo'])),
            'quitar_correo'   => $this->guardarCorreoTutor($estudiante, null),
        };

        if ($datos['accion'] === 'reset_pin') {
            session()->put('ficha_pin.' . $estudiante->id, $datos['nuevo_pin']);
            return back()
                ->with('mensaje', 'PIN cambiado correctamente.')
                ->with('ficha_id', $estudiante->id);
        }

        return back()
            ->with('mensaje', 'Cuenta actualizada correctamente.')
            ->with('seleccionado', $estudiante->id);
    }

    /**
     * Siguiente código de acceso libre, con el mismo formato de siempre (SAN-1A-007).
     *
     * Antes el número era "cantidad de estudiantes + 1": si alguna vez se borraba
     * un estudiante, el siguiente código podía repetirse y la creación fallaba
     * (el código es único). Ahora se parte del mayor entre la cantidad de
     * estudiantes y el número más alto ya usado, y se avanza mientras el código
     * exista. En el uso normal da el mismo resultado que antes.
     */
    private function siguienteCodigoAcceso(string $prefijo): string
    {
        $mayorUsado = $this->estudiantes->codigosDeAcceso()
            ->map(fn ($c) => preg_match('/(\d+)$/', (string) $c, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        $numero = max($this->estudiantes->contar(), $mayorUsado) + 1;

        do {
            $codigo = $prefijo . str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
            $numero++;
        } while ($this->estudiantes->existeCodigo($codigo));

        return $codigo;
    }

    /**
     * Fichas de acceso imprimibles (para llevar a casa).
     * Una sola: GET /docente/cuentas/{id}/ficha
     */
    public function ficha(int $id): View|RedirectResponse
    {
        $docente   = Auth::user()->docente;
        $matricula = $this->docentes->ultimaMatriculaActivaConFicha($id, $docente?->id);

        if (!$matricula || !$matricula->estudiante) {
            return redirect()->route('docente.cuentas')->withErrors(['error' => 'No tenés permiso.']);
        }

        // El PIN se imprime solo si se viene del aviso "Imprimir ficha con PIN" (justo
        // después de crear la cuenta o cambiar el PIN). Se muestra una sola vez.
        $pin = request()->boolean('con_pin') ? session()->pull('ficha_pin.' . $id) : null;

        return view('docente.ficha-familia', ['fichas' => [$this->datosFicha($matricula, $pin)]]);
    }

    /**
     * Todas las fichas del grado (cuentas activas), listas para imprimir.
     * GET /docente/cuentas/fichas
     */
    public function fichas(): View|RedirectResponse
    {
        $docente    = Auth::user()->docente;
        $matriculas = $this->docentes
            ->matriculasActivasDelAnio($docente?->id, date('Y'), ['estudiante', 'institucion'])
            ->filter(fn ($m) => $m->estudiante && $m->estudiante->activo)
            ->sortBy(fn ($m) => mb_strtolower($m->estudiante->apellido . ' ' . $m->estudiante->nombre))
            ->values();

        if ($matriculas->isEmpty()) {
            return redirect()->route('docente.cuentas')->withErrors(['error' => 'Todavía no hay cuentas activas para imprimir.']);
        }

        return view('docente.ficha-familia', [
            'fichas' => $matriculas->map(fn ($m) => $this->datosFicha($m))->all(),
        ]);
    }

    /**
     * Datos de una ficha. El PIN solo se incluye si se pasa (justo después de crear la cuenta
     * o de cambiar el PIN): el sistema guarda únicamente su versión cifrada y no puede leerlo.
     */
    private function datosFicha(Matricula $matricula, ?string $pin = null): array
    {
        $est = $matricula->estudiante;
        return [
            'pin'     => ($pin !== null && preg_match('/^\d{4}$/', $pin)) ? $pin : null,
            'nombre'  => trim($est->nombre . ' ' . $est->apellido),
            'codigo'  => $est->codigo_acceso,
            'correo'  => $est->email_tutor,
            'grado'   => $matricula->grado,
            'escuela' => $matricula->institucion?->nombre,
        ];
    }

    /** ¿Ya se ejecutó la migración que agrega estudiantes.email_tutor? */
    private function existeColumnaCorreo(): bool
    {
        return Schema::hasColumn('estudiantes', 'email_tutor');
    }

    private function avisoFaltaMigracion(): string
    {
        return 'Falta actualizar la base de datos para guardar el correo de la familia: ejecutá "php artisan migrate" en la carpeta del proyecto y volvé a intentar.';
    }

    /**
     * Pasa el correo a minúsculas y sin espacios; vacío → null.
     */
    private function normalizarCorreo(?string $correo): ?string
    {
        $correo = mb_strtolower(trim((string) $correo));
        return $correo === '' ? null : $correo;
    }

    /**
     * Regla de validación: el correo de la familia no puede ser el de una
     * cuenta de docente o administración (el acceso de familias nunca debe
     * abrir una cuenta de personal del sistema).
     */
    private function reglaCorreoDeFamilia(): \Closure
    {
        return function (string $atributo, $valor, \Closure $fallar) {
            $correo = $this->normalizarCorreo($valor);
            if ($correo === null) return;

            if ($this->usuarios->existeCorreoDePersonal($correo)) {
                $fallar('Ese correo pertenece a una cuenta de docente o de administración. Usá el correo de la familia.');
            }
        };
    }

    /**
     * Guarda (o quita) el correo del tutor. Si se cambia o se quita, la cuenta
     * de familia que tenía el correo anterior deja de ver a este niño/a.
     */
    private function guardarCorreoTutor(Estudiante $estudiante, ?string $nuevo): void
    {
        $anterior = $this->normalizarCorreo($estudiante->email_tutor);
        $this->estudiantes->actualizar($estudiante, ['email_tutor' => $nuevo]);

        if ($anterior !== null && $anterior !== $nuevo) {
            $usuariosAnteriores = $this->usuarios->idsDeFamiliaPorCorreo($anterior);

            $this->padres->eliminarVinculos($estudiante->id, $usuariosAnteriores);
        }
    }

    public function configurarJuegos(): View
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;
        $grado      = $asignacion?->grado ?? 1;
        $juegos     = $this->juegos->activosConMecanica();
        $configuraciones = $this->docentes->habilitacionDeJuegos($docente?->id, $grado);

        return view('docente.juegos-config', compact('juegos','configuraciones','grado','asignacion'));
    }

    public function guardarConfigJuegos(Request $request): RedirectResponse
    {
        $docente        = Auth::user()->docente;
        $asignacion     = $docente?->asignacionActiva;
        $grado          = $asignacion?->grado ?? 1;
        $juegosMarcados = $request->input('juegos', []);

        $this->juegos->idsActivos()->each(function ($juegoId) use ($docente, $grado, $juegosMarcados) {
            $this->docentes->guardarHabilitacion(
                $docente->id,
                $juegoId,
                $grado,
                in_array($juegoId, $juegosMarcados)
            );
        });

        return back()->with('mensaje', '✅ Configuración guardada.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────

    private function esEstudianteDelDocente(int $estudianteId, ?int $docenteId): bool
    {
        return $this->docentes->esEstudianteDelDocente($estudianteId, $docenteId);
    }

    private function calcularMetricas(?int $docenteId, ?int $grado): array
    {
        if (!$docenteId) return [];
        $ids = $this->docentes->idsEstudiantesActivos($docenteId);
        $r   = $this->sesiones->resumenCompletadas($ids);

        return [
            'total_estudiantes' => $ids->count(),
            'total_sesiones'    => $r['sesiones'],
            'pct_global'        => $r['respuestas'] > 0 ? round(($r['aciertos'] / $r['respuestas']) * 100) : 0,
            'activos_hoy'       => $this->sesiones->contarEstudiantesActivosHoy($ids),
        ];
    }
}
