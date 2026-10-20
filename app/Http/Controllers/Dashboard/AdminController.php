<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\InstitucionRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\MensajeRetroRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CONTROLADOR: AdminController — Panel administración completo
 * =========================================================================
 * Gestión global del sistema:
 *
 *   INSTITUCIONES (CU-19):
 *     index()    → lista de instituciones con resumen
 *     crear()    → formulario de alta + config automática (CU-27)
 *     editar()   → modificar datos + cambiar configuración (CU-25)
 *     activar()  → activar/desactivar institución
 *
 *   DOCENTES (CU-20):
 *     docentes()     → lista de docentes con asignaciones
 *     crearDocente() → alta de docente + cuenta usuario
 *     asignarDocente() → asignar a institución y grado
 *
 *   CATÁLOGOS:
 *     mensajes()      → mensajes de retroalimentación bilingüe (CU-22)
 *     guardarMensaje()→ crear/editar mensajes
 *
 * RF: RF-14 (gestión de cuentas), RF-16 (configuración institucional)
 * CU: CU-19, CU-20, CU-22, CU-25, CU-27
 * =========================================================================
 */
class AdminController extends Controller
{
    public function __construct(
        private InstitucionRepository $instituciones,
        private DocenteRepository $docentes,
        private EstudianteRepository $estudiantes,
        private SesionRepository $sesiones,
        private DiagnosticoRepository $diagnosticos,
        private UsuarioRepository $usuarios,
        private MensajeRetroRepository $mensajes,
        private JuegoRepository $juegos
    ) {
    }

    // =========================================================================
    // PANEL PRINCIPAL
    // =========================================================================

    public function panel(): View
    {
        return view('admin.panel', [
            'stats' => [
                'instituciones' => $this->instituciones->contarActivas(),
                'docentes'      => $this->docentes->contarActivos(),
                'estudiantes'   => $this->estudiantes->contarActivos(),
                'sesiones'      => $this->sesiones->contarCompletadasGlobal(),
                'diagnosticos'  => $this->diagnosticos->contarExitosos(),
            ],
        ]);
    }

    // =========================================================================
    // GESTIÓN DE INSTITUCIONES (CU-19)
    // =========================================================================

    /**
     * Lista todas las instituciones con resumen de actividad.
     */
    public function index(): View
    {
        $instituciones = $this->instituciones->paginadasConConfig(10);

        return view('admin.instituciones', [
            'instituciones' => $instituciones,
            'modo'          => 'lista',
        ]);
    }

    /**
     * Formulario para crear una nueva institución.
     */
    public function create(): View
    {
        return view('admin.instituciones', [
            'instituciones' => collect(),
            'modo'          => 'crear',
        ]);
    }

    /**
     * Guarda la nueva institución y crea su config_institucion por defecto.
     * CU-27: la config se crea automáticamente al registrar la institución.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'codigo' => ['required', 'string', 'max:20', 'unique:instituciones,codigo'],
            'ciudad' => ['required', 'string', 'max:100'],
            'tipo'   => ['required', 'in:EEB,Colegio,Instituto,Otro'],
        ], [
            'codigo.unique' => 'Ya existe una institución con ese código.',
        ]);

        $inst = $this->instituciones->crear(array_merge($datos, ['activo' => true]));

        // Crear config con valores por defecto (CU-27 / RF-16)
        $this->instituciones->crearConfigPorDefecto($inst->id);

        return redirect()
            ->route('admin.instituciones.index')
            ->with('mensaje', "✅ Institución registrada: {$inst->nombre}");
    }

    /**
     * Formulario para editar una institución existente.
     */
    public function edit(int $id): View
    {
        $inst   = $this->instituciones->buscarConConfig($id);
        $config = $this->instituciones->configOCrearPorDefecto($inst);

        return view('admin.instituciones', [
            'instituciones' => collect([$inst]),
            'inst'          => $inst,
            'config'        => $config,
            'modo'          => 'editar',
        ]);
    }

    /**
     * Actualiza los datos de la institución.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $inst  = $this->instituciones->buscar($id);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'ciudad' => ['required', 'string', 'max:100'],
            'tipo'   => ['required', 'in:EEB,Colegio,Instituto,Otro'],
            'activo' => ['boolean'],
        ]);

        $this->instituciones->actualizar($inst, $datos);

        return redirect()
            ->route('admin.instituciones.index')
            ->with('mensaje', '✅ Institución actualizada.');
    }

    /**
     * Configura los parámetros operativos de la institución (CU-25 / RF-16).
     */
    public function config(int $id): View
    {
        $inst   = $this->instituciones->buscar($id);
        $config = $this->instituciones->configOCrearPorDefecto($inst);

        return view('admin.config-institucion', compact('inst', 'config'));
    }

    /**
     * Guarda la configuración institucional (CU-25).
     */
    public function guardarConfig(Request $request, int $id): RedirectResponse
    {
        $datos = $request->validate([
            'dias_reinicio_racha_default'  => ['required', 'integer', 'between:1,30'],
            'permitir_acceso_grados_ant'   => ['boolean'],
            'meses_conservacion_egresados' => ['required', 'integer', 'between:1,12'],
        ], [
            'dias_reinicio_racha_default.between'  => 'Los días de racha deben estar entre 1 y 30.',
            'meses_conservacion_egresados.between' => 'Los meses deben estar entre 1 y 12.',
        ]);

        // Asegurar que el booleano se maneje correctamente
        $datos['permitir_acceso_grados_ant'] = $request->boolean('permitir_acceso_grados_ant');

        $this->instituciones->actualizarConfig($id, $datos);

        return redirect()
            ->route('admin.instituciones.config', $id)
            ->with('mensaje', '✅ Configuración institucional guardada.');
    }

    // =========================================================================
    // GESTIÓN DE DOCENTES (CU-20)
    // =========================================================================

    /**
     * Lista de todos los docentes con sus asignaciones activas.
     */
    public function docentes(): View
    {
        $docentes = $this->docentes->activosConAsignacion()
            ->sortBy(fn($d) => $d->usuario?->apellido);

        $instituciones = $this->instituciones->activas();

        return view('admin.docentes', compact('docentes', 'instituciones'));
    }

    /**
     * Crea una cuenta de usuario + perfil de docente + asignación inicial.
     * CU-20: alta completa del docente.
     */
    public function crearDocente(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre'         => ['required', 'string', 'max:100'],
            'apellido'       => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'unique:usuarios,email'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'institucion_id' => ['required', 'exists:instituciones,id'],
            'grado'          => ['required', 'integer', 'between:1,3'],
            'turno'          => ['required', 'in:mañana,tarde,noche'],
        ], [
            'email.unique'   => 'Ya existe un usuario con ese correo.',
            'password.min'   => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // 1. Crear cuenta de usuario con perfil 'docente'
        $usuario = $this->usuarios->crear([
            'nombre'        => $datos['nombre'],
            'apellido'      => $datos['apellido'],
            'email'         => $datos['email'],
            'password_hash' => Hash::make($datos['password']),
            'perfil'        => 'docente',
            'activo'        => true,
        ]);

        // 2. Crear perfil extendido de docente
        $docente = $this->docentes->crearPerfil($usuario->id);

        // 3. Crear asignación inicial a la institución y grado
        $this->docentes->crearAsignacion([
            'docente_id'     => $docente->id,
            'institucion_id' => $datos['institucion_id'],
            'grado'          => $datos['grado'],
            'turno'          => $datos['turno'],
            'anio_lectivo'   => date('Y'),
            'activo'         => true,
            'fecha_inicio'   => now()->toDateString(),
        ]);

        return redirect()
            ->route('admin.docentes')
            ->with('mensaje', "✅ Docente creado: {$datos['apellido']}, {$datos['nombre']} — {$datos['email']}");
    }

    /**
     * Desactiva un docente (sin eliminar historial).
     */
    public function desactivarDocente(int $id): RedirectResponse
    {
        $docente = $this->docentes->buscar($id);

        // Desactiva docente y cuenta, y cierra las asignaciones activas
        $this->docentes->desactivar($docente);

        return redirect()
            ->route('admin.docentes')
            ->with('mensaje', 'Docente desactivado.');
    }

    // =========================================================================
    // MENSAJES DE RETROALIMENTACIÓN (CU-22)
    // =========================================================================

    /**
     * Lista y formulario de mensajes bilingüe de retroalimentación.
     */
    public function mensajes(): View
    {
        $mensajes = $this->mensajes->todos();
        $tipos    = ['acierto','error','racha','nivel_superado','inicio_sesion'];
        $contextos= ['general','primer_intento','reintento','maximo_racha'];

        return view('admin.mensajes', compact('mensajes','tipos','contextos'));
    }

    /**
     * Crea o actualiza un mensaje de retroalimentación bilingüe.
     */
    public function guardarMensaje(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'id'        => ['nullable', 'integer', 'exists:mensajes_retro,id'],
            'tipo'      => ['required', 'in:acierto,error,racha,nivel_superado,inicio_sesion'],
            'contexto'  => ['required', 'in:general,primer_intento,reintento,maximo_racha'],
            'texto_es'  => ['required', 'string', 'max:160'],
            'texto_gn'  => ['required', 'string', 'max:160'],
            'activo'    => ['boolean'],
        ], [
            'texto_es.max' => 'El mensaje en castellano no puede superar 160 caracteres.',
            'texto_gn.max' => 'El mensaje en guaraní no puede superar 160 caracteres.',
        ]);

        if ($datos['id']) {
            $this->mensajes->actualizar((int) $datos['id'], $datos);
            $msg = '✅ Mensaje actualizado.';
        } else {
            $datos['activo'] = true;
            $this->mensajes->crear($datos);
            $msg = '✅ Mensaje creado.';
        }

        return redirect()->route('admin.mensajes')->with('mensaje', $msg);
    }

    /**
     * Catálogo de juegos (CU-21) — stub, implementar si hay tiempo.
     */
    public function juegos(): View
    {
        $juegos = $this->juegos->todosConMecanica();
        return view('admin.juegos', compact('juegos'));
    }
}
