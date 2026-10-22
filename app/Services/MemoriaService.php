<?php

namespace App\Services;

use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Models\TarjetaNivel;
use App\Repositories\EjercicioRepository;
use App\Repositories\MemoriaRepository;
use App\Repositories\SesionRepository;
use Illuminate\Support\Collection;

/**
 * SERVICIO: MemoriaService
 * =========================================================================
 * Lógica propia del juego "Encuentra el par". Reutiliza SesionService
 * para todo lo genérico (iniciar/cerrar sesión, actualizar racha) y solo
 * implementa lo específico de este juego: generación del mazo, cálculo
 * de puntaje (10 por acierto, sin bono de velocidad), y su propia evaluación 3/2 (porque
 * opera sobre movimientos_memoria, no sobre movimientos_sesion).
 *
 * POR QUÉ NO SE REUTILIZA SistemaAdaptativoService DIRECTAMENTE:
 * Ese servicio lee $sesion->movimientos() (relación hacia movimientos_sesion,
 * ligada a contenido_nivel). El juego de memoria usa movimientos_memoria
 * (ligada a tarjetas_nivel). En vez de forzar una tabla ajena a este
 * esquema, se reimplementa aquí la misma regla 3/2 sobre la tabla correcta
 * — mismo comportamiento, sin tocar código que ya funciona en los otros
 * juegos.
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Secciones 6 y 11
 * =========================================================================
 */
class MemoriaService
{
    private const PUNTOS_ACIERTO        = 10;

    /** Segundos que se suman al reloj del cliente por cada acierto (RF — bonus de tiempo) */
    public const BONUS_TIEMPO_SEG = 4;

    private const UMBRAL_ACIERTOS = 3;
    private const UMBRAL_ERRORES  = 2;

    /**
     * Umbrales de la regla "por partida completa" (2026-09-29, a pedido
     * explícito — reemplaza la regla anterior de "ganás una vez subís,
     * perdés una vez bajás", que hacía que el nivel cambiara demasiado
     * rápido y resultaba estresante para chicos de 6 a 9 años).
     */
    private const UMBRAL_PARTIDAS_SUBE = 3; // partidas GANADAS seguidas, en el mismo nivel, para subir
    private const UMBRAL_PARTIDAS_BAJA = 2; // partidas PERDIDAS (tiempo agotado) seguidas, para bajar

    public function __construct(
        private MemoriaRepository $memoria,
        private SesionRepository $sesiones,
        private EjercicioRepository $ejercicios
    ) {
    }

    // =========================================================================
    // GENERACIÓN DEL MAZO
    // =========================================================================

    /**
     * Genera el mazo mezclado para una sesión nueva.
     *
     * Selecciona aleatoriamente N tarjetas del pool del nivel (N = pares
     * del nivel, guardado en niveles.total_ejercicios) y construye 2
     * posiciones de carta por cada una:
     *   - tipo 'objeto' → ambas cartas muestran lo mismo (modo 'normal')
     *   - tipo 'numero' → una carta muestra el numeral, la otra la cantidad
     *
     * @param  Nivel $nivel
     * @return array  Mazo mezclado, cada ítem con: posicion, tarjeta_id,
     *                modo_render, valor, nombre_es, nombre_gn, emoji
     */
    public function generarMazo(Nivel $nivel): array
    {
        $paresNecesarios = $nivel->total_ejercicios; // reutilizado semánticamente como "cantidad de pares"

        $poolDisponible = $this->memoria->tarjetasActivasDelNivel($nivel->id);

        // Seleccionar aleatoriamente N tarjetas del pool (aunque el pool tenga más)
        $seleccionadas = $poolDisponible->count() > $paresNecesarios
            ? $poolDisponible->random($paresNecesarios)
            : $poolDisponible;

        $mazo = collect();

        foreach ($seleccionadas as $tarjeta) {
            if ($tarjeta->tipo_contenido === 'numero') {
                // Carta A: numeral | Carta B: cantidad — mismo tarjeta_id, distinto render
                $mazo->push($this->crearCarta($tarjeta, 'numeral'));
                $mazo->push($this->crearCarta($tarjeta, 'cantidad'));
            } else {
                // Objeto: ambas cartas idénticas
                $mazo->push($this->crearCarta($tarjeta, 'normal'));
                $mazo->push($this->crearCarta($tarjeta, 'normal'));
            }
        }

        // Mezclar posiciones y asignar índice de posición final
        return $mazo->shuffle()->values()->map(function ($carta, $index) {
            $carta['posicion'] = $index;
            return $carta;
        })->all();
    }

    private function crearCarta(TarjetaNivel $tarjeta, string $modoRender): array
    {
        return [
            'tarjeta_id'  => $tarjeta->id,
            'modo_render' => $modoRender,   // 'normal' | 'numeral' | 'cantidad'
            'valor'       => $tarjeta->valor,
            'nombre_es'   => $tarjeta->nombre_es,
            'nombre_gn'   => $tarjeta->nombre_gn,
            'emoji'       => $tarjeta->emoji,
            'audio_es'    => $tarjeta->audio_es,
            'audio_gn'    => $tarjeta->audio_gn,
        ];
    }

    // =========================================================================
    // REGISTRO DE MOVIMIENTOS (intento de par)
    // =========================================================================

    /**
     * Registra un intento de par y actualiza los contadores de la sesión.
     *
     * @param  SesionJuego $sesion
     * @param  int         $tarjetaAId  ID de tarjetas_nivel de la 1ra carta volteada
     * @param  int         $tarjetaBId  ID de tarjetas_nivel de la 2da carta volteada
     * @param  int|null    $tiempoMs    Tiempo que tardó en voltear la 2da carta
     * @return array  ['es_acierto', 'puntos', 'movimiento']
     */
    public function registrarMovimiento(
        SesionJuego $sesion,
        int $tarjetaAId,
        int $tarjetaBId,
        ?int $tiempoMs
    ): array {
        // Es acierto si ambas cartas volteadas pertenecen a la misma tarjeta_nivel
        // (una pareja siempre comparte el mismo tarjeta_id, ver generarMazo())
        $esAcierto = $tarjetaAId === $tarjetaBId;

        $movimiento = $this->memoria->registrarMovimiento([
            'sesion_id'            => $sesion->id,
            'tarjeta_a_id'         => $tarjetaAId,
            'tarjeta_b_id'         => $tarjetaBId,
            'es_acierto'           => $esAcierto,
            'tiempo_respuesta_ms'  => $tiempoMs,
        ]);

        $puntos = $this->calcularPuntos($esAcierto);

        $this->sesiones->acumularResultado($sesion, $puntos, $esAcierto);

        return [
            'es_acierto' => $esAcierto,
            'puntos'     => $puntos,
            'movimiento' => $movimiento,
        ];
    }

    private function calcularPuntos(bool $esAcierto): int
    {
        // Sin bono de velocidad: en un juego de memoria apurar al niño premia
        // el azar y castiga al que se concentra. El tiempo ya está en el reloj
        // del nivel (y cada acierto suma segundos: BONUS_TIEMPO_SEG).
        return $esAcierto ? self::PUNTOS_ACIERTO : 0;
    }

    // =========================================================================
    // EVALUACIÓN 3/2 PROPIA (sobre movimientos_memoria)
    // =========================================================================

    /**
     * Evalúa si corresponde cambiar de nivel según la racha de aciertos/errores
     * consecutivos DENTRO de esta sesión. Misma regla 3/2 que el resto del
     * sistema, aplicada sobre la tabla propia de este juego.
     *
     * @param  SesionJuego $sesion
     * @return string|null  'sube' | 'baja' | null (sin cambio)
     */
    public function evaluar3y2(SesionJuego $sesion): ?string
    {
        $ultimos = $this->memoria->ultimosMovimientos(
            $sesion,
            max(self::UMBRAL_ACIERTOS, self::UMBRAL_ERRORES)
        );

        if ($ultimos->isEmpty()) return null;

        $aciertosConsecutivos = 0;
        $erroresConsecutivos  = 0;

        foreach ($ultimos as $mov) {
            if ($mov->es_acierto) {
                if ($erroresConsecutivos > 0) break;
                $aciertosConsecutivos++;
            } else {
                if ($aciertosConsecutivos > 0) break;
                $erroresConsecutivos++;
            }
        }

        if ($aciertosConsecutivos >= self::UMBRAL_ACIERTOS) return 'sube';
        if ($erroresConsecutivos  >= self::UMBRAL_ERRORES)  return 'baja';

        return null;
    }

    /**
     * NUEVA REGLA DE NIVEL (2026-09-29) — "por partida completa, con racha
     * de varias partidas", en vez de "una sola partida decide todo".
     * =====================================================================
     * Una partida es "ganada" si se completaron todos los pares antes de
     * que se acabara el tiempo (sin importar si hubo errores en el camino
     * — un error normal de memoria no cuenta en contra). Es "perdida" si
     * se acabó el tiempo sin completar el tablero.
     *
     *   - 3 partidas GANADAS seguidas en el mismo nivel → sube de nivel.
     *   - 2 partidas PERDIDAS seguidas en el mismo nivel → baja de nivel.
     *   - Cualquier otro caso (todavía no se llegó a la racha necesaria)
     *     → se queda en el mismo nivel, para seguir practicando.
     *
     * IMPORTANTE — esto NO agrega ninguna columna ni tabla nueva a la base
     * de datos: mira directamente el historial de sesiones ya guardadas
     * (la columna `nivel_superado` de `sesiones_juego`, que el sistema ya
     * completa al cerrar cualquier partida). Apenas el estudiante cambia
     * de nivel, el historial de ESE nivel nuevo empieza vacío, así que la
     * racha "arranca de cero" sola, sin necesidad de resetear nada a mano.
     *
     * @param  SesionJuego $sesionCerrada  La sesión que se acaba de cerrar
     * @param  bool        $gano           true = completó todos los pares a tiempo
     * @return array{direccion: ?string, consecutivas: int, meta: int}
     */
    public function evaluarRachaPorPartida(SesionJuego $sesionCerrada, bool $gano): array
    {
        $limite = max(self::UMBRAL_PARTIDAS_SUBE, self::UMBRAL_PARTIDAS_BAJA);

        // Últimas partidas CERRADAS de este estudiante, en este mismo juego
        // y este mismo nivel — de la más reciente a la más vieja. La
        // partida que se acaba de cerrar ya queda incluida acá (se guardó
        // justo antes de llamar a este método).
        // (el repositorio trae solo partidas hasta la que se está evaluando: así el
        // resultado es el mismo al cerrar la partida y al mostrar el resumen más tarde)
        $historial = $this->sesiones->resultadosRecientesDelNivel($sesionCerrada, $limite);

        // Cuenta cuántas de las últimas partidas, empezando por la más
        // reciente, tuvieron el MISMO resultado que la que se acaba de
        // jugar (todas ganadas, o todas perdidas) — corta apenas encuentra
        // una distinta, porque tiene que ser una racha SEGUIDA.
        $consecutivas = 0;
        foreach ($historial as $resultadoPrevio) {
            if ((bool) $resultadoPrevio === $gano) {
                $consecutivas++;
            } else {
                break;
            }
        }

        $direccion = null;
        if ($gano && $consecutivas >= self::UMBRAL_PARTIDAS_SUBE) {
            $direccion = 'sube';
        } elseif (!$gano && $consecutivas >= self::UMBRAL_PARTIDAS_BAJA) {
            $direccion = 'baja';
        }

        return [
            'direccion'    => $direccion,
            'consecutivas' => $consecutivas,
            'meta'         => $gano ? self::UMBRAL_PARTIDAS_SUBE : self::UMBRAL_PARTIDAS_BAJA,
        ];
    }

    /**
     * Busca el nivel siguiente o anterior dentro del mismo juego y grado.
     *
     * @param  Nivel  $nivelActual
     * @param  string $direccion  'sube' | 'baja'
     * @return Nivel|null
     */
    public function buscarNivelAdyacente(Nivel $nivelActual, string $direccion): ?Nivel
    {
        $delta = $direccion === 'sube' ? 1 : -1;

        return $this->ejercicios->nivelAdyacente($nivelActual, $delta);
    }

    /**
     * Verifica si todas las parejas del mazo fueron encontradas.
     *
     * @param  SesionJuego $sesion
     * @param  int         $totalPares
     * @return bool
     */
    public function todasLasParejasEncontradas(SesionJuego $sesion, int $totalPares): bool
    {
        return $sesion->aciertos >= $totalPares;
    }
}
