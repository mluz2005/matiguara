<?php

namespace App\Services;

use App\Models\ContenidoNivel;
use App\Models\Nivel;
use App\Models\Racha;
use App\Models\SesionJuego;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\SesionRepository;
use Carbon\Carbon;

/**
 * SERVICIO: SesionService
 * =========================================================================
 * Gestiona el ciclo de vida completo de una sesión de juego.
 *
 * RESPONSABILIDADES:
 *   1. Crear/iniciar una sesión (SesionJuego)
 *   2. Seleccionar el próximo ejercicio de la sesión
 *   3. Registrar cada respuesta (MovimientoSesion)
 *   4. Calcular puntaje según correctitud y tiempo de respuesta
 *   5. Detectar si la sesión debe terminar
 *   6. Cerrar la sesión y actualizar ProgresoEstudiante
 *   7. Actualizar Rachas (aciertos consecutivos y días)
 *
 * SISTEMA DE PUNTAJE:
 *   Respuesta correcta en 1er intento:     10 puntos base
 *   + bonus por velocidad (< 5 seg):       +5 puntos
 *   + bonus por velocidad (5-15 seg):      +2 puntos
 *   Respuesta correcta en reintento:        5 puntos
 *   Respuesta incorrecta:                   0 puntos
 *
 * ACCESO A DATOS: este servicio no construye consultas propias; lee y escribe
 * a través de SesionRepository, EjercicioRepository y EstudianteRepository
 * (capa app/Repositories).
 *
 * Referencia: CU-03 (Resolver ejercicio bilingüe adaptativo)
 * RF: RF-07 (anonimización), RF-04 (algoritmo adaptativo)
 * =========================================================================
 */
class SesionService
{
    // ── Constantes de puntaje ────────────────────────────────────────────
    private const PUNTOS_CORRECTO_1ER_INTENTO  = 10;
    private const PUNTOS_CORRECTO_REINTENTO    = 5;
    private const BONUS_VELOCIDAD_RAPIDA       = 5;   // < 5 segundos
    private const BONUS_VELOCIDAD_MEDIA        = 2;   // 5-15 segundos
    private const LIMITE_TIEMPO_RAPIDO_MS      = 5000;   // 5 segundos en ms
    private const LIMITE_TIEMPO_MEDIO_MS       = 15000;  // 15 segundos en ms

    public function __construct(
        private SesionRepository $sesiones,
        private EjercicioRepository $ejercicios,
        private EstudianteRepository $estudiantes
    ) {
    }

    /**
     * Crea e inicia una nueva sesión de juego.
     *
     * @param  int $estudianteId
     * @param  int $juegoId
     * @param  int $nivelId
     * @param  string|null $deviceTipo  'mobile' | 'tablet' | 'desktop'
     * @return SesionJuego
     */
    public function iniciar(
        int $estudianteId,
        int $juegoId,
        int $nivelId,
        ?string $deviceTipo = null
    ): SesionJuego {
        // Cerrar cualquier sesión en_curso anterior del mismo juego
        // (puede quedar huérfana si el estudiante cerró el navegador)
        $this->sesiones->abandonarEnCurso($estudianteId, $juegoId);

        // Crear la nueva sesión
        return $this->sesiones->crear([
            'estudiante_id'   => $estudianteId,
            'juego_id'        => $juegoId,
            'nivel_id'        => $nivelId,
            'fecha_inicio'    => now(),
            'puntaje_obtenido'=> 0,
            'aciertos'        => 0,
            'errores'         => 0,
            'nivel_superado'  => false,
            'estado'          => 'en_curso',
            'device_tipo'     => $deviceTipo,
        ]);
    }

    /**
     * Obtiene el próximo ejercicio que el estudiante debe responder en esta sesión.
     * Excluye los ejercicios ya respondidos en esta sesión.
     *
     * @param  SesionJuego $sesion
     * @return ContenidoNivel|null  NULL si no quedan ejercicios
     */
    public function obtenerProximoEjercicio(SesionJuego $sesion): ?ContenidoNivel
    {
        // IDs de ejercicios ya respondidos en esta sesión
        $respondidosIds = $this->sesiones->idsEjerciciosRespondidos($sesion);

        // Cuántos ejercicios tiene el nivel en total (respondidos o no)
        $totalEnNivel = $this->ejercicios->contarDelNivel($sesion->nivel_id);
        $totalPorSesion = (int) ($sesion->nivel->total_ejercicios ?? 0);

        // VARIEDAD (2026-10-11): si el nivel tiene MÁS ejercicios que los que se
        // juegan por partida (ej.: 12 cargados, se juegan 5), cada partida toma
        // una selección distinta. El orden "al azar" se calcula con el número de
        // la sesión, así que es ESTABLE: si el niño actualiza la página, ve el
        // mismo ejercicio y no puede saltearlo. Si el nivel tiene justo los
        // ejercicios que se juegan (caso de Flash Matemático), se mantiene el
        // orden fijo de siempre.
        $semilla = ($totalPorSesion > 0 && $totalEnNivel > $totalPorSesion)
            ? (string) $sesion->id
            : null;

        return $this->ejercicios->siguienteNoRespondido($sesion->nivel_id, $respondidosIds, $semilla);
    }

    /**
     * Registra la respuesta del estudiante a un ejercicio.
     * Crea el MovimientoSesion y actualiza los contadores de la sesión.
     *
     * @param  SesionJuego    $sesion
     * @param  ContenidoNivel $ejercicio
     * @param  string         $respuesta       La respuesta ingresada
     * @param  int|null       $tiempoMs        Tiempo de respuesta en milisegundos
     * @return array  [
     *   'movimiento'    => MovimientoSesion,
     *   'es_correcto'   => bool,
     *   'puntos'        => int,
     *   'numero_intento'=> int,
     * ]
     */
    public function registrarRespuesta(
        SesionJuego $sesion,
        ContenidoNivel $ejercicio,
        string $respuesta,
        ?int $tiempoMs = null
    ): array {
        // Determinar el número de intento (¿ya intentó antes este ejercicio?)
        $intentosPrevios = $this->sesiones->contarIntentos($sesion, $ejercicio->id);
        $numeroIntento = $intentosPrevios + 1;

        // Verificar si la respuesta es correcta
        $esCorrect = $ejercicio->esRespuestaCorrecta($respuesta);

        // Clasificar el tipo de error (si lo hay)
        $tipoError = null;
        if (!$esCorrect) {
            $tipoError = $this->clasificarError($ejercicio, $respuesta, $tiempoMs);
        }

        // Calcular puntaje
        $puntos = $this->calcularPuntos($esCorrect, $numeroIntento, $tiempoMs);

        // Crear el registro de movimiento
        $movimiento = $this->sesiones->registrarMovimiento([
            'sesion_id'          => $sesion->id,
            'contenido_id'       => $ejercicio->id,
            'numero_intento'     => $numeroIntento,
            'respuesta_dada'     => $respuesta,
            'es_correcto'        => $esCorrect,
            'tiempo_respuesta_ms'=> $tiempoMs,
            'tipo_error'         => $tipoError,
            'timestamp_mov'      => now(),
        ]);

        // Actualizar contadores de la sesión
        // (el repositorio recarga la sesión para reflejar los incrementos)
        $this->sesiones->acumularResultado($sesion, $puntos, $esCorrect);

        return [
            'movimiento'     => $movimiento,
            'es_correcto'    => $esCorrect,
            'puntos'         => $puntos,
            'numero_intento' => $numeroIntento,
        ];
    }

    /**
     * Verifica si la sesión debe terminar.
     * La sesión termina cuando no quedan más ejercicios del nivel.
     *
     * @param  SesionJuego $sesion
     * @param  Nivel       $nivel
     * @return bool
     */
    public function debeTerminar(SesionJuego $sesion, Nivel $nivel): bool
    {
        $totalRespondidos = $this->sesiones->contarEjerciciosRespondidos($sesion);

        return $totalRespondidos >= $nivel->total_ejercicios;
    }

    /**
     * Cierra la sesión correctamente y actualiza el progreso del estudiante.
     *
     * @param  SesionJuego $sesion
     * @param  bool        $nivelSuperado  El algoritmo 3/2 determinó que superó el nivel
     * @return SesionJuego  Sesión actualizada
     */
    public function cerrar(SesionJuego $sesion, bool $nivelSuperado = false): SesionJuego
    {
        // Verificar independientemente si alcanzó el mínimo de aciertos
        $nivel = $sesion->nivel;
        $superado = $nivelSuperado || ($sesion->aciertos >= $nivel->min_aciertos_pasar);

        // Actualizar la sesión
        $this->sesiones->completar($sesion, $superado);

        // Actualizar el progreso acumulado del estudiante para este nivel
        $this->actualizarProgreso($sesion, $superado);

        return $sesion->fresh();
    }

    /**
     * Actualiza ProgresoEstudiante después de cerrar la sesión.
     * Usa updateOrCreate para manejar la restricción UNIQUE.
     */
    private function actualizarProgreso(SesionJuego $sesion, bool $nivelSuperado): void
    {
        $nivel = $sesion->nivel;

        // Acumula partidas, aciertos y mejor puntaje (crea el registro si no existía)
        $this->estudiantes->registrarPartida($sesion, $nivel, $nivelSuperado);

        // DESBLOQUEO (2026-10-11): superar un nivel abre el siguiente (mismo juego
        // y grado). Lo hace para TODOS los juegos, porque todos cierran por acá.
        // (El sistema adaptativo 3/2 también desbloquea al subir de nivel.)
        if ($nivelSuperado) {
            $siguiente = $nivel->nivelSiguiente();
            if ($siguiente) {
                $this->estudiantes->desbloquearNivel(
                    $sesion->estudiante_id,
                    $sesion->juego_id,
                    $siguiente
                );
            }
        }
    }

    /**
     * Actualiza las rachas del estudiante (aciertos y días consecutivos).
     *
     * @param  int  $estudianteId
     * @param  int  $juegoId
     * @param  bool $esAcierto         ¿La última respuesta fue correcta?
     * @param  int  $diasReinicio      Umbral de días sin jugar para reiniciar
     */
    public function actualizarRacha(
        int $estudianteId,
        int $juegoId,
        bool $esAcierto,
        int $diasReinicio = 3
    ): Racha {
        $racha = $this->estudiantes->obtenerOCrearRacha($estudianteId, $juegoId);

        $hoy = Carbon::today();

        // ── Racha de días ─────────────────────────────────────────────────
        if ($racha->ultima_sesion_fecha === null) {
            // Primera sesión de este juego
            $racha->racha_actual_dias = 1;
        } elseif ($racha->ultima_sesion_fecha->isToday()) {
            // Ya jugó hoy: no incrementar la racha de días
        } elseif ($racha->ultima_sesion_fecha->isYesterday()) {
            // Jugó ayer: mantener la racha
            $racha->racha_actual_dias++;
        } else {
            // Pasaron más de 1 día
            $diasDesdeUltima = $hoy->diffInDays($racha->ultima_sesion_fecha);
            if ($diasDesdeUltima > $diasReinicio) {
                // Se rompió la racha de días
                $racha->racha_actual_dias = 1;
            } else {
                $racha->racha_actual_dias++;
            }
        }

        // Actualizar máximo de días
        if ($racha->racha_actual_dias > $racha->racha_maxima_dias) {
            $racha->racha_maxima_dias = $racha->racha_actual_dias;
        }

        // ── Racha de aciertos ─────────────────────────────────────────────
        if ($esAcierto) {
            $racha->racha_actual_aciertos++;
            if ($racha->racha_actual_aciertos > $racha->racha_maxima_aciertos) {
                $racha->racha_maxima_aciertos = $racha->racha_actual_aciertos;
            }
        } else {
            $racha->racha_actual_aciertos = 0;  // Reiniciar racha de aciertos
        }

        $racha->ultima_sesion_fecha = $hoy;

        return $this->estudiantes->guardarRacha($racha);
    }

    /**
     * Clasifica el tipo de error para análisis posterior por el diagnóstico IA.
     *
     * Heurísticas de clasificación:
     *   'atencion'   → respuesta muy rápida (< 1 seg): no leyó el enunciado
     *   'lectura'    → el número ingresado está en las opciones pero no es el correcto
     *   'calculo'    → la respuesta es cercana al resultado correcto (± 1 o 2)
     *   'conceptual' → la respuesta no tiene relación con la operación
     *
     * @param  ContenidoNivel $ejercicio
     * @param  string         $respuesta
     * @param  int|null       $tiempoMs
     * @return string
     */
    private function clasificarError(
        ContenidoNivel $ejercicio,
        string $respuesta,
        ?int $tiempoMs
    ): string {
        // Respuesta extremadamente rápida → no leyó (atención)
        if ($tiempoMs !== null && $tiempoMs < 1000) {
            return 'atencion';
        }

        // Verificar si la respuesta es numéricamente cercana (± 2)
        $respCorrecta = (int) $ejercicio->respuesta_correcta;
        $respDada     = (int) $respuesta;

        if (is_numeric($respuesta) && abs($respCorrecta - $respDada) <= 2) {
            return 'calculo';  // Error de cálculo leve
        }

        // Si la respuesta dada estaba entre las opciones incorrectas → conceptual
        if ($ejercicio->opciones_json && is_array($ejercicio->opciones_json)) {
            if (in_array($respuesta, $ejercicio->opciones_json)) {
                return 'conceptual';
            }
        }

        return 'lectura';  // Por defecto: dificultad con la lectura del enunciado
    }

    /**
     * Calcula el puntaje obtenido por una respuesta.
     *
     * @param  bool     $esCorrect
     * @param  int      $numeroIntento
     * @param  int|null $tiempoMs
     * @return int
     */
    private function calcularPuntos(bool $esCorrect, int $numeroIntento, ?int $tiempoMs): int
    {
        if (!$esCorrect) {
            return 0;
        }

        if ($numeroIntento === 1) {
            // Primer intento correcto: puntos base + bonus por velocidad
            $puntos = self::PUNTOS_CORRECTO_1ER_INTENTO;

            if ($tiempoMs !== null) {
                if ($tiempoMs <= self::LIMITE_TIEMPO_RAPIDO_MS) {
                    $puntos += self::BONUS_VELOCIDAD_RAPIDA;
                } elseif ($tiempoMs <= self::LIMITE_TIEMPO_MEDIO_MS) {
                    $puntos += self::BONUS_VELOCIDAD_MEDIA;
                }
            }

            return $puntos;
        }

        // Reintento correcto: puntos reducidos (sin bonus de velocidad)
        return self::PUNTOS_CORRECTO_REINTENTO;
    }
}
