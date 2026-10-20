<?php

namespace App\Services;

use App\Models\CatalogoLogro;
use App\Models\Racha;
use App\Models\SesionJuego;
use App\Models\Estudiante;
use App\Repositories\LogroRepository;
use App\Repositories\SesionRepository;
use Illuminate\Support\Collection;

/**
 * SERVICIO: LogroService — adaptado a la estructura real de catalogo_logros
 * =========================================================================
 * Evalúa y otorga logros automáticamente al finalizar cada sesión.
 *
 * ESTRUCTURA REAL DE LA TABLA:
 *   condicion_tipo:  'aciertos_consecutivos' | 'partidas_jugadas'
 *                    'nivel_superado' | 'dias_consecutivos'
 *                    'flash_ultimo_nivel' (medalla Flash Master)
 *   condicion_valor: número que debe alcanzarse para obtener el logro
 *
 * CORRECCIÓN (2026-09-27): la tabla `logros_obtenidos` tiene la columna
 * `logro_id` (no `catalogo_logro_id` como se usaba antes en este archivo).
 * Ese desajuste de nombre causaba el error "Column not found: 1054" al
 * cerrar cualquier sesión de juego, en los tres juegos.
 *
 * CU-07, RF-03
 * =========================================================================
 */
class LogroService
{
    public function __construct(
        private LogroRepository $logros,
        private SesionRepository $sesiones
    ) {
    }

    /**
     * Evalúa qué logros corresponden al finalizar una sesión.
     * Devuelve los logros NUEVOS obtenidos.
     */
    public function evaluar(SesionJuego $sesion, Racha $racha): Collection
    {
        $estudiante   = $sesion->estudiante;
        $logrosNuevos = collect();

        // IDs de logros que el estudiante ya tiene
        $yaTiene = $this->logros->idsObtenidos($estudiante->id);

        // Obtener todos los logros activos que aún no tiene
        $logrosPendientes = $this->logros->pendientes($yaTiene);

        foreach ($logrosPendientes as $logro) {
            $cumple = $this->evaluarCondicion($logro, $sesion, $racha, $estudiante);

            if ($cumple) {
                $nuevo = $this->logros->otorgar($estudiante->id, $logro->id, $sesion->id);
                $logrosNuevos->push($nuevo);
            }
        }

        return $logrosNuevos;
    }

    /**
     * Evalúa si un logro específico se cumple según su condicion_tipo.
     */
    private function evaluarCondicion(
        CatalogoLogro $logro,
        SesionJuego $sesion,
        Racha $racha,
        Estudiante $estudiante
    ): bool {
        $valor = (int) $logro->condicion_valor;

        return match ($logro->condicion_tipo) {

            // Aciertos consecutivos en la racha actual
            'aciertos_consecutivos' => $racha->racha_actual_aciertos >= $valor,

            // Total de partidas jugadas
            'partidas_jugadas' => $this->sesiones->contarCompletadas($estudiante->id) >= $valor,

            // Niveles SUPERADOS (2026-10-12): se cuentan los niveles distintos que el
            // estudiante realmente superó. Antes se contaban los niveles "desbloqueados",
            // que incluyen el siguiente al superado y adelantaba las medallas un nivel.
            'nivel_superado' => $sesion->nivel_superado &&
                $this->sesiones->contarNivelesSuperados($estudiante->id) >= $valor,

            // Flash Master (2026-10-12): superar el ÚLTIMO nivel de Flash Matemático
            // (el de mayor número en el grado del niño). condicion_valor no se usa.
            'flash_ultimo_nivel' => (bool) $sesion->nivel_superado
                && $sesion->juego?->nombre_es === 'Flash Matemático'
                && $sesion->nivel !== null
                && $sesion->nivel->nivelSiguiente() === null,

            // Días consecutivos jugando
            'dias_consecutivos' => $racha->racha_actual_dias >= $valor,

            default => false,
        };
    }
}
