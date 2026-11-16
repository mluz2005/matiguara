<?php

namespace App\Services;

use App\Models\DetalleNumeros;
use App\Models\Estudiante;
use App\Models\TamizajeAviso;
use App\Models\TamizajeEstado;
use App\Models\TamizajeFicha;
use App\Repositories\NumerosRepository;
use App\Support\Numeros\TextosTamizaje;
use Illuminate\Support\Facades\Log;

/**
 * SERVICIO: TamizajeService — el semáforo de «Mati y los números»
 * =========================================================================
 * Convierte las respuestas de los viajes en UNA señal para la docente:
 * verde, amarillo o rojo («mirar con más atención»). NO es un diagnóstico.
 *
 * Reglas (acordadas con la autora; los cortes son PROVISORIOS hasta tener datos del piloto):
 *   1. Hace falta al menos 2 ciclos COMPLETOS (3 viajes completos y válidos cada uno). Antes, sin color.
 *   2. Se miran los últimos 3 ciclos completos.
 *   3. Cada área (números y cantidades / sentido de la cantidad / signos y cálculo) se puntúa con el
 *      % de aciertos de cada ciclo y se promedian los ciclos. Un área necesita datos de al menos
 *      2 ciclos para contar.
 *   4. El color es el del ÁREA PEOR: ≥ corte verde → verde; ≥ corte amarillo → amarillo; menos → rojo
 *      (por defecto 75 y 60, los cambia la administración en la configuración de la institución).
 *   5. Señales extra: se confunde seguido con el cero, o necesitó casi siempre las preguntas más
 *      fáciles. Con una señal activa, un verde pasa a amarillo (las señales nunca llegan a rojo solas).
 *   6. No cuentan: el calentamiento, las respuestas de menos de 350 ms (posible toque al azar), los
 *      viajes poco confiables (más de 35 % de toques al azar) ni los que la docente marcó como no válidos.
 *   7. La campanita avisa UNA vez cuando el color EMPEORA a amarillo o rojo; no repite si sigue igual
 *      y no avisa si mejora.
 * =========================================================================
 */
class TamizajeService
{
    public const CORTE_VERDE_DEFECTO    = 75;
    public const CORTE_AMARILLO_DEFECTO = 60;

    public const MIN_CICLOS          = 2;   // ciclos completos para que haya semáforo
    public const VENTANA_CICLOS      = 3;   // se miran los últimos N ciclos completos
    public const VIAJES_POR_CICLO    = 3;
    public const MIN_RESPUESTAS_AREA = 4;   // respuestas mínimas de un área en un ciclo
    public const CERO_MIN            = 4;   // preguntas con cero respondidas para mirar la señal
    public const CERO_PCT_ERROR      = 50;  // % de errores en esas preguntas
    public const NIVEL_BAJO_MIN      = 10;  // preguntas adaptativas respondidas para mirar la señal
    public const NIVEL_BAJO_PCT      = 50;  // % de ellas hechas en el nivel más fácil

    private const ORDEN = ['verde' => 1, 'amarillo' => 2, 'rojo' => 3];

    public function __construct(private NumerosRepository $numeros)
    {
    }

    // =========================================================================
    // CÁLCULO PURO (sin base de datos: se puede probar con arreglos)
    // =========================================================================

    /**
     * @param  array<int, array{ciclo:int, viaje:int, completa:bool, valida:bool, confiable:bool, respuestas:array}> $viajes
     *         cada respuesta: [parte, area, es_correcto, cero, fija, nivel, azar]
     * @return array{semaforo:?string, motivo:?string, ciclos_completos:int, ciclos_considerados:int,
     *               areas:array<string, array{pct:?int, ciclos:int, color:?string}>, senales:string[], subio_por_senales:bool}
     */
    public static function evaluar(array $viajes, int $corteVerde = self::CORTE_VERDE_DEFECTO, int $corteAmarillo = self::CORTE_AMARILLO_DEFECTO): array
    {
        [$corteVerde, $corteAmarillo] = self::cortesValidos($corteVerde, $corteAmarillo);

        $areas = [];
        foreach (array_keys(TextosTamizaje::AREAS) as $a) {
            $areas[$a] = ['pct' => null, 'ciclos' => 0, 'color' => null];
        }

        $resultado = [
            'semaforo'            => null,
            'motivo'              => null,
            'ciclos_completos'    => 0,
            'ciclos_considerados' => 0,
            'areas'               => $areas,
            'senales'             => [],
            'subio_por_senales'   => false,
        ];

        // Viajes completos y válidos, agrupados por ciclo.
        $validos = array_values(array_filter($viajes, fn ($v) => !empty($v['completa']) && !empty($v['valida'])));
        $viajesPorCiclo = [];
        foreach ($validos as $v) {
            $viajesPorCiclo[(int) $v['ciclo']][(int) $v['viaje']] = true;
        }
        $completos = [];
        foreach ($viajesPorCiclo as $ciclo => $vs) {
            if (count($vs) >= self::VIAJES_POR_CICLO) {
                $completos[] = $ciclo;
            }
        }
        sort($completos);
        $resultado['ciclos_completos'] = count($completos);

        if (count($completos) < self::MIN_CICLOS) {
            $resultado['motivo'] = 'faltan_ciclos';
            return $resultado;
        }

        $ventana = array_slice($completos, -self::VENTANA_CICLOS);
        $resultado['ciclos_considerados'] = count($ventana);

        // Respuestas que cuentan: viajes confiables de la ventana, sin calentamiento ni toques al azar.
        $porCicloArea = [];   // [ciclo][area] => [aciertos, total]
        $todas = [];
        foreach ($validos as $v) {
            $ciclo = (int) $v['ciclo'];
            if (!in_array($ciclo, $ventana, true) || empty($v['confiable'])) {
                continue;
            }
            foreach ($v['respuestas'] as $r) {
                if ((int) $r['parte'] === 1 || !empty($r['azar'])) {
                    continue;
                }
                $a = (string) $r['area'];
                $porCicloArea[$ciclo][$a][1] = ($porCicloArea[$ciclo][$a][1] ?? 0) + 1;
                $porCicloArea[$ciclo][$a][0] = ($porCicloArea[$ciclo][$a][0] ?? 0) + (!empty($r['es_correcto']) ? 1 : 0);
                $todas[] = $r;
            }
        }

        // Porcentaje por área = promedio de los ciclos que tienen datos suficientes de esa área.
        $peor = null;
        foreach (array_keys($areas) as $a) {
            $porcentajes = [];
            foreach ($porCicloArea as $porArea) {
                [$ok, $total] = $porArea[$a] ?? [0, 0];
                if ($total >= self::MIN_RESPUESTAS_AREA) {
                    $porcentajes[] = 100 * $ok / $total;
                }
            }
            $resultado['areas'][$a]['ciclos'] = count($porcentajes);
            if (count($porcentajes) >= self::MIN_CICLOS) {
                $pct   = (int) round(array_sum($porcentajes) / count($porcentajes));
                $color = $pct >= $corteVerde ? 'verde' : ($pct >= $corteAmarillo ? 'amarillo' : 'rojo');
                $resultado['areas'][$a]['pct']   = $pct;
                $resultado['areas'][$a]['color'] = $color;
                if ($peor === null || self::ORDEN[$color] > self::ORDEN[$peor]) {
                    $peor = $color;
                }
            }
        }

        // Señales extra.
        $ceros = array_values(array_filter($todas, fn ($r) => !empty($r['cero'])));
        if (count($ceros) >= self::CERO_MIN) {
            $errores = count(array_filter($ceros, fn ($r) => empty($r['es_correcto'])));
            if (100 * $errores / count($ceros) >= self::CERO_PCT_ERROR) {
                $resultado['senales'][] = 'cero';
            }
        }
        $adaptativas = array_values(array_filter($todas, fn ($r) => empty($r['fija'])));
        if (count($adaptativas) >= self::NIVEL_BAJO_MIN) {
            $faciles = count(array_filter($adaptativas, fn ($r) => (int) $r['nivel'] === 1));
            if (100 * $faciles / count($adaptativas) >= self::NIVEL_BAJO_PCT) {
                $resultado['senales'][] = 'nivel_bajo';
            }
        }

        if ($peor === null) {
            $resultado['motivo'] = 'sin_datos_confiables';
            return $resultado;
        }

        if ($peor === 'verde' && $resultado['senales'] !== []) {
            $peor = 'amarillo';
            $resultado['subio_por_senales'] = true;
        }

        $resultado['semaforo'] = $peor;
        return $resultado;
    }

    /** Cortes sanos: verde entre 2 y 100 y amarillo siempre menor que el verde; si no, los de siempre. */
    public static function cortesValidos(int $verde, int $amarillo): array
    {
        if ($verde < 2 || $verde > 100 || $amarillo < 1 || $amarillo >= $verde) {
            return [self::CORTE_VERDE_DEFECTO, self::CORTE_AMARILLO_DEFECTO];
        }
        return [$verde, $amarillo];
    }

    /** ¿El color nuevo es peor que el anterior? (sin color = 0) */
    public static function empeoro(?string $antes, ?string $ahora): bool
    {
        return (self::ORDEN[$ahora ?? ''] ?? 0) > (self::ORDEN[$antes ?? ''] ?? 0);
    }

    // =========================================================================
    // CON BASE DE DATOS
    // =========================================================================

    /** Cortes de la institución del niño (los de siempre si no tiene configuración). */
    public function cortesDe(?Estudiante $estudiante): array
    {
        $config = $estudiante?->matriculaActiva?->institucion?->config;

        return self::cortesValidos(
            (int) ($config->tamizaje_corte_verde ?? self::CORTE_VERDE_DEFECTO),
            (int) ($config->tamizaje_corte_amarillo ?? self::CORTE_AMARILLO_DEFECTO)
        );
    }

    /** Calcula el semáforo del niño con lo que hay hoy en la base. */
    public function calcular(int $estudianteId): array
    {
        $viajes = $this->numeros->viajesParaSemaforo($estudianteId)->map(fn (DetalleNumeros $d) => [
            'ciclo'     => (int) $d->ciclo,
            'viaje'     => (int) $d->viaje,
            'completa'  => (bool) $d->completa,
            'valida'    => (bool) $d->valida,
            'confiable' => (bool) $d->confiable,
            'respuestas' => $d->respuestas->map(fn ($r) => [
                'parte'       => (int) $r->parte,
                'area'        => (string) $r->area,
                'es_correcto' => (bool) $r->es_correcto,
                'cero'        => (bool) $r->cero,
                'fija'        => (bool) $r->fija,
                'nivel'       => (int) $r->nivel,
                'azar'        => (bool) $r->azar,
            ])->all(),
        ])->all();

        $estudiante = Estudiante::with('matriculaActiva.institucion.config')->find($estudianteId);
        [$verde, $amarillo] = $this->cortesDe($estudiante);

        return self::evaluar($viajes, $verde, $amarillo);
    }

    /**
     * Recalcula el semáforo, lo guarda y, si EMPEORÓ a amarillo o rojo, deja un aviso para la docente.
     * Nunca rompe a quien lo llama: si algo falla, se anota en el registro y se sigue.
     *
     * @return array{resultado:array, aviso:?TamizajeAviso}|null
     */
    public function actualizar(int $estudianteId): ?array
    {
        try {
            $resultado = $this->calcular($estudianteId);

            $estado = TamizajeEstado::firstOrNew(['estudiante_id' => $estudianteId]);
            $antes  = $estado->semaforo;
            $ahora  = $resultado['semaforo'];

            $estado->fill([
                'semaforo'            => $ahora,
                'ciclos_completos'    => $resultado['ciclos_completos'],
                'ciclos_considerados' => $resultado['ciclos_considerados'],
                'resultado_json'      => $resultado,
                'calculado_en'        => now(),
            ])->save();

            $aviso = null;
            if (in_array($ahora, ['amarillo', 'rojo'], true) && self::empeoro($antes, $ahora)) {
                $docenteId = Estudiante::find($estudianteId)?->matriculaActiva?->docente_id;
                if ($docenteId) {
                    $aviso = TamizajeAviso::create([
                        'estudiante_id' => $estudianteId,
                        'docente_id'    => $docenteId,
                        'semaforo'      => $ahora,
                        'ciclos'        => $resultado['ciclos_completos'],
                    ]);
                }
            }

            return ['resultado' => $resultado, 'aviso' => $aviso];
        } catch (\Throwable $e) {
            Log::error('TamizajeService: no se pudo actualizar el semáforo', [
                'estudiante_id' => $estudianteId,
                'error'         => $e->getMessage(),
            ]);
            return null;
        }
    }

    /** La docente marca un viaje como no válido (o lo vuelve a validar) y se recalcula el semáforo (RF-20). */
    public function cambiarValidez(DetalleNumeros $detalle, bool $valida, ?string $motivo = null): void
    {
        $motivo = $valida ? null : (array_key_exists((string) $motivo, TextosTamizaje::MOTIVOS_NO_VALIDA) ? $motivo : 'otro');

        $detalle->update([
            'valida'               => $valida,
            'motivo_no_valida'     => $motivo,
            'marcada_no_valida_en' => $valida ? null : now(),
        ]);

        $estudianteId = $detalle->sesion?->estudiante_id;
        if ($estudianteId) {
            $this->actualizar((int) $estudianteId);
        }
    }

    /** Guarda (o actualiza) la ficha de observación de la docente. */
    public function guardarFicha(int $estudianteId, ?int $docenteId, array $marcadas, ?string $nota): TamizajeFicha
    {
        $validas = array_values(array_intersect(array_keys(TextosTamizaje::FICHA), $marcadas));
        $nota    = $nota !== null ? mb_substr(trim($nota), 0, TextosTamizaje::FICHA_NOTA_MAX) : null;

        return TamizajeFicha::updateOrCreate(
            ['estudiante_id' => $estudianteId],
            ['docente_id' => $docenteId, 'observaciones' => $validas, 'nota' => $nota === '' ? null : $nota]
        );
    }
}
