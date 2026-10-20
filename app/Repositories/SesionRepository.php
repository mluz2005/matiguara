<?php

namespace App\Repositories;

use App\Models\MovimientoSesion;
use App\Models\SesionJuego;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: SesionRepository
 * =========================================================================
 * Capa de acceso a datos de las sesiones de juego (tablas sesiones_juego y
 * movimientos_sesion). Los servicios (SesionService, SistemaAdaptativoService,
 * DiagnosticoIAService) consultan y escriben a través de esta clase, sin
 * construir consultas Eloquent propias.
 *
 * Capa: app/Repositories (acceso a datos) — ver Tabla de capas de la
 * arquitectura MVC extendida y Figura 6 (secuencia de resolución de ejercicio).
 * =========================================================================
 */
class SesionRepository
{
    // ── Sesiones ─────────────────────────────────────────────────────────

    /**
     * Marca como 'abandonada' toda sesión 'en_curso' previa del mismo
     * estudiante y juego (puede quedar huérfana si cerró el navegador).
     *
     * @return int Cantidad de sesiones cerradas
     */
    public function abandonarEnCurso(int $estudianteId, int $juegoId): int
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('juego_id', $juegoId)
            ->where('estado', 'en_curso')
            ->update([
                'estado'    => 'abandonada',
                'fecha_fin' => now(),
            ]);
    }

    /** Crea una sesión nueva con los datos indicados. */
    public function crear(array $datos): SesionJuego
    {
        return SesionJuego::create($datos);
    }

    /** Marca la sesión como completada y registra si superó el nivel. */
    public function completar(SesionJuego $sesion, bool $nivelSuperado): void
    {
        $sesion->update([
            'estado'         => 'completada',
            'fecha_fin'      => now(),
            'nivel_superado' => $nivelSuperado,
        ]);
    }

    /**
     * Suma los puntos y el acierto/error de la última respuesta a los
     * contadores de la sesión y recarga el modelo.
     */
    public function acumularResultado(SesionJuego $sesion, int $puntos, bool $esCorrecto): void
    {
        $sesion->increment('puntaje_obtenido', $puntos);
        if ($esCorrecto) {
            $sesion->increment('aciertos');
        } else {
            $sesion->increment('errores');
        }

        $sesion->refresh();
    }

    /**
     * Sesiones completadas de un estudiante (opcionalmente de un solo juego),
     * con su nivel y su juego cargados. Insumo del diagnóstico pedagógico.
     */
    public function completadasDe(int $estudianteId, ?int $juegoId = null): Collection
    {
        $consulta = SesionJuego::where('estudiante_id', $estudianteId)
            ->where('estado', 'completada');

        if ($juegoId) {
            $consulta->where('juego_id', $juegoId);
        }

        return $consulta->with(['nivel', 'juego'])->get();
    }

    // ── Movimientos (respuestas) ─────────────────────────────────────────

    /** IDs de los ejercicios ya respondidos en la sesión (sin repetir). */
    public function idsEjerciciosRespondidos(SesionJuego $sesion): array
    {
        return $sesion->movimientos()
            ->pluck('contenido_id')
            ->unique()
            ->toArray();
    }

    /** Cantidad de ejercicios distintos respondidos en la sesión. */
    public function contarEjerciciosRespondidos(SesionJuego $sesion): int
    {
        return $sesion->movimientos()
            ->distinct('contenido_id')
            ->count('contenido_id');
    }

    /** Cantidad de intentos previos del ejercicio dentro de la sesión. */
    public function contarIntentos(SesionJuego $sesion, int $contenidoId): int
    {
        return $sesion->movimientos()
            ->where('contenido_id', $contenidoId)
            ->count();
    }

    /** Registra una respuesta (movimiento) de la sesión. */
    public function registrarMovimiento(array $datos): MovimientoSesion
    {
        return MovimientoSesion::create($datos);
    }

    /** Los últimos $cantidad movimientos de la sesión, del más reciente al más antiguo. */
    public function ultimosMovimientos(SesionJuego $sesion, int $cantidad): Collection
    {
        return $sesion->movimientos()
            ->orderBy('id', 'desc')
            ->take($cantidad)
            ->get();
    }

    // ── Consultas para el diagnóstico pedagógico ─────────────────────────

    /**
     * Cantidad de errores por tipo de error, para las sesiones indicadas.
     *
     * @param  iterable $sesionIds
     * @return array<string,int>  [tipo_error => cantidad]
     */
    public function erroresPorTipo($sesionIds): array
    {
        return MovimientoSesion::whereIn('sesion_id', $sesionIds)
            ->where('es_correcto', false)
            ->whereNotNull('tipo_error')
            ->groupBy('tipo_error')
            ->selectRaw('tipo_error, COUNT(*) as cantidad')
            ->pluck('cantidad', 'tipo_error')
            ->toArray();
    }

    /** Tiempo de respuesta promedio (ms) de las sesiones indicadas; null si no hay datos. */
    public function tiempoRespuestaPromedioMs($sesionIds)
    {
        return MovimientoSesion::whereIn('sesion_id', $sesionIds)
            ->whereNotNull('tiempo_respuesta_ms')
            ->avg('tiempo_respuesta_ms');
    }

    /** Movimientos de las sesiones indicadas, con su ejercicio cargado. */
    public function movimientosConEjercicio($sesionIds): Collection
    {
        return MovimientoSesion::whereIn('sesion_id', $sesionIds)
            ->with('ejercicio')
            ->get();
    }

    // ── Consultas de resumen del estudiante ──────────────────────────────

    /** Datos mínimos (nivel y si lo superó) de todas las sesiones del estudiante. */
    public function nivelesJugados(int $estudianteId): Collection
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->get(['nivel_id', 'nivel_superado']);
    }

    /** IDs de los niveles que el estudiante superó al menos una vez. */
    public function idsNivelesSuperados(int $estudianteId): array
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('nivel_superado', true)
            ->pluck('nivel_id')
            ->all();
    }

    /** Cantidad de niveles DISTINTOS que el estudiante superó. */
    public function contarNivelesSuperados(int $estudianteId): int
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('nivel_superado', true)
            ->distinct()
            ->count('nivel_id');
    }

    /** Cantidad de sesiones completadas del estudiante. */
    public function contarCompletadas(int $estudianteId): int
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('estado', 'completada')
            ->count();
    }

    /** Suma del puntaje de las sesiones completadas del estudiante. */
    public function puntajeTotalCompletadas(int $estudianteId)
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('estado', 'completada')
            ->sum('puntaje_obtenido');
    }

    /**
     * Resultado (nivel_superado) de las últimas partidas COMPLETADAS del mismo
     * estudiante, juego y nivel hasta la sesión indicada, de la más reciente a
     * la más antigua. Insumo de la regla por partida de "Encuentra el par".
     */
    public function resultadosRecientesDelNivel(SesionJuego $sesionCerrada, int $limite): Collection
    {
        return SesionJuego::where('estudiante_id', $sesionCerrada->estudiante_id)
            ->where('juego_id', $sesionCerrada->juego_id)
            ->where('nivel_id', $sesionCerrada->nivel_id)
            ->where('estado', 'completada')
            ->where('id', '<=', $sesionCerrada->id)
            ->orderByDesc('id')
            ->limit($limite)
            ->pluck('nivel_superado');
    }

    // ── Resumen de rendimiento (panel del docente y de la familia) ───────

    /**
     * Resumen de las sesiones COMPLETADAS de uno o varios estudiantes:
     * cantidad de sesiones, aciertos y total de respuestas (aciertos + errores).
     *
     * @param  int|array|\Illuminate\Support\Collection  $estudianteIds
     * @return array{sesiones:int, aciertos:int, respuestas:int}
     */
    public function resumenCompletadas($estudianteIds): array
    {
        $ids = is_array($estudianteIds) || $estudianteIds instanceof Collection
            ? $estudianteIds
            : [$estudianteIds];

        $fila = SesionJuego::whereIn('estudiante_id', $ids)
            ->where('estado', 'completada')
            ->selectRaw('COUNT(*) as sesiones, COALESCE(SUM(aciertos), 0) as aciertos, COALESCE(SUM(aciertos + errores), 0) as respuestas')
            ->first();

        return [
            'sesiones'   => (int) ($fila->sesiones ?? 0),
            'aciertos'   => (int) ($fila->aciertos ?? 0),
            'respuestas' => (int) ($fila->respuestas ?? 0),
        ];
    }

    /** Última sesión completada del estudiante (null si no tiene). */
    public function ultimaCompletada(int $estudianteId): ?SesionJuego
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('estado', 'completada')
            ->latest('fecha_inicio')
            ->first();
    }

    /** Las últimas $limite sesiones completadas, con juego y nivel, de la más reciente a la más antigua. */
    public function recientesCompletadas(int $estudianteId, int $limite): Collection
    {
        return SesionJuego::where('estudiante_id', $estudianteId)
            ->where('estado', 'completada')
            ->with(['juego', 'nivel'])
            ->orderBy('fecha_inicio', 'desc')
            ->take($limite)
            ->get();
    }

    /** Cantidad de estudiantes distintos (entre los indicados) que jugaron hoy. */
    public function contarEstudiantesActivosHoy($estudianteIds): int
    {
        return SesionJuego::whereIn('estudiante_id', $estudianteIds)
            ->whereDate('fecha_inicio', today())
            ->distinct('estudiante_id')
            ->count('estudiante_id');
    }

    /** Cantidad de sesiones completadas en todo el sistema. */
    public function contarCompletadasGlobal(): int
    {
        return SesionJuego::where('estado', 'completada')->count();
    }
}
