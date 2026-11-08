<?php

namespace App\Repositories;

use App\Models\ContenidoNivel;
use App\Models\DetalleLaberinto;
use App\Models\MovimientoSesion;
use App\Models\SesionJuego;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: LaberintoRepository
 * =========================================================================
 * Acceso a datos propios de «El camino a la escuela»: el banco de ejercicios
 * del nivel (contenido_nivel), el detalle de cada partida (detalle_laberinto)
 * y consultas sobre sus movimientos (movimientos_sesion).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class LaberintoRepository
{
    /** Banco de ejercicios del nivel, ordenado por `orden`. */
    public function ejerciciosDelNivel(int $nivelId): Collection
    {
        return ContenidoNivel::where('nivel_id', $nivelId)->orderBy('orden')->get();
    }

    // ── detalle_laberinto ────────────────────────────────────────────────

    public function crearDetalle(int $sesionId, int $semilla, int $obstaculos): DetalleLaberinto
    {
        return DetalleLaberinto::create([
            'sesion_id'        => $sesionId,
            'semilla'          => $semilla,
            'obstaculos_total' => $obstaculos,
        ]);
    }

    public function detalleDe(int $sesionId): ?DetalleLaberinto
    {
        return DetalleLaberinto::where('sesion_id', $sesionId)->first();
    }

    public function guardarCierre(DetalleLaberinto $detalle, array $datos): DetalleLaberinto
    {
        $detalle->update($datos);
        return $detalle->fresh();
    }

    /** Últimas partidas del laberinto de un alumno (con su sesión y nivel), la más reciente primero. */
    public function recientesDelAlumno(int $estudianteId, int $limite = 10): Collection
    {
        return DetalleLaberinto::whereHas('sesion', fn ($q) => $q->where('estudiante_id', $estudianteId))
            ->with('sesion.nivel')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    // ── movimientos_sesion ───────────────────────────────────────────────

    /** Un error de "atención" (respuesta en menos de 1 s) no cuenta como intento. */
    private function contados($consulta)
    {
        return $consulta->where(function ($q) {
            $q->where('es_correcto', true)
              ->orWhere(fn ($w) => $w->where('es_correcto', false)
                  ->where(fn ($t) => $t->whereNull('tipo_error')->orWhere('tipo_error', '!=', 'atencion')));
        });
    }

    /** Intentos que cuentan (sin los errores por apuro) de ese ejercicio en esa partida. */
    public function contarIntentosContados(int $sesionId, int $contenidoId): int
    {
        return $this->contados(
            MovimientoSesion::where('sesion_id', $sesionId)->where('contenido_id', $contenidoId)
        )->count();
    }

    /** ¿Ya existe un intento que cuenta con ese número para ese ejercicio? (para no duplicar si se reenvía) */
    public function existeIntento(int $sesionId, int $contenidoId, int $numeroIntento): bool
    {
        return $this->contados(
            MovimientoSesion::where('sesion_id', $sesionId)->where('contenido_id', $contenidoId)
        )->where('numero_intento', $numeroIntento)->exists();
    }

    /**
     * Por cada ejercicio respondido en la partida: errores que cuentan, aciertos y su `orden`.
     *
     * @return Collection  filas con contenido_id, orden, errores, aciertos
     */
    public function resumenPorEjercicio(int $sesionId): Collection
    {
        return MovimientoSesion::query()
            ->join('contenido_nivel', 'contenido_nivel.id', '=', 'movimientos_sesion.contenido_id')
            ->where('movimientos_sesion.sesion_id', $sesionId)
            ->selectRaw(
                "movimientos_sesion.contenido_id as contenido_id, contenido_nivel.orden as orden, " .
                "SUM(CASE WHEN movimientos_sesion.es_correcto = 0 AND (movimientos_sesion.tipo_error IS NULL OR movimientos_sesion.tipo_error <> 'atencion') THEN 1 ELSE 0 END) as errores, " .
                "SUM(CASE WHEN movimientos_sesion.es_correcto = 1 THEN 1 ELSE 0 END) as aciertos"
            )
            ->groupBy('movimientos_sesion.contenido_id', 'contenido_nivel.orden')
            ->get();
    }
}
