<?php

namespace App\Services;

use App\Models\DetalleNumeros;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Repositories\NumerosRepository;
use App\Support\Numeros\GeneradorItems;
use App\Support\Numeros\TextosNumeros;
use InvalidArgumentException;
use RuntimeException;

/**
 * SERVICIO: NumerosService — lógica de «Mati y los números» (tamizaje)
 * =========================================================================
 * «Mati y los números» NO es un juego de puntos: es una herramienta de apoyo
 * que avisa a la docente que preste más atención a un niño. No emite ningún
 * diagnóstico. Este servicio es la autoridad de:
 *   · en qué VIAJE y CICLO está el niño (3 viajes = 1 ciclo);
 *   · qué preguntas se le muestran en cada parte (GeneradorItems) y el nivel
 *     adaptativo con la regla de MatiGuarã (3 aciertos seguidos suben, 2 errores
 *     seguidos bajan; el cambio se aplica en la parte siguiente);
 *   · la corrección de cada respuesta y la clasificación del error;
 *   · el cierre del viaje: completo o no, confiable o no.
 *
 * Reglas de este juego (RF-02, RF-07, RF-08, RF-12, RF-13):
 *   · no suma puntos, rachas, logros ni progreso: usa sesiones_juego solo para
 *     registrar cuándo jugó (y así contar el tiempo de juego), nunca
 *     SesionService::cerrar ni la racha;
 *   · un solo intento por pregunta; el navegador no recibe la respuesta correcta
 *     y la respuesta del juego es igual para aciertos y errores;
 *   · una respuesta en menos de 350 ms es un posible toque al azar; si pasan del
 *     35 % de las respuestas del viaje, el viaje queda como no confiable;
 *   · un viaje cortado (límite de tiempo, salir) queda como no completo y no se
 *     interpreta como completo.
 * =========================================================================
 */
class NumerosService
{
    public const AZAR_MS             = 350;
    public const AZAR_MAX_PORCENTAJE = 35;
    /** En la línea de números se considera acierto un error de hasta este % del largo de la línea. */
    public const LINEA_ACIERTO_PCT   = 10.0;
    public const SUBE_CON_ACIERTOS   = 3;
    public const BAJA_CON_ERRORES    = 2;
    public const VIAJES_POR_CICLO    = 3;

    public function __construct(
        private SesionService $sesionService,
        private NumerosRepository $numeros
    ) {
    }

    // =========================================================================
    // ESTADO DEL NIÑO: ¿qué viaje le toca?
    // =========================================================================

    /**
     * Qué viaje le toca al niño: el primer hueco (ciclo, viaje) que todavía no tiene hecho.
     * Lo normal es que se juegue en orden 1, 2, 3 y luego otro ciclo; si la docente marca un viaje
     * como no válido (RF-20), ese viaje vuelve a tocarle.
     *
     * @return array{completos:int, ciclo:int, viaje:int, hechos_en_ciclo:int, viajes_hechos:int[], jugo_hoy:bool}
     */
    public function estadoDe(int $estudianteId, int $juegoId): array
    {
        $pares = $this->numeros->paresCompletos($estudianteId, $juegoId);
        $hechos = [];
        foreach ($pares as [$ciclo, $viaje]) {
            $hechos[$ciclo . '-' . $viaje] = true;
        }

        $ciclo = 1;
        $viaje = 1;
        for ($c = 1; $c <= count($pares) + 1; $c++) {
            for ($v = 1; $v <= self::VIAJES_POR_CICLO; $v++) {
                if (!isset($hechos[$c . '-' . $v])) {
                    $ciclo = $c;
                    $viaje = $v;
                    break 2;
                }
            }
        }

        $enCiclo = array_values(array_filter(
            range(1, self::VIAJES_POR_CICLO),
            fn ($v) => isset($hechos[$ciclo . '-' . $v])
        ));

        return [
            'completos'       => count($pares),
            'ciclo'           => $ciclo,
            'viaje'           => $viaje,
            'hechos_en_ciclo' => count($enCiclo),
            'viajes_hechos'   => $enCiclo,
            'jugo_hoy'        => count($pares) > 0 && $this->numeros->terminoViajeHoy($estudianteId, $juegoId),
        ];
    }

    // =========================================================================
    // INICIO DEL VIAJE
    // =========================================================================

    public function iniciar(Estudiante $estudiante, Juego $juego, Nivel $nivel, ?string $dispositivo = null): SesionJuego
    {
        $estado = $this->estadoDe($estudiante->id, $juego->id);
        $grado  = (int) $nivel->grado;

        $sesion = $this->sesionService->iniciar($estudiante->id, $juego->id, $nivel->id, $dispositivo);

        $this->numeros->crearDetalle([
            'sesion_id'       => $sesion->id,
            'viaje'           => $estado['viaje'],
            'ciclo'           => $estado['ciclo'],
            'semilla'         => random_int(1000, 900000000),
            'grado'           => $grado,
            'partes_json'     => [],
            'total_preguntas' => GeneradorItems::totalDelViaje($estado['viaje']),
        ]);

        return $sesion;
    }

    // =========================================================================
    // PARTES Y PREGUNTAS
    // =========================================================================

    /**
     * Las preguntas de una parte, para el navegador (sin la respuesta correcta).
     * Si la parte ya se armó (el niño actualizó la página), devuelve las mismas.
     *
     * @return array{parte:int, nivel:int, area:string, items:array, respondidos:int[], demo:array}
     */
    public function parteDe(SesionJuego $sesion, int $parte): array
    {
        $detalle = $this->detalleOFalla($sesion);

        if (!in_array($parte, GeneradorItems::partesDelViaje((int) $detalle->viaje), true)) {
            throw new InvalidArgumentException('Esa parte no es de este viaje.');
        }

        $partes = $detalle->partes_json ?? [];
        $clave  = (string) $parte;

        if (!isset($partes[$clave])) {
            $nivel = $this->nivelAdaptativoAntesDe($sesion->id, $parte);
            $partes[$clave] = [
                'nivel' => $nivel,
                'items' => GeneradorItems::parte((int) $detalle->grado, $parte, (int) $detalle->ciclo, $nivel, (int) $detalle->semilla),
            ];
            $detalle->partes_json = $partes;
            $detalle->save();
        }

        $guardada = $partes[$clave];

        $items = [];
        foreach ($guardada['items'] as $orden => $it) {
            $items[] = $this->paraElNavegador($it, $orden);
        }

        return [
            'parte'       => $parte,
            'nivel'       => (int) $guardada['nivel'],
            'area'        => GeneradorItems::areaDe($parte),
            'items'       => $items,
            'respondidos' => $this->numeros->ordenesRespondidos($sesion->id, $parte),
            // La demostración sí lleva la respuesta: es solo para mostrar cómo se juega.
            'demo'        => $this->paraElNavegador(GeneradorItems::demostracion((int) $detalle->grado, $parte), 0, true),
        ];
    }

    /** Lo que el navegador necesita de una pregunta; sin la respuesta correcta salvo en la demostración. */
    private function paraElNavegador(array $it, int $orden, bool $conRespuesta = false): array
    {
        $salida = [
            'orden'    => $orden,
            'tipo'     => $it['tipo'],
            'datos'    => $it['datos'],
            'opciones' => $it['opciones'],
        ];
        if ($conRespuesta) {
            $salida['correcta'] = $it['correcta'];
        }
        return $salida;
    }

    /**
     * Nivel adaptativo (1 inicial, 2 esperado, 3 ampliado) con el que se arma una parte.
     * Empieza en 2; con 3 aciertos seguidos en preguntas adaptativas sube y con 2 errores seguidos
     * baja. El cambio se aplica en la parte siguiente (no a mitad de parte).
     */
    public function nivelAdaptativoAntesDe(int $sesionId, int $parte): int
    {
        $porParte = [];
        foreach ($this->numeros->respuestasDe($sesionId) as $r) {
            if (!$r->fija && (int) $r->parte < $parte) {
                $porParte[(int) $r->parte][] = $r;
            }
        }
        ksort($porParte);

        return $this->nivelTrasRespuestas($porParte);
    }

    /** @param array<int, iterable> $porParte  respuestas adaptativas agrupadas por parte, en orden */
    public function nivelTrasRespuestas(array $porParte): int
    {
        $nivel = GeneradorItems::ESPERADO;
        $aciertos = $errores = 0;

        foreach ($porParte as $respuestas) {
            $nuevo = $nivel;
            foreach ($respuestas as $r) {
                if ($r->es_correcto) {
                    $aciertos++;
                    $errores = 0;
                    if ($aciertos >= self::SUBE_CON_ACIERTOS) {
                        $nuevo = min(GeneradorItems::AMPLIADO, $nuevo + 1);
                        $aciertos = 0;
                    }
                } else {
                    $errores++;
                    $aciertos = 0;
                    if ($errores >= self::BAJA_CON_ERRORES) {
                        $nuevo = max(GeneradorItems::INICIAL, $nuevo - 1);
                        $errores = 0;
                    }
                }
            }
            $nivel = $nuevo;   // el cambio rige desde la parte siguiente
        }

        return $nivel;
    }

    // =========================================================================
    // RESPUESTAS
    // =========================================================================

    /**
     * Registra la respuesta a una pregunta. La respuesta del juego es la misma para aciertos
     * y errores (RF-08), por eso no se devuelve si fue correcta.
     *
     * @return array{guardada:bool, duplicada:bool}
     * @throws InvalidArgumentException si la pregunta no existe en este viaje
     */
    public function registrarRespuesta(SesionJuego $sesion, int $parte, int $orden, string $respuesta, ?int $tiempoMs): array
    {
        $detalle = $this->detalleOFalla($sesion);
        $item    = $detalle->partes_json[(string) $parte]['items'][$orden] ?? null;
        $nivel   = $detalle->partes_json[(string) $parte]['nivel'] ?? null;

        if ($item === null || $nivel === null) {
            throw new InvalidArgumentException('Pregunta no válida.');
        }

        // Un solo intento por pregunta: un segundo envío (toque doble, reintento de red) no se guarda.
        if ($this->numeros->existeRespuesta($sesion->id, $parte, $orden)) {
            return ['guardada' => false, 'duplicada' => true];
        }

        $respuesta = mb_substr(trim($respuesta), 0, 24);
        $es        = $this->corregir($item, $respuesta);
        $esCalentamiento = $parte === 1;
        $azar      = !$esCalentamiento && $tiempoMs !== null && $tiempoMs < self::AZAR_MS;

        $tipoError = null;
        if (!$es['correcto']) {
            $tipoError = $azar ? 'azar' : $this->clasificarError($item, $respuesta);
        }

        $this->numeros->guardarRespuesta([
            'sesion_id'     => $sesion->id,
            'parte'         => $parte,
            'orden'         => $orden,
            'item_clave'    => mb_substr((string) $item['clave'], 0, 60),
            'area'          => $item['area'],
            'nivel'         => (int) $nivel,
            'fija'          => (bool) $item['fija'],
            'cero'          => (bool) $item['cero'],
            'respuesta'     => $respuesta,
            'es_correcto'   => $es['correcto'],
            'tipo_error'    => $tipoError,
            'tiempo_ms'     => $tiempoMs,
            'azar'          => $azar,
            'error_pct'     => $es['error_pct'],
            'respondido_en' => now(),
        ]);

        return ['guardada' => true, 'duplicada' => false];
    }

    /** @return array{correcto:bool, error_pct:?float} */
    private function corregir(array $item, string $respuesta): array
    {
        switch ($item['tipo']) {
            case 'toque':
                return ['correcto' => true, 'error_pct' => null];

            case 'linea':
                if (!is_numeric($respuesta)) {
                    throw new InvalidArgumentException('Respuesta no válida.');
                }
                $largo = max(1, (int) $item['datos']['max']);
                $pct   = round(abs((float) $respuesta - (int) $item['datos']['objetivo']) / $largo * 100, 1);
                return ['correcto' => $pct <= self::LINEA_ACIERTO_PCT, 'error_pct' => min(999.9, $pct)];

            case 'mas_puntos':
            case 'mayor':
            case 'signo':
                if (!in_array($respuesta, array_map('strval', $item['opciones']), true)) {
                    throw new InvalidArgumentException('Respuesta no válida.');
                }
                return ['correcto' => $respuesta === (string) $item['correcta'], 'error_pct' => null];

            default:   // opciones numéricas
                if (!is_numeric($respuesta) || (string) (int) $respuesta !== ltrim($respuesta, '+')) {
                    throw new InvalidArgumentException('Respuesta no válida.');
                }
                $valores = array_map(fn ($o) => (string) (is_array($o) ? $o['valor'] : $o), $item['opciones']);
                if (!in_array((string) (int) $respuesta, $valores, true)) {
                    throw new InvalidArgumentException('Respuesta no válida.');
                }
                return ['correcto' => (int) $respuesta === (int) $item['correcta'], 'error_pct' => null];
        }
    }

    /**
     * Tipo de error de una respuesta incorrecta: con_cero (el niño confunde el 0), uno más/menos,
     * número parecido o cifras invertidas, operación contraria, lejano o comparación.
     * (El toque al azar se decide antes, por el tiempo.)
     */
    public function clasificarError(array $item, string $respuesta): string
    {
        if (!empty($item['cero'])) {
            return 'cero';
        }

        switch ($item['tipo']) {
            case 'mas_puntos':
            case 'mayor':
                return 'comparacion';

            case 'signo':
                return 'operacion_contraria';

            case 'puntos_rapidos':
                return abs((int) $respuesta - (int) $item['correcta']) === 1 ? 'mas_menos_uno' : 'lejano';

            case 'linea':
                return 'lejano';

            default:
                return $item['tipos'][(string) (int) $respuesta] ?? 'lejano';
        }
    }

    // =========================================================================
    // CIERRE
    // =========================================================================

    /**
     * Cierra el viaje. Es COMPLETO solo si se respondieron todas las preguntas; si no (se acabó el
     * tiempo de juego o el niño salió) queda abandonado y no cuenta para el ciclo.
     *
     * @return array{ya_cerrada:bool, completa:bool, confiable:bool, viaje:int, ciclo:int, figurita:array, respondidas:int, total:int}
     */
    public function cerrar(SesionJuego $sesion, int $duracionS = 0): array
    {
        $detalle = $this->detalleOFalla($sesion);
        $figurita = TextosNumeros::figurita((int) $detalle->ciclo, (int) $detalle->viaje);

        if (!$sesion->estaEnCurso()) {
            return [
                'ya_cerrada' => true, 'completa' => (bool) $detalle->completa, 'confiable' => (bool) $detalle->confiable,
                'viaje' => (int) $detalle->viaje, 'ciclo' => (int) $detalle->ciclo, 'figurita' => $figurita,
                'respondidas' => (int) $detalle->respondidas, 'total' => (int) $detalle->total_preguntas,
            ];
        }

        $respuestas  = $this->numeros->respuestasDe($sesion->id);
        $respondidas = $respuestas->count();
        $total       = (int) $detalle->total_preguntas;
        $completa    = $respondidas >= $total && $total > 0;

        $juego = $respuestas->where('parte', '!=', 1);
        $pctAzar = $juego->isEmpty() ? 0 : (int) round(100 * $juego->where('azar', true)->count() / $juego->count());

        $tiemposBase = $respuestas->where('parte', 1)->pluck('tiempo_ms')->filter(fn ($t) => $t !== null)->sort()->values();
        $base = null;
        if ($tiemposBase->isNotEmpty()) {
            $n = $tiemposBase->count();
            $base = $n % 2 ? (int) $tiemposBase[intdiv($n, 2)] : (int) round(($tiemposBase[$n / 2 - 1] + $tiemposBase[$n / 2]) / 2);
        }

        $porParte = [];
        foreach ($respuestas as $r) {
            if (!$r->fija) {
                $porParte[(int) $r->parte][] = $r;
            }
        }
        ksort($porParte);

        $detalle->update([
            'completa'        => $completa,
            'confiable'       => $pctAzar <= self::AZAR_MAX_PORCENTAJE,
            'pct_azar'        => min(100, $pctAzar),
            'tiempo_base_ms'  => $base,
            'nivel_final'     => $this->nivelTrasRespuestas($porParte),
            'duracion_s'      => max(0, min($duracionS, 7200)),
            'respondidas'     => $respondidas,
        ]);

        // La sesión de juego solo marca CUÁNDO jugó (y cuánto): sin puntos, sin progreso, sin racha.
        $sesion->update([
            'estado'         => $completa ? 'completada' : 'abandonada',
            'fecha_fin'      => now(),
            'nivel_superado' => false,
        ]);

        // Un viaje completo puede cambiar el semáforo de la docente (y avisarle). Nunca rompe el cierre.
        if ($completa) {
            app(TamizajeService::class)->actualizar((int) $sesion->estudiante_id);
        }

        return [
            'ya_cerrada' => false, 'completa' => $completa, 'confiable' => $pctAzar <= self::AZAR_MAX_PORCENTAJE,
            'viaje' => (int) $detalle->viaje, 'ciclo' => (int) $detalle->ciclo, 'figurita' => $figurita,
            'respondidas' => $respondidas, 'total' => $total,
        ];
    }

    // =========================================================================
    // VOCES (grabaciones opcionales)
    // =========================================================================

    /**
     * Grabaciones de voz que existen, para el botón 🔊: public/audio/numeros/p{parte}_es.mp3 y _gn.mp3.
     * Mientras no estén, el navegador usa la voz del dispositivo en castellano; en guaraní no hay voz
     * del dispositivo, así que el botón solo aparece si existe el archivo.
     *
     * @return array<int, array{es:?string, gn:?string}>
     */
    public function voces(): array
    {
        $salida = [];
        foreach (array_keys(TextosNumeros::PARTES) as $parte) {
            $fila = [];
            foreach (['es', 'gn'] as $leng) {
                $rel = "audio/numeros/p{$parte}_{$leng}.mp3";
                $fila[$leng] = is_file(public_path($rel)) ? asset($rel) : null;
            }
            $salida[$parte] = $fila;
        }
        return $salida;
    }

    /** Detalle del viaje (null si la sesión no es de este juego). */
    public function detalleDeSesion(SesionJuego $sesion): ?DetalleNumeros
    {
        return $this->numeros->detalleDe($sesion->id);
    }

    private function detalleOFalla(SesionJuego $sesion): DetalleNumeros
    {
        return $this->numeros->detalleDe($sesion->id)
            ?? throw new RuntimeException("La sesión {$sesion->id} no tiene detalle de «Mati y los números».");
    }
}
