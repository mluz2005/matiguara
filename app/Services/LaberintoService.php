<?php

namespace App\Services;

use App\Models\ContenidoNivel;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Repositories\EstudianteRepository;
use App\Repositories\LaberintoRepository;
use App\Repositories\SesionRepository;
use App\Support\DesbloqueoNiveles;
use InvalidArgumentException;
use RuntimeException;

/**
 * SERVICIO: LaberintoService — lógica de «El camino a la escuela»
 * =========================================================================
 * El laberinto, su dibujo y los movimientos del personaje viven en el
 * navegador (public/js/laberinto-v2.js). El servidor es la autoridad de:
 *   · QUÉ ejercicio va en cada obstáculo (banco `contenido_nivel` del nivel);
 *   · si cada respuesta es correcta, los puntos y la clasificación del error;
 *   · el cierre de la partida: nivel superado, estrellas, logros y regla 3/2.
 *
 * Reutiliza SesionService (crear/cerrar partida, racha de días, clasificación
 * del error), LogroService (medallas) y la regla 3/2 por partida de
 * MemoriaService (3 partidas superadas seguidas suben de nivel; 2 no
 * superadas seguidas bajan). No toca el flujo de los otros juegos.
 *
 * REGLAS (documento de requerimientos «El camino a la escuela»):
 *   Puntos: 10 si acierta al primer intento, 5 si al segundo, 0 si falla.
 *   Un error en menos de 1 segundo ("¡Mirá bien!") se registra como error de
 *   atención pero NO cuenta como intento ni suma error.
 *   Cada obstáculo permite 2 intentos: tras el 2.° error se muestra la solución
 *   y el obstáculo vuelve con un ejercicio parecido (ronda 1); si en esa ronda
 *   también falla 2 veces, se abre mostrando la solución.
 *   Nivel superado: como máximo UN obstáculo con la solución mostrada.
 *   Estrellas: 3 sin pistas, sin flecha y sin soluciones · 2 con pistas o flecha ·
 *   1 si hubo alguna solución mostrada.
 * =========================================================================
 */
class LaberintoService
{
    public const PUNTOS_1ER_INTENTO = 10;
    public const PUNTOS_REINTENTO   = 5;
    /** Máximo de obstáculos con solución mostrada para considerar superado el nivel. */
    public const MAX_SOLUCIONES_PARA_SUPERAR = 1;

    public function __construct(
        private SesionService $sesionService,
        private SistemaAdaptativoService $adaptativo,
        private MemoriaService $memoria,
        private LogroService $logros,
        private LaberintoRepository $laberinto,
        private SesionRepository $sesiones,
        private EstudianteRepository $estudiantes
    ) {
    }

    // =========================================================================
    // INICIO: semilla y ejercicios de cada obstáculo
    // =========================================================================

    /** Semilla del laberinto de una partida nueva. */
    public function nuevaSemilla(): int
    {
        return random_int(1000, 900000000);
    }

    /**
     * Ejercicios de cada obstáculo de la partida.
     *
     * Devuelve, por obstáculo (0..n-1), sus dos ejercicios: el propio (ronda 0)
     * y el "parecido" que vuelve si hizo falta ver la solución (ronda 1). La
     * variante se elige según la partida, así dos partidas del mismo nivel no
     * repiten los mismos ejercicios, pero al actualizar la página se ve el mismo.
     *
     * @return array<int, array{slot:int, ejercicios:array<int,array>}>
     * @throws RuntimeException si el nivel no tiene cargado su banco de ejercicios
     */
    public function obstaculosDe(SesionJuego $sesion): array
    {
        $nivel = $sesion->nivel;
        $n     = (int) $nivel->total_ejercicios;                       // cantidad de obstáculos
        $filas = $this->laberinto->ejerciciosDelNivel($nivel->id)->keyBy('orden');

        $variantes = intdiv($filas->count(), max(1, 2 * $n));
        if ($n < 1 || $variantes < 1) {
            throw new RuntimeException("El nivel {$nivel->id} no tiene ejercicios cargados para el laberinto.");
        }

        $obstaculos = [];
        for ($slot = 0; $slot < $n; $slot++) {
            $v = (($sesion->id * 31) + ($slot * 17)) % $variantes;
            $ejercicios = [];
            for ($ronda = 0; $ronda < 2; $ronda++) {
                $orden = $v * (2 * $n) + $slot * 2 + $ronda + 1;
                $fila  = $filas->get($orden) ?? $filas->get($slot * 2 + $ronda + 1);   // respaldo: variante 0
                if (!$fila) {
                    throw new RuntimeException("Falta el ejercicio (nivel {$nivel->id}, orden {$orden}).");
                }
                $ejercicios[] = $this->paraElNavegador($fila);
            }
            $obstaculos[] = ['slot' => $slot, 'ejercicios' => $ejercicios];
        }

        return $obstaculos;
    }

    private function paraElNavegador(ContenidoNivel $c): array
    {
        return [
            'id'                 => $c->id,
            'tipo_operacion'     => $c->tipo_operacion,
            'valor_a'            => (int) $c->valor_a,
            'operador'           => $c->operador,
            'valor_b'            => (int) $c->valor_b,
            'operador_2'         => $c->operador_2,
            'valor_c'            => $c->valor_c !== null ? (int) $c->valor_c : null,
            'respuesta_correcta' => (int) $c->respuesta_correcta,
            'opciones_json'      => array_map('intval', $c->opciones_json ?? []),
            'enunciado_es'       => $c->enunciado_es,
            'enunciado_gn'       => $c->enunciado_gn,
        ];
    }

    // =========================================================================
    // RESPUESTAS
    // =========================================================================

    /**
     * Registra una respuesta a un ejercicio del laberinto.
     *
     * @param  int|null $intentoCliente  Número de intento según el navegador (1 o 2); sirve para
     *                                   no duplicar si el mismo envío llega dos veces.
     * @return array{es_correcto:bool, puntos:int, numero_intento:int, tipo_error:?string,
     *               atencion:bool, duplicado:bool, puntaje_total:int}
     * @throws InvalidArgumentException si el ejercicio no es de este nivel
     */
    public function registrarRespuesta(
        SesionJuego $sesion,
        ContenidoNivel $ejercicio,
        string $respuesta,
        ?int $tiempoMs,
        ?int $intentoCliente = null
    ): array {
        if ((int) $ejercicio->nivel_id !== (int) $sesion->nivel_id) {
            throw new InvalidArgumentException('El ejercicio no pertenece al nivel de esta partida.');
        }

        $contados   = $this->laberinto->contarIntentosContados($sesion->id, $ejercicio->id);
        $esCorrecto = $ejercicio->esRespuestaCorrecta($respuesta);
        $tipoError  = $esCorrecto ? null : $this->sesionService->clasificarError($ejercicio, $respuesta, $tiempoMs);
        $atencion   = !$esCorrecto && $tipoError === 'atencion';
        $numero     = $contados + 1;

        if (!$atencion) {
            $repetido = ($intentoCliente !== null && $this->laberinto->existeIntento($sesion->id, $ejercicio->id, $intentoCliente))
                || $contados >= 2;
            if ($repetido) {
                return [
                    'es_correcto' => $esCorrecto, 'puntos' => 0, 'numero_intento' => $contados,
                    'tipo_error' => $tipoError, 'atencion' => false, 'duplicado' => true,
                    'puntaje_total' => (int) $sesion->fresh()->puntaje_obtenido,
                ];
            }
        }

        $puntos = $esCorrecto ? ($numero === 1 ? self::PUNTOS_1ER_INTENTO : self::PUNTOS_REINTENTO) : 0;

        $this->sesiones->registrarMovimiento([
            'sesion_id'           => $sesion->id,
            'contenido_id'        => $ejercicio->id,
            'numero_intento'      => $numero,
            'respuesta_dada'      => mb_substr($respuesta, 0, 20),
            'es_correcto'         => $esCorrecto,
            'tiempo_respuesta_ms' => $tiempoMs,
            'tipo_error'          => $tipoError,
            'timestamp_mov'       => now(),
        ]);

        if (!$atencion) {
            $this->sesiones->acumularResultado($sesion, $puntos, $esCorrecto);

            $estudiante = $sesion->estudiante;
            $this->sesionService->actualizarRacha(
                $estudiante->id,
                $sesion->juego_id,
                $esCorrecto,
                $this->adaptativo->obtenerDiasRacha($sesion->juego, $estudiante)
            );
        }

        return [
            'es_correcto' => $esCorrecto, 'puntos' => $puntos, 'numero_intento' => $numero,
            'tipo_error' => $tipoError, 'atencion' => $atencion, 'duplicado' => false,
            'puntaje_total' => (int) $sesion->fresh()->puntaje_obtenido,
        ];
    }

    // =========================================================================
    // CIERRE (llegó a la escuela)
    // =========================================================================

    /**
     * Cierra la partida al llegar a la escuela.
     *
     * @param  array $cliente  pistas (int), flecha (bool), chipas_recogidas, chipas_total, duracion_s
     * @return array  sesion, detalle, superado, estrellas, logros (Collection), direccion ('sube'|'baja'|null),
     *                nivel_destino (?Nivel), consecutivas, meta, ya_cerrada (bool)
     */
    public function cerrar(SesionJuego $sesion, array $cliente): array
    {
        $nivel = $sesion->nivel;
        $n     = (int) $nivel->total_ejercicios;
        $detalle = $this->laberinto->detalleDe($sesion->id)
            ?? $this->laberinto->crearDetalle($sesion->id, $this->nuevaSemilla(), $n);

        if (!$sesion->estaEnCurso()) {
            return [
                'sesion' => $sesion, 'detalle' => $detalle, 'superado' => (bool) $sesion->nivel_superado,
                'estrellas' => (int) $detalle->estrellas, 'logros' => collect(), 'direccion' => null,
                'nivel_destino' => null, 'consecutivas' => 0, 'meta' => 0, 'ya_cerrada' => true,
            ];
        }

        $soluciones = $this->contarSolucionesMostradas($sesion->id, $n);
        $pistas     = max(0, min((int) ($cliente['pistas'] ?? 0), $n * 2));
        $flecha     = (bool) ($cliente['flecha'] ?? false);
        $chipasTot  = max(0, min((int) ($cliente['chipas_total'] ?? 0), 20));
        $chipasOk   = max(0, min((int) ($cliente['chipas_recogidas'] ?? 0), $chipasTot));
        $duracion   = max(0, min((int) ($cliente['duracion_s'] ?? 0), 7200));

        $superado  = $soluciones <= self::MAX_SOLUCIONES_PARA_SUPERAR;
        $estrellas = $soluciones > 0 ? 1 : (($pistas > 0 || $flecha) ? 2 : 3);

        $cerrada = $this->sesionService->cerrar($sesion, $superado);

        $detalle = $this->laberinto->guardarCierre($detalle, [
            'obstaculos_total'     => $n,
            'estrellas'            => $estrellas,
            'pistas_usadas'        => $pistas,
            'uso_flecha'           => $flecha,
            'soluciones_mostradas' => $soluciones,
            'chipas_recogidas'     => $chipasOk,
            'chipas_total'         => $chipasTot,
            'duracion_s'           => $duracion,
            'llego'                => true,
        ]);

        // Medallas (usa el detalle ya guardado)
        $racha = $this->estudiantes->rachaDelJuego($cerrada->estudiante_id, $cerrada->juego_id);
        $logros = $racha ? $this->logros->evaluar($cerrada, $racha) : collect();

        // Regla 3/2 por partida: 3 superadas seguidas suben, 2 no superadas seguidas bajan.
        $evaluacion = $this->memoria->evaluarRachaPorPartida($cerrada, $superado);
        $destino = null;
        if ($evaluacion['direccion'] !== null) {
            $destino = $this->memoria->buscarNivelAdyacente($cerrada->nivel, $evaluacion['direccion']);
            if ($destino) {
                DesbloqueoNiveles::abrir($cerrada->estudiante_id, $destino);
            }
        }

        return [
            'sesion' => $cerrada, 'detalle' => $detalle, 'superado' => $superado, 'estrellas' => $estrellas,
            'logros' => $logros, 'direccion' => $destino ? $evaluacion['direccion'] : null,
            'nivel_destino' => $destino, 'consecutivas' => $evaluacion['consecutivas'],
            'meta' => $evaluacion['meta'], 'ya_cerrada' => false,
        ];
    }

    /**
     * Cantidad de obstáculos en los que se mostró la solución: ejercicios con
     * 2 o más errores que cuentan y ningún acierto. Un obstáculo cuenta una sola vez
     * aunque haya pasado por sus dos rondas.
     */
    public function contarSolucionesMostradas(int $sesionId, int $obstaculos): int
    {
        $slots = [];
        foreach ($this->laberinto->resumenPorEjercicio($sesionId) as $f) {
            if ((int) $f->aciertos === 0 && (int) $f->errores >= 2) {
                $slots[intdiv(((int) $f->orden - 1) % max(1, 2 * $obstaculos), 2)] = true;
            }
        }
        return count($slots);
    }

    /** Escenario según el nivel (igual que el navegador): chacra 1-2, arroyo 3-4, campo 5-6, pueblo 7-8. */
    public static function escenarioDe(int $numeroNivel): string
    {
        return ['chacra', 'arroyo', 'campo', 'pueblo'][max(0, min(3, (int) ceil($numeroNivel / 2) - 1))];
    }
}
