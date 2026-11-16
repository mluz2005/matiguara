<?php

namespace App\Repositories;

use App\Models\DetalleNumeros;
use App\Models\RespuestaNumeros;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: NumerosRepository
 * =========================================================================
 * Acceso a datos de «Mati y los números»: el detalle de cada viaje
 * (detalle_numeros) y las respuestas pregunta por pregunta (respuestas_numeros).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class NumerosRepository
{
    // ── detalle_numeros ──────────────────────────────────────────────────

    public function crearDetalle(array $datos): DetalleNumeros
    {
        return DetalleNumeros::create($datos);
    }

    public function detalleDe(int $sesionId): ?DetalleNumeros
    {
        return DetalleNumeros::where('sesion_id', $sesionId)->first();
    }

    /**
     * Cantidad de viajes COMPLETOS y válidos del niño (cada tres forman un ciclo).
     * Un viaje cortado por el límite de tiempo o marcado como no válido no cuenta.
     */
    public function contarViajesCompletos(int $estudianteId, int $juegoId): int
    {
        return $this->viajesCompletos($estudianteId, $juegoId)->count();
    }

    /** ¿Ya terminó un viaje hoy? (se sugiere un viaje por día) */
    public function terminoViajeHoy(int $estudianteId, int $juegoId): bool
    {
        return $this->viajesCompletos($estudianteId, $juegoId)
            ->whereHas('sesion', fn ($q) => $q->whereDate('fecha_fin', today()))
            ->exists();
    }

    /** Viajes de un niño (de la más reciente a la más antigua), con su sesión. */
    public function viajesDelAlumno(int $estudianteId, int $limite = 30): Collection
    {
        return DetalleNumeros::whereHas('sesion', fn ($q) => $q->where('estudiante_id', $estudianteId))
            ->with('sesion')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Pares «ciclo-viaje» que el niño ya tiene hechos (completos y válidos), sin repetir.
     * Así, si la docente marca un viaje como no válido, ese hueco se vuelve a jugar
     * (en vez de contar «tres viajes» y desordenar los ciclos).
     *
     * @return array<int, array{0:int,1:int}>  lista de [ciclo, viaje]
     */
    public function paresCompletos(int $estudianteId, int $juegoId): array
    {
        return $this->viajesCompletos($estudianteId, $juegoId)
            ->get(['ciclo', 'viaje'])
            ->map(fn ($d) => [(int) $d->ciclo, (int) $d->viaje])
            ->unique(fn ($par) => $par[0] . '-' . $par[1])
            ->values()
            ->all();
    }

    /**
     * Todos los viajes COMPLETOS del niño (válidos o no) con sus respuestas, para calcular el semáforo.
     * Solo se traen las columnas que el cálculo necesita.
     */
    public function viajesParaSemaforo(int $estudianteId): Collection
    {
        return DetalleNumeros::where('completa', true)
            ->whereHas('sesion', fn ($q) => $q->where('estudiante_id', $estudianteId)->where('estado', 'completada'))
            ->with(['respuestas:id,sesion_id,parte,area,es_correcto,cero,fija,nivel,azar'])
            ->orderBy('ciclo')->orderBy('viaje')->orderBy('id')
            ->get();
    }

    private function viajesCompletos(int $estudianteId, int $juegoId)
    {
        return DetalleNumeros::where('completa', true)
            ->where('valida', true)
            ->whereHas('sesion', fn ($q) => $q->where('estudiante_id', $estudianteId)
                ->where('juego_id', $juegoId)
                ->where('estado', 'completada'));
    }

    // ── respuestas_numeros ───────────────────────────────────────────────

    public function respuestasDe(int $sesionId): Collection
    {
        return RespuestaNumeros::where('sesion_id', $sesionId)->orderBy('id')->get();
    }

    public function existeRespuesta(int $sesionId, int $parte, int $orden): bool
    {
        return RespuestaNumeros::where('sesion_id', $sesionId)
            ->where('parte', $parte)->where('orden', $orden)->exists();
    }

    public function guardarRespuesta(array $datos): RespuestaNumeros
    {
        return RespuestaNumeros::create($datos);
    }

    public function contarRespuestas(int $sesionId): int
    {
        return RespuestaNumeros::where('sesion_id', $sesionId)->count();
    }

    /** Órdenes ya respondidos de una parte (para retomar si el niño actualiza la página). */
    public function ordenesRespondidos(int $sesionId, int $parte): array
    {
        return RespuestaNumeros::where('sesion_id', $sesionId)->where('parte', $parte)
            ->pluck('orden')->map(fn ($o) => (int) $o)->all();
    }
}
