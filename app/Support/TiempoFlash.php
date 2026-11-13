<?php

namespace App\Support;

use App\Models\ConfigInstitucion;
use App\Models\Estudiante;

/**
 * Tiempo que se ven los números en Flash Matemático.
 *
 * El tiempo base de cada nivel está en niveles.tiempo_mostrar_ms. A ese valor se le
 * SUMA el ajuste de la institución del alumno (config_institucion.segundos_extra_flash):
 * cada unidad = 1 segundo. El ajuste lo cambia la administración, sin tocar código.
 *
 * Solo se usa para Flash Matemático (los demás juegos no tienen tiempo_mostrar_ms).
 */
final class TiempoFlash
{
    public const EXTRA_MIN = -5;
    public const EXTRA_MAX = 20;

    /** Nunca se muestra menos de esto, aunque el ajuste sea negativo. */
    public const MINIMO_MS = 2000;

    /** Ajuste dentro del rango permitido. */
    public static function acotar(int $segundos): int
    {
        return max(self::EXTRA_MIN, min(self::EXTRA_MAX, $segundos));
    }

    /**
     * Milisegundos que se muestran los números.
     * Si el nivel no es de Flash (base vacía) devuelve 0.
     */
    public static function ms(int|string|null $baseMs, int $segundosExtra): int
    {
        $baseMs = (int) $baseMs;
        if ($baseMs <= 0) return 0;

        return max(self::MINIMO_MS, $baseMs + self::acotar($segundosExtra) * 1000);
    }

    /** Segundos enteros para mostrar en pantalla («⏱ 13 s»). */
    public static function segundos(int|string|null $baseMs, int $segundosExtra): int
    {
        return intdiv(self::ms($baseMs, $segundosExtra), 1000);
    }

    /** Ajuste de la institución de la matrícula activa del alumno (0 si no hay). */
    public static function extraDe(?Estudiante $estudiante): int
    {
        $valor = $estudiante?->matriculaActiva?->institucion?->config?->segundos_extra_flash;

        return self::acotar((int) ($valor ?? 0));
    }

    /** Ajuste configurado para una institución (0 si no tiene configuración). */
    public static function extraDeInstitucion(?int $institucionId): int
    {
        if (!$institucionId) return 0;

        return self::acotar((int) ConfigInstitucion::where('institucion_id', $institucionId)->value('segundos_extra_flash'));
    }
}
