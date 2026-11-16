<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DetalleNumeros;
use App\Models\TamizajeAviso;
use App\Models\TamizajeEstado;
use App\Models\TamizajeFicha;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\NumerosRepository;
use App\Services\TamizajeInformeService;
use App\Services\TamizajeService;
use App\Support\Numeros\DescribirPregunta;
use App\Support\Numeros\TextosTamizaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: TamizajeController — «Atención numérica» (panel de la docente)
 * =========================================================================
 * Muestra a la docente el semáforo de «Mati y los números» de sus alumnos, el detalle de cada
 * niño (ciclos, viajes, pregunta por pregunta), la ficha de observación opcional y la campanita
 * de avisos. Es una herramienta de apoyo: NO diagnostica; invita a mirar con más atención.
 *
 *   GET  docente/atencion                     lista de la clase con el semáforo
 *   GET  docente/atencion/{id}                detalle de un niño (marca sus avisos como leídos)
 *   POST docente/atencion/{id}/ficha          guarda la ficha de observación
 *   POST docente/atencion/{id}/informe        pide el informe redactado con ayuda de la IA (solo a pedido de la docente)
 *   POST docente/atencion/viaje/{detalle}/validez   marca un viaje como no válido o lo vuelve a validar (RF-20)
 *   POST docente/avisos/leer                  marca todos los avisos como leídos
 * =========================================================================
 */
class TamizajeController extends Controller
{
    public function __construct(
        private DocenteRepository $docentes,
        private EstudianteRepository $estudiantes,
        private NumerosRepository $numeros,
        private TamizajeService $tamizaje,
        private TamizajeInformeService $informes
    ) {
    }

    // =========================================================================
    // LISTA DE LA CLASE
    // =========================================================================

    public function index(): View
    {
        $docente    = Auth::user()->docente;
        $asignacion = $docente?->asignacionActiva;

        $matriculas = $this->docentes->matriculasActivasDelAnio($docente?->id, date('Y'))
            ->filter(fn ($m) => $m->estudiante);

        $estados = TamizajeEstado::whereIn('estudiante_id', $matriculas->pluck('estudiante_id'))
            ->get()->keyBy('estudiante_id');
        $fichas = TamizajeFicha::whereIn('estudiante_id', $matriculas->pluck('estudiante_id'))
            ->pluck('estudiante_id')->flip();

        $orden = ['rojo' => 0, 'amarillo' => 1, 'verde' => 2];
        $filas = $matriculas->map(function ($m) use ($estados, $fichas) {
            $estado = $estados->get($m->estudiante_id);
            $res    = $estado?->resultado_json ?? [];
            return [
                'estudiante' => $m->estudiante,
                'grado'      => $m->grado,
                'semaforo'   => $estado?->semaforo,
                'ciclos'     => (int) ($estado?->ciclos_completos ?? 0),
                'areas'      => $res['areas'] ?? [],
                'senales'    => $res['senales'] ?? [],
                'ficha'      => $fichas->has($m->estudiante_id),
            ];
        })->sort(function ($a, $b) use ($orden) {
            return [$orden[$a['semaforo'] ?? ''] ?? 3, $a['estudiante']->apellido] <=> [$orden[$b['semaforo'] ?? ''] ?? 3, $b['estudiante']->apellido];
        })->values();

        $cuentas = [
            'rojo'     => $filas->where('semaforo', 'rojo')->count(),
            'amarillo' => $filas->where('semaforo', 'amarillo')->count(),
            'verde'    => $filas->where('semaforo', 'verde')->count(),
            'sin'      => $filas->whereNull('semaforo')->count(),
        ];

        $config = $asignacion?->institucion?->config;
        [$corteVerde, $corteAmarillo] = TamizajeService::cortesValidos(
            (int) ($config->tamizaje_corte_verde ?? TamizajeService::CORTE_VERDE_DEFECTO),
            (int) ($config->tamizaje_corte_amarillo ?? TamizajeService::CORTE_AMARILLO_DEFECTO)
        );

        return view('docente.tamizaje', compact('filas', 'cuentas', 'asignacion', 'corteVerde', 'corteAmarillo'));
    }

    // =========================================================================
    // DETALLE DE UN NIÑO
    // =========================================================================

    public function alumno(int $id): View|RedirectResponse
    {
        $docente = Auth::user()->docente;
        if (!$this->docentes->esEstudianteDelDocente($id, $docente?->id)) {
            return redirect()->route('docente.tamizaje')
                ->withErrors(['error' => 'No tenés acceso a este estudiante.']);
        }

        $estudiante = $this->estudiantes->buscarOFallar($id);
        $matricula  = $this->docentes->matriculaActivaDelAlumno($id, $docente->id);

        // Verlo es leerlo: los avisos de este niño quedan como leídos.
        TamizajeAviso::where('docente_id', $docente->id)->where('estudiante_id', $id)->whereNull('leido_en')
            ->update(['leido_en' => now()]);

        $resultado = $this->tamizaje->calcular($id);

        $viajes = $this->numeros->viajesDelAlumno($id, 12)->load('respuestas');
        $viajes->each(function (DetalleNumeros $d) {
            $items = $d->partes_json ?? [];
            $d->filas = $d->respuestas->sortBy(fn ($r) => [$r->parte, $r->orden])->map(function ($r) use ($items) {
                $item = $items[(string) $r->parte]['items'][$r->orden] ?? null;
                return [
                    'parte'    => (int) $r->parte,
                    'pregunta' => $item ? DescribirPregunta::enunciado($item) : $r->item_clave,
                    'respondio' => $item ? DescribirPregunta::respuesta($item, (string) $r->respuesta) : (string) $r->respuesta,
                    'correcta' => $item ? DescribirPregunta::respuesta($item, (string) $item['correcta']) : '',
                    'acierto'  => (bool) $r->es_correcto,
                    'segundos' => $r->tiempo_ms !== null ? round($r->tiempo_ms / 1000, 1) : null,
                    'error'    => $r->tipo_error ? (TextosTamizaje::TIPOS_ERROR[$r->tipo_error] ?? $r->tipo_error) : null,
                    'fija'     => (bool) $r->fija,
                    'azar'     => (bool) $r->azar,
                ];
            })->values();

            $contables = $d->respuestas->where('parte', '!=', 1);
            $d->pct_aciertos = $contables->isEmpty() ? null : (int) round(100 * $contables->where('es_correcto', true)->count() / $contables->count());
        });

        $ficha = TamizajeFicha::where('estudiante_id', $id)->first();
        $resumen = $this->resumenParaCopiar($estudiante->nombre_completo, (int) ($matricula?->grado ?? 0), $resultado, $ficha);

        return view('docente.tamizaje-alumno', [
            'estudiante' => $estudiante,
            'grado'      => (int) ($matricula?->grado ?? 0),
            'resultado'  => $resultado,
            'viajes'     => $viajes,
            'ficha'      => $ficha,
            'resumen'    => $resumen,
            'informe'    => $this->informes->ultimoDe($id),
            'espera'     => $this->informes->esperaRestante($id),
        ]);
    }

    // =========================================================================
    // FICHA DE OBSERVACIÓN
    // =========================================================================

    public function guardarFicha(Request $request, int $id): RedirectResponse
    {
        $docente = Auth::user()->docente;
        if (!$this->docentes->esEstudianteDelDocente($id, $docente?->id)) {
            return back()->withErrors(['error' => 'No tenés permiso.']);
        }

        $datos = $request->validate([
            'observaciones'   => ['nullable', 'array'],
            'observaciones.*' => ['string', 'in:' . implode(',', array_keys(TextosTamizaje::FICHA))],
            'nota'            => ['nullable', 'string', 'max:' . TextosTamizaje::FICHA_NOTA_MAX],
        ], [
            'nota.max' => 'La nota puede tener hasta ' . TextosTamizaje::FICHA_NOTA_MAX . ' caracteres.',
        ]);

        $this->tamizaje->guardarFicha($id, $docente->id, $datos['observaciones'] ?? [], $datos['nota'] ?? null);

        return redirect()->route('docente.tamizaje.alumno', $id)->with('mensaje', 'Observaciones guardadas.');
    }

    // =========================================================================
    // INFORME CON AYUDA DE LA IA (solo cuando la docente lo pide — RN-05)
    // =========================================================================

    public function generarInforme(int $id): RedirectResponse
    {
        $docente = Auth::user()->docente;
        if (!$this->docentes->esEstudianteDelDocente($id, $docente?->id)) {
            return redirect()->route('docente.tamizaje')
                ->withErrors(['error' => 'No tenés acceso a este estudiante.']);
        }

        $volver = fn () => redirect()->to(route('docente.tamizaje.alumno', $id) . '#informe');

        $espera = $this->informes->esperaRestante($id);
        if ($espera > 0) {
            return $volver()->withErrors(['error' => "Ya pediste un informe hace un momento. Esperá {$espera} segundos para pedir otro."]);
        }

        $estudiante = $this->estudiantes->buscarOFallar($id);
        $matricula  = $this->docentes->matriculaActivaDelAlumno($id, $docente->id);

        try {
            $informe = $this->informes->generar($estudiante, $docente->id, (int) ($matricula?->grado ?? 0));
        } catch (\DomainException $e) {
            return $volver()->withErrors(['error' => 'Todavía no hay semáforo para este niño: el informe se puede pedir cuando complete al menos 2 ciclos.']);
        }

        return $volver()->with('mensaje', $informe->esDeLaIA()
            ? 'Listo: el informe está redactado. Es un borrador: revisalo antes de usarlo.'
            : 'No pudimos usar la IA en este momento, así que te dejamos un informe estándar con los mismos datos. Podés pedir otro más tarde.');
    }

    // =========================================================================
    // VIAJE NO VÁLIDO (RF-20)
    // =========================================================================

    public function validez(Request $request, int $detalle): RedirectResponse
    {
        $docente = Auth::user()->docente;
        $viaje   = DetalleNumeros::with('sesion')->findOrFail($detalle);
        $id      = (int) ($viaje->sesion?->estudiante_id ?? 0);

        if (!$id || !$this->docentes->esEstudianteDelDocente($id, $docente?->id)) {
            return back()->withErrors(['error' => 'No tenés permiso.']);
        }

        $datos = $request->validate([
            'accion' => ['required', 'in:invalidar,validar'],
            'motivo' => ['nullable', 'string', 'in:' . implode(',', array_keys(TextosTamizaje::MOTIVOS_NO_VALIDA))],
        ]);

        $this->tamizaje->cambiarValidez($viaje, $datos['accion'] === 'validar', $datos['motivo'] ?? null);

        return redirect()->route('docente.tamizaje.alumno', $id)->with('mensaje', $datos['accion'] === 'validar'
            ? 'El viaje volvió a contar.'
            : 'El viaje quedó como no válido: no cuenta para el semáforo y el niño lo volverá a hacer.');
    }

    // =========================================================================
    // CAMPANITA
    // =========================================================================

    public function leerAvisos(): RedirectResponse
    {
        $docente = Auth::user()->docente;
        if ($docente) {
            TamizajeAviso::where('docente_id', $docente->id)->whereNull('leido_en')->update(['leido_en' => now()]);
        }

        return back();
    }

    // =========================================================================
    // TEXTO PARA COPIAR
    // =========================================================================

    private function resumenParaCopiar(string $nombre, int $grado, array $r, ?TamizajeFicha $ficha): string
    {
        $sem = TextosTamizaje::semaforo($r['semaforo']);
        $lineas = [];
        $lineas[] = "Mati y los números — resumen de {$nombre}" . ($grado ? " ({$grado}.° grado)" : '');
        $lineas[] = 'Fecha: ' . now()->format('d/m/Y');
        $lineas[] = 'Semáforo: ' . $sem['etiqueta'] . ' · ciclos completos: ' . $r['ciclos_completos'];

        $areas = [];
        foreach (TextosTamizaje::AREAS as $clave => $nombreArea) {
            $a = $r['areas'][$clave] ?? null;
            $areas[] = $nombreArea . ': ' . (($a['pct'] ?? null) !== null ? $a['pct'] . ' % de aciertos' : 'sin datos suficientes');
        }
        $lineas[] = implode(' · ', $areas);

        if (!empty($r['senales'])) {
            $lineas[] = 'Señales: ' . implode('; ', array_map(fn ($s) => TextosTamizaje::SENALES[$s] ?? $s, $r['senales']));
        }
        if ($ficha && !empty($ficha->observaciones)) {
            $lineas[] = 'Observaciones de la docente: ' . implode('; ', array_map(fn ($o) => TextosTamizaje::FICHA[$o] ?? $o, $ficha->observaciones));
        }
        if ($ficha && $ficha->nota) {
            $lineas[] = 'Nota: ' . $ficha->nota;
        }
        $lineas[] = '';
        $lineas[] = TextosTamizaje::AVISO_LEGAL;

        return implode("\n", $lineas);
    }
}
