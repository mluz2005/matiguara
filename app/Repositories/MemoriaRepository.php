<?php

namespace App\Repositories;

use App\Models\MovimientoMemoria;
use App\Models\SesionJuego;
use App\Models\TarjetaNivel;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: MemoriaRepository
 * =========================================================================
 * Capa de acceso a datos del juego "Encuentra el par": tarjetas de cada
 * nivel (tarjetas_nivel) y sus intentos (movimientos_memoria).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class MemoriaRepository
{
    /** Tarjetas activas del nivel (pool del que se arma el mazo). */
    public function tarjetasActivasDelNivel(int $nivelId): Collection
    {
        return TarjetaNivel::where('nivel_id', $nivelId)
            ->activas()
            ->get();
    }

    /** Registra un intento (par de tarjetas destapadas) de la sesión. */
    public function registrarMovimiento(array $datos): MovimientoMemoria
    {
        return MovimientoMemoria::create($datos);
    }

    /** Los últimos $cantidad intentos de la sesión, del más reciente al más antiguo. */
    public function ultimosMovimientos(SesionJuego $sesion, int $cantidad): Collection
    {
        return MovimientoMemoria::where('sesion_id', $sesion->id)
            ->orderBy('id', 'desc')
            ->take($cantidad)
            ->get();
    }
}
