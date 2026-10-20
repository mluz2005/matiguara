<?php

namespace App\Services;

use App\Models\Nivel;
use App\Models\Racha;
use App\Models\SesionJuego;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\SesionRepository;

/**
 * SERVICIO: SistemaAdaptativoService
 * =========================================================================
 * Implementa el algoritmo adaptativo 3/2 del sistema (RF-04, RF-13).
 *
 * REGLA 3/2:
 *   - 3 aciertos consecutivos → intentar subir al siguiente nivel
 *   - 2 errores consecutivos  → intentar bajar al nivel anterior
 *
 * El sistema ajusta simultáneamente tres dimensiones de dificultad:
 *   1. Nivel (sube/baja en la tabla 'niveles')
 *   2. Complejidad matemática (manejada por la selección de ContenidoNivel)
 *   3. Tiempo de respuesta (pendiente RF-13 — Semana 3)
 *
 * DISEÑO PEDAGÓGICO:
 * El algoritmo no castiga los errores: si no hay nivel inferior, el
 * estudiante permanece en el mismo nivel y se le anima a continuar.
 * El objetivo es mantener el flujo (flow state) de Csikszentmihalyi:
 * ni muy fácil (aburrimiento) ni muy difícil (frustración).
 *
 * Referencia: CU-17 (Algoritmo adaptativo 3/2)
 * RF: RF-04 (algoritmo adaptativo), RF-13 (progresión dificultad)
 * =========================================================================
 */
class SistemaAdaptativoService
{
    /**
     * Umbral de aciertos consecutivos para intentar subir de nivel.
     * Documentado en el TFG como la regla "3" del algoritmo 3/2.
     */
    private const UMBRAL_ACIERTOS = 3;

    /**
     * Umbral de errores consecutivos para intentar bajar de nivel.
     * Documentado en el TFG como la regla "2" del algoritmo 3/2.
     */
    private const UMBRAL_ERRORES = 2;

    public function __construct(
        private SesionRepository $sesiones,
        private EjercicioRepository $ejercicios,
        private EstudianteRepository $estudiantes
    ) {
    }


    /**
     * Evalúa el rendimiento del estudiante en la sesión actual y
     * determina si corresponde un cambio de nivel.
     *
     * Este método se llama después de CADA respuesta del estudiante.
     *
     * @param  SesionJuego $sesion  La sesión activa del estudiante
     * @return array  Resultado de la evaluación:
     *   [
     *     'cambio_nivel' => bool,       // ¿Hubo cambio de nivel?
     *     'tipo_cambio'  => 'sube'|'baja'|null,
     *     'nivel_nuevo'  => Nivel|null,  // El nuevo nivel (si hubo cambio)
     *     'mensaje_tipo' => string,      // Tipo de mensaje de retro
     *   ]
     */
    public function evaluar(SesionJuego $sesion): array
    {
        // Obtener los últimos movimientos para evaluar la racha actual
        $ultimosMovimientos = $this->sesiones->ultimosMovimientos(
            $sesion,
            max(self::UMBRAL_ACIERTOS, self::UMBRAL_ERRORES)
        );

        if ($ultimosMovimientos->isEmpty()) {
            return $this->sinCambio('acierto');
        }

        // Contar aciertos y errores consecutivos desde el último movimiento
        $aciertosConsecutivos = 0;
        $erroresConsecutivos  = 0;

        foreach ($ultimosMovimientos as $mov) {
            if ($mov->es_correcto) {
                if ($erroresConsecutivos > 0) break;   // Rompió la racha de errores
                $aciertosConsecutivos++;
            } else {
                if ($aciertosConsecutivos > 0) break;  // Rompió la racha de aciertos
                $erroresConsecutivos++;
            }
        }

        // ── Decisión: ¿cambiar de nivel? ──────────────────────────────────
        if ($aciertosConsecutivos >= self::UMBRAL_ACIERTOS) {
            return $this->intentarSubirNivel($sesion);
        }

        if ($erroresConsecutivos >= self::UMBRAL_ERRORES) {
            return $this->intentarBajarNivel($sesion);
        }

        // Sin umbral alcanzado: continuar en el mismo nivel
        $tipo = $ultimosMovimientos->first()->es_correcto ? 'acierto' : 'error';
        return $this->sinCambio($tipo);
    }

    /**
     * Intenta subir al nivel siguiente dentro del mismo juego y grado.
     * Si ya está en el nivel máximo, permanece donde está.
     *
     * @param  SesionJuego $sesion
     * @return array
     */
    private function intentarSubirNivel(SesionJuego $sesion): array
    {
        $nivelActual = $sesion->nivel;
        $nivelSiguiente = $nivelActual->nivelSiguiente();

        if (!$nivelSiguiente) {
            // Ya está en el nivel más alto del grado: felicitación especial
            return [
                'cambio_nivel' => false,
                'tipo_cambio'  => null,
                'nivel_nuevo'  => null,
                'mensaje_tipo' => 'nivel_superado',  // Mensaje de nivel máximo
            ];
        }

        // Desbloquear el nivel siguiente si no estaba desbloqueado
        $this->desbloquearNivel($sesion->estudiante_id, $nivelSiguiente, $sesion->juego_id);

        return [
            'cambio_nivel' => true,
            'tipo_cambio'  => 'sube',
            'nivel_nuevo'  => $nivelSiguiente,
            'mensaje_tipo' => 'nivel_superado',
        ];
    }

    /**
     * Intenta bajar al nivel anterior dentro del mismo juego y grado.
     * Si ya está en el nivel mínimo, permanece donde está (sin penalizar).
     *
     * @param  SesionJuego $sesion
     * @return array
     */
    private function intentarBajarNivel(SesionJuego $sesion): array
    {
        $nivelActual = $sesion->nivel;

        // Buscar nivel anterior (numero_nivel - 1, mismo juego y grado)
        $nivelAnterior = $this->ejercicios->nivelAnterior($nivelActual);

        if (!$nivelAnterior) {
            // Ya está en el nivel más bajo: mantener y alentar
            return [
                'cambio_nivel' => false,
                'tipo_cambio'  => null,
                'nivel_nuevo'  => null,
                'mensaje_tipo' => 'error',  // Mensaje motivador de error
            ];
        }

        return [
            'cambio_nivel' => true,
            'tipo_cambio'  => 'baja',
            'nivel_nuevo'  => $nivelAnterior,
            'mensaje_tipo' => 'error',
        ];
    }

    /**
     * Resultado por defecto cuando no hay cambio de nivel.
     */
    private function sinCambio(string $tipoMensaje): array
    {
        return [
            'cambio_nivel' => false,
            'tipo_cambio'  => null,
            'nivel_nuevo'  => null,
            'mensaje_tipo' => $tipoMensaje,
        ];
    }

    /**
     * Desbloquea el nivel siguiente en progreso_estudiante si no estaba desbloqueado.
     * Usa updateOrCreate para manejar la restricción UNIQUE.
     *
     * @param  int   $estudianteId
     * @param  Nivel $nivel         El nivel a desbloquear
     * @param  int   $juegoId
     */
    public function desbloquearNivel(int $estudianteId, Nivel $nivel, int $juegoId): void
    {
        $this->estudiantes->desbloquearNivel($estudianteId, $juegoId, $nivel);
    }

    /**
     * Verifica si la racha de días del estudiante debe reiniciarse.
     * Implementa RN-08: cadena de consulta juego → institución → default(3).
     *
     * @param  Racha    $racha        La racha del estudiante en este juego
     * @param  int|null $diasConfig   Días configurados (del juego o institución)
     * @return bool  TRUE si la racha debe reiniciarse
     */
    public function debeReiniciarRachaD(Racha $racha, ?int $diasConfig): bool
    {
        if ($racha->ultima_sesion_fecha === null) {
            return false;  // Nunca jugó: no hay racha que reiniciar
        }

        // Días desde la última sesión
        $diasSinJugar = now()->diffInDays($racha->ultima_sesion_fecha);

        // Si no hay configuración explícita, usar el default del sistema (3 días)
        $umbral = $diasConfig ?? 3;

        return $diasSinJugar > $umbral;
    }

    /**
     * Obtiene los días de reinicio de racha para un juego,
     * respetando la cadena de configuración (RN-08):
     *   juego.dias_reinicio_racha → config_institucion.dias_reinicio_racha_default → 3
     *
     * @param  \App\Models\Juego       $juego
     * @param  \App\Models\Estudiante  $estudiante
     * @return int
     */
    public function obtenerDiasRacha(\App\Models\Juego $juego, \App\Models\Estudiante $estudiante): int
    {
        // 1° Prioridad: configuración específica del juego
        if ($juego->dias_reinicio_racha !== null) {
            return $juego->dias_reinicio_racha;
        }

        // 2° Prioridad: configuración de la institución del estudiante
        $matricula = $estudiante->matriculaActiva;
        if ($matricula) {
            $config = $matricula->institucion->config;
            if ($config) {
                return $config->dias_reinicio_racha_default;
            }
        }

        // 3° Default del sistema: 3 días
        return 3;
    }
}
