<?php

namespace App\Services;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Repositories\SesionRepository;

/**
 * SERVICIO: TiempoJuegoService — límite de juego por sesión y pausa obligatoria
 * =========================================================================
 * Para cuidar la salud de los niños (vista, postura), TODOS los juegos de
 * MatiGuarã comparten esta regla:
 *
 *   RN-L10  Un alumno juega como máximo `limite_sesion_min` minutos de juego
 *           ACTIVO seguidos (defecto 8). Al llegar al límite termina el
 *           ejercicio que tiene en pantalla y pasa a la pantalla de descanso.
 *   RN-L11  Después debe esperar `pausa_entre_sesiones_min` minutos (defecto 30)
 *           antes de volver a jugar. Si deja de jugar antes del límite y vuelve
 *           sin haber cumplido la pausa, el tiempo jugado se SUMA (no puede
 *           saltearse el límite saliendo y entrando).
 *
 * Ambos valores los fija el docente en su panel (0 = sin límite / sin pausa).
 * No agrega tablas de sesiones: todo se calcula con `sesiones_juego`, que ya
 * guarda cuándo empezó y terminó cada partida de cualquier juego.
 *
 * CÓMO SE CUENTA EL TIEMPO ("ciclo de juego"):
 *   - Cuenta solo el tiempo de las partidas (no el que pasa en el menú).
 *   - Una partida sin fecha_fin (en curso) cuenta hasta ahora, pero nunca más que
 *     el límite ni más allá del inicio de la partida siguiente (así una partida
 *     huérfana — cerró el navegador — no bloquea para siempre).
 *   - Si entre el fin de una partida y el inicio de la siguiente pasó al menos la
 *     pausa, el ciclo empieza de nuevo en cero.
 *
 * `calcular()` es una función pura (recibe números, no toca la base) para poder
 * probarla sin base de datos.
 * =========================================================================
 */
class TiempoJuegoService
{
    /** Ventana hacia atrás en la que se buscan partidas (horas). Alcanza para cualquier pausa razonable. */
    private const VENTANA_HORAS = 24;

    public function __construct(private SesionRepository $sesiones)
    {
    }

    /**
     * Límite y pausa (en segundos) que corresponden al estudiante, según su docente.
     *
     * @return array{limite_seg:int, pausa_seg:int}
     */
    public function configuracion(Estudiante $estudiante): array
    {
        $docente = $estudiante->matriculaActiva?->docente;

        $limite = $docente?->limite_sesion_min;
        $pausa  = $docente?->pausa_entre_sesiones_min;

        return [
            'limite_seg' => max(0, (int) ($limite ?? Docente::LIMITE_SESION_DEFECTO_MIN)) * 60,
            'pausa_seg'  => max(0, (int) ($pausa  ?? Docente::PAUSA_DEFECTO_MIN)) * 60,
        ];
    }

    /**
     * Estado actual de tiempo de juego del estudiante.
     *
     * @return array{limite_seg:int, pausa_seg:int, jugado_seg:int, restante_seg:int,
     *               bloqueado:bool, vuelve_en_seg:int}
     */
    public function estado(Estudiante $estudiante): array
    {
        $cfg   = $this->configuracion($estudiante);
        $ahora = time();

        $filas = $this->sesiones
            ->sesionesParaTiempo($estudiante->id, now()->subHours(self::VENTANA_HORAS))
            ->map(fn ($s) => [
                'inicio' => $s->fecha_inicio->getTimestamp(),
                'fin'    => $s->fecha_fin?->getTimestamp(),
            ])
            ->all();

        return self::calcular($filas, $ahora, $cfg['limite_seg'], $cfg['pausa_seg']);
    }

    /**
     * Cierra las partidas en curso (de cualquier juego) y deja al niño en descanso.
     * El tiempo de pausa empieza a correr desde este momento.
     */
    public function mandarADescansar(Estudiante $estudiante): void
    {
        $this->sesiones->abandonarTodasEnCurso($estudiante->id);
    }

    /**
     * Cálculo puro del ciclo de juego.
     *
     * @param  array<int, array{inicio:int, fin:?int}>  $partidas  De la más vieja a la más nueva (marcas de tiempo)
     * @param  int  $ahora      Marca de tiempo actual
     * @param  int  $limiteSeg  0 = sin límite
     * @param  int  $pausaSeg   0 = sin pausa
     * @return array{limite_seg:int, pausa_seg:int, jugado_seg:int, restante_seg:int,
     *               bloqueado:bool, vuelve_en_seg:int}
     */
    public static function calcular(array $partidas, int $ahora, int $limiteSeg, int $pausaSeg): array
    {
        $sinLimite = [
            'limite_seg' => $limiteSeg, 'pausa_seg' => $pausaSeg,
            'jugado_seg' => 0, 'restante_seg' => 0,
            'bloqueado' => false, 'vuelve_en_seg' => 0,
        ];
        if ($limiteSeg <= 0) {
            return $sinLimite;
        }

        $acumulado = 0;
        $ultimoFin = null;
        $lista     = array_values($partidas);

        foreach ($lista as $i => $p) {
            $inicio    = (int) $p['inicio'];
            $siguiente = $lista[$i + 1]['inicio'] ?? null;

            // Fin real si ya cerró; si no, hasta ahora (o hasta que empezó la siguiente), con tope.
            $fin = $p['fin'] !== null
                ? (int) $p['fin']
                : min($siguiente !== null ? (int) $siguiente : $ahora, $inicio + $limiteSeg);
            $fin = max($fin, $inicio);

            // Una pausa cumplida entre partidas reinicia el ciclo.
            if ($ultimoFin !== null && ($inicio - $ultimoFin) >= $pausaSeg) {
                $acumulado = 0;
            }

            $acumulado += min($fin - $inicio, $limiteSeg);
            $ultimoFin  = $fin;
        }

        // Si desde la última partida ya pasó la pausa, el ciclo termina: puede jugar de nuevo.
        if ($ultimoFin === null || ($ahora - $ultimoFin) >= $pausaSeg) {
            $acumulado = 0;
        }

        $bloqueado = $acumulado >= $limiteSeg;

        return [
            'limite_seg'    => $limiteSeg,
            'pausa_seg'     => $pausaSeg,
            'jugado_seg'    => $acumulado,
            'restante_seg'  => max(0, $limiteSeg - $acumulado),
            'bloqueado'     => $bloqueado,
            'vuelve_en_seg' => $bloqueado ? max(0, $pausaSeg - ($ahora - (int) $ultimoFin)) : 0,
        ];
    }
}
