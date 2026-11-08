<?php

namespace App\Services;

use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\SesionRepository;

/**
 * SERVICIO: GraficosService
 * =========================================================================
 * Prepara los datos de los gráficos del panel docente. No dibuja nada: las
 * vistas (resources/views/docente/graficos) los convierten en dibujos SVG.
 *
 *   delAlumno(): evolución de aciertos por sesión y errores por tipo de un alumno.
 *   delGrupo():  aciertos por alumno, actividad de los últimos 14 días,
 *                alumnos que podrían necesitar apoyo y nivel del grupo por juego.
 *
 * Todos los datos salen de lo que el sistema ya guarda (sesiones y movimientos),
 * y las consultas se hacen solo a través de los repositorios.
 * =========================================================================
 */
class GraficosService
{
    /** Sesiones completadas que muestran los gráficos de un alumno. */
    public const SESIONES_ALUMNO = 15;

    /** Días que abarca el gráfico de actividad del grupo. */
    public const DIAS_ACTIVIDAD = 14;

    /** Para marcar «podría necesitar apoyo»: sesiones recientes que se miran y mínimo para opinar. */
    public const SESIONES_APOYO     = 5;
    public const MIN_SESIONES_APOYO = 3;

    /** Menos de este % de aciertos en las sesiones recientes = podría necesitar apoyo. */
    public const UMBRAL_APOYO_PCT = 50;

    /** Más de estos días sin jugar (habiendo jugado antes) = podría necesitar apoyo. */
    public const DIAS_SIN_JUGAR = 7;

    /** Tipos de error y su explicación para la docente (los asigna SesionService). */
    public const TIPOS_ERROR = [
        'atencion'   => ['Atención',   'Respondió muy rápido, sin leer el enunciado'],
        'lectura'    => ['Lectura',    'Eligió otra de las opciones que tenía delante'],
        'calculo'    => ['Cálculo',    'Se acercó al resultado (se equivocó por poco)'],
        'conceptual' => ['Conceptual', 'La respuesta no se relaciona con la operación'],
    ];

    public function __construct(
        private SesionRepository $sesiones,
        private EstudianteRepository $estudiantes,
        private DocenteRepository $docentes,
        private JuegoRepository $juegos
    ) {
    }

    // ── Un alumno ────────────────────────────────────────────────────────

    /**
     * @return array{
     *   sesiones:int,
     *   puntos:array<int,array{etiqueta:string,valor:int,detalle:string}>,
     *   errores:array<string,array{etiqueta:string,ayuda:string,cantidad:int}>,
     *   total_errores:int
     * }
     */
    public function delAlumno(int $estudianteId): array
    {
        // De la más antigua a la más reciente, para dibujar de izquierda a derecha.
        $sesiones = $this->sesiones->recientesCompletadas($estudianteId, self::SESIONES_ALUMNO)->reverse()->values();

        $puntos = [];
        foreach ($sesiones as $s) {
            $respuestas = $s->aciertos + $s->errores;
            if ($respuestas <= 0) {
                continue;
            }
            $puntos[] = [
                'etiqueta' => $s->fecha_inicio->format('d/m'),
                'valor'    => (int) round($s->aciertos / $respuestas * 100),
                'detalle'  => ($s->juego->nombre_es ?? 'Juego') . ' · nivel ' . ($s->nivel->numero_nivel ?? '?'),
            ];
        }

        $porTipo = $sesiones->isEmpty() ? [] : $this->sesiones->erroresPorTipo($sesiones->pluck('id'));
        $errores = [];
        foreach (self::TIPOS_ERROR as $clave => [$etiqueta, $ayuda]) {
            $errores[$clave] = ['etiqueta' => $etiqueta, 'ayuda' => $ayuda, 'cantidad' => (int) ($porTipo[$clave] ?? 0)];
        }

        return [
            'sesiones'      => $sesiones->count(),
            'puntos'        => $puntos,
            'errores'       => $errores,
            'total_errores' => array_sum(array_column($errores, 'cantidad')),
        ];
    }

    // ── Todo el grupo ────────────────────────────────────────────────────

    /**
     * @return array{
     *   total:int,
     *   aciertos:array<int,array{nombre:string,valor:?int,sesiones:int}>,
     *   actividad:array<int,array{etiqueta:string,dia:string,cantidad:int}>,
     *   apoyo:array{pocos_aciertos:array<int,string>,sin_jugar:array<int,string>,sin_empezar:array<int,string>},
     *   niveles:array<int,array{juego:string,sin_empezar:int,por_nivel:array<int,int>}>
     * }
     */
    public function delGrupo(?int $docenteId): array
    {
        if (!$docenteId) {
            return [];
        }

        $matriculas = $this->docentes->matriculasActivasDelAnio($docenteId, date('Y'))
            ->filter(fn ($m) => $m->estudiante)
            ->sortBy(fn ($m) => $m->estudiante->apellido . ' ' . $m->estudiante->nombre)
            ->values();

        $ids = $matriculas->pluck('estudiante_id');

        $aciertos = [];
        $apoyo    = ['pocos_aciertos' => [], 'sin_jugar' => [], 'sin_empezar' => []];

        foreach ($matriculas as $m) {
            $est    = $m->estudiante;
            $nombre = $this->nombreCorto($est);
            $r      = $this->sesiones->resumenCompletadas($est->id);

            $aciertos[] = [
                'nombre'   => $nombre,
                'valor'    => $r['respuestas'] > 0 ? (int) round($r['aciertos'] / $r['respuestas'] * 100) : null,
                'sesiones' => $r['sesiones'],
            ];

            if ($r['sesiones'] === 0) {
                $apoyo['sin_empezar'][] = $nombre;
                continue;
            }

            $recientes = $this->sesiones->recientesCompletadas($est->id, self::SESIONES_APOYO);
            $resp      = $recientes->sum(fn ($s) => $s->aciertos + $s->errores);
            if ($recientes->count() >= self::MIN_SESIONES_APOYO && $resp > 0
                && ($recientes->sum('aciertos') / $resp * 100) < self::UMBRAL_APOYO_PCT) {
                $apoyo['pocos_aciertos'][] = $nombre;
            }

            $ultima = $this->sesiones->ultimaCompletada($est->id);
            if ($ultima && $ultima->fecha_inicio->lt(now()->subDays(self::DIAS_SIN_JUGAR)->startOfDay())) {
                $apoyo['sin_jugar'][] = $nombre;
            }
        }

        return [
            'total'     => $matriculas->count(),
            'aciertos'  => $aciertos,
            'actividad' => $this->actividad($ids),
            'apoyo'     => $apoyo,
            'niveles'   => $this->nivelesDelGrupo($matriculas),
        ];
    }

    /** «Ana M.»: nombre y la inicial del apellido, para que entre en el gráfico. */
    private function nombreCorto($estudiante): string
    {
        $inicial = mb_substr(trim((string) $estudiante->apellido), 0, 1);
        return trim($estudiante->nombre . ($inicial !== '' ? ' ' . mb_strtoupper($inicial) . '.' : ''));
    }

    /** Alumnos que jugaron cada uno de los últimos DIAS_ACTIVIDAD días (incluye los días en cero). */
    private function actividad($ids): array
    {
        $desde  = now()->subDays(self::DIAS_ACTIVIDAD - 1)->startOfDay();
        $porDia = $ids->isEmpty() ? [] : $this->sesiones->estudiantesActivosPorDia($ids, $desde);

        $dias = [];
        for ($i = 0; $i < self::DIAS_ACTIVIDAD; $i++) {
            $d      = $desde->copy()->addDays($i);
            $dias[] = [
                'etiqueta' => $d->format('d/m'),
                'dia'      => $d->format('Y-m-d'),
                'cantidad' => (int) ($porDia[$d->format('Y-m-d')] ?? 0),
            ];
        }
        return $dias;
    }

    /**
     * Por juego: cuántos alumnos están en cada nivel (el más alto con actividad, dentro
     * del grado que cada uno cursa hoy) y cuántos todavía no empezaron ese juego.
     */
    private function nivelesDelGrupo($matriculas): array
    {
        if ($matriculas->isEmpty()) {
            return [];
        }

        $gradoDe  = $matriculas->mapWithKeys(fn ($m) => [$m->estudiante_id => (int) $m->grado])->all();
        $progresos = $this->estudiantes->progresosConActividad($matriculas->pluck('estudiante_id'));

        // Nivel más alto por alumno y juego, solo del grado actual del alumno.
        $maximo = [];
        foreach ($progresos as $p) {
            if (!$p->nivel || (int) $p->grado !== ($gradoDe[$p->estudiante_id] ?? null)) {
                continue;
            }
            $clave = $p->juego_id . '|' . $p->estudiante_id;
            $maximo[$clave] = max($maximo[$clave] ?? 0, (int) $p->nivel->numero_nivel);
        }

        $grados = array_values(array_unique(array_values($gradoDe)));
        $salida = [];
        foreach ($this->juegos->activos() as $juego) {
            $cantNiveles = 0;
            foreach ($grados as $g) {
                $cantNiveles = max($cantNiveles, (int) $this->juegos->nivelesDelGrado($juego, $g)->max('numero_nivel'));
            }
            if ($cantNiveles === 0) {
                continue;
            }

            $porNivel   = array_fill(1, $cantNiveles, 0);
            $sinEmpezar = 0;
            foreach ($matriculas as $m) {
                $n = $maximo[$juego->id . '|' . $m->estudiante_id] ?? 0;
                if ($n === 0) {
                    $sinEmpezar++;
                } else {
                    $porNivel[min($n, $cantNiveles)]++;
                }
            }
            $salida[] = ['juego' => $juego->nombre_es, 'sin_empezar' => $sinEmpezar, 'por_nivel' => $porNivel];
        }
        return $salida;
    }
}
