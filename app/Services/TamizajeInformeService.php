<?php

namespace App\Services;

use App\Models\DetalleNumeros;
use App\Models\Estudiante;
use App\Models\RespuestaNumeros;
use App\Models\TamizajeInforme;
use App\Support\Numeros\FiltroFrases;
use App\Support\Numeros\PlantillaInforme;
use App\Support\Numeros\TextosTamizaje;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SERVICIO: TamizajeInformeService — informe de «Atención numérica» redactado con ayuda de la IA (Etapa 3)
 * =========================================================================
 * FLUJO (solo cuando la docente aprieta el botón — RN-05):
 *   1. Se calcula el semáforo actual (TamizajeService). Sin semáforo no hay informe.
 *   2. Se arma un JSON ANÓNIMO: id_anonimizado + grado + porcentajes por área + señales + tendencia
 *      + tipos de error. Nada más (RN-01).
 *   3. Se le pide a la API Claude un JSON con tres textos: lectura, actividades y texto para la familia.
 *   4. Se valida el JSON y cada texto pasa por el FILTRO DE FRASES (nada de diagnósticos ni alarma).
 *   5. Si algo falla (sin clave, sin conexión, JSON roto, frase no permitida) se usa la PLANTILLA de respaldo.
 *   6. Se guarda el informe (con el JSON enviado, para poder auditar la privacidad).
 *
 * PRIVACIDAD (RN-01): la IA NUNCA recibe nombre, apellido, código de acceso, ID interno, fecha de
 * nacimiento, institución, docente ni las observaciones de la ficha (casillas y nota libre).
 * El nombre de pila entra al texto de la familia recién al MOSTRARLO (marcador {nombre}).
 *
 * NO DIAGNOSTICA (RN-02): el prompt lo prohíbe y el filtro lo comprueba.
 *
 * Es independiente de DiagnosticoIAService (el diagnóstico de los otros cuatro juegos no se toca).
 * =========================================================================
 */
class TamizajeInformeService
{
    private const API_URL     = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    /** Espera mínima entre dos informes del mismo niño (evita apretar el botón varias veces y gastar de más). */
    public const ESPERA_SEGUNDOS = 30;

    /** Tokens mínimos para la respuesta: son tres textos. */
    private const TOKENS_MIN = 1500;

    private string $modelo;
    private int $maxTokens;

    public function __construct(private TamizajeService $tamizaje)
    {
        $this->modelo    = (string) config('services.claude.model', 'claude-sonnet-4-6');
        $this->maxTokens = max(self::TOKENS_MIN, (int) config('services.claude.max_tokens', 1000));
    }

    // =========================================================================
    // PRINCIPAL
    // =========================================================================

    /**
     * Genera y guarda el informe de un niño.
     *
     * @throws \DomainException si todavía no hay semáforo (faltan ciclos o no hay datos confiables)
     */
    public function generar(Estudiante $estudiante, int $docenteId, int $grado): TamizajeInforme
    {
        $resultado = $this->tamizaje->calcular($estudiante->id);
        if ($resultado['semaforo'] === null) {
            throw new \DomainException('Todavía no hay semáforo para este niño.');
        }

        [$verde, $amarillo] = $this->tamizaje->cortesDe($estudiante);
        $datos = self::datosAnonimos(
            (string) $estudiante->id_anonimizado,
            $grado,
            $resultado,
            $this->resumenDeRespuestas($estudiante->id),
            $verde,
            $amarillo
        );

        $api = $this->llamarApi(self::construirPrompt($datos));

        $textos = null;
        $motivo = null;
        if ($api['exitoso']) {
            [$textos, $motivo] = self::interpretarRespuesta($api['texto']);
        } else {
            $motivo = $api['error'];
        }

        $origen = 'ia';
        if ($textos === null) {
            $origen = 'plantilla';
            $textos = PlantillaInforme::generar($resultado);
            Log::info('TamizajeInformeService: se usó la plantilla de respaldo', ['razon' => $motivo]);
        }

        return TamizajeInforme::create([
            'estudiante_id'   => $estudiante->id,
            'docente_id'      => $docenteId,
            'id_anonimizado'  => (string) $estudiante->id_anonimizado,
            'semaforo'        => $resultado['semaforo'],
            'ciclos'          => $resultado['ciclos_completos'],
            'lectura'         => $textos['lectura'],
            'actividades'     => $textos['actividades'],
            'texto_familia'   => $textos['texto_familia'],
            'origen'          => $origen,
            'motivo_respaldo' => $origen === 'plantilla' ? mb_substr((string) $motivo, 0, 120) : null,
            'datos_enviados'  => $datos,
            'tokens_usados'   => $api['tokens'] ?: null,
            'generado_en'     => now(),
        ]);
    }

    /** Segundos que faltan para poder pedir otro informe de este niño (0 = ya se puede). */
    public function esperaRestante(int $estudianteId): int
    {
        $ultimo = TamizajeInforme::where('estudiante_id', $estudianteId)->max('generado_en');
        if (!$ultimo) {
            return 0;
        }
        $pasaron = now()->diffInSeconds(\Illuminate\Support\Carbon::parse($ultimo), true);

        return (int) max(0, ceil(self::ESPERA_SEGUNDOS - $pasaron));
    }

    public function ultimoDe(int $estudianteId): ?TamizajeInforme
    {
        return TamizajeInforme::where('estudiante_id', $estudianteId)
            ->orderByDesc('generado_en')->orderByDesc('id')->first();
    }

    // =========================================================================
    // DATOS ANÓNIMOS (puro)
    // =========================================================================

    /**
     * El ÚNICO conjunto de datos que sale hacia la IA.
     *
     * @param  array $resultado  semáforo calculado (TamizajeService::evaluar)
     * @param  array $resumen    ['tendencia' => int[], 'errores' => array<string,int>]
     */
    public static function datosAnonimos(string $idAnonimizado, int $grado, array $resultado, array $resumen, int $corteVerde, int $corteAmarillo): array
    {
        $areas = [];
        foreach (TextosTamizaje::AREAS as $clave => $nombre) {
            $a = $resultado['areas'][$clave] ?? ['pct' => null, 'ciclos' => 0, 'color' => null];
            $areas[$clave] = [
                'nombre'             => $nombre,
                'porcentaje_aciertos' => $a['pct'],
                'ciclos_con_datos'   => (int) $a['ciclos'],
                'color'              => $a['color'],
            ];
        }

        $errores = [];
        foreach (($resumen['errores'] ?? []) as $tipo => $cantidad) {
            if (isset(TextosTamizaje::TIPOS_ERROR[$tipo]) && $tipo !== 'azar' && (int) $cantidad > 0) {
                $errores[$tipo] = (int) $cantidad;
            }
        }
        arsort($errores);

        return [
            'id_anonimizado'          => $idAnonimizado,
            'grado'                   => $grado ?: null,
            'semaforo'                => $resultado['semaforo'],
            'cortes'                  => ['verde' => $corteVerde, 'amarillo' => $corteAmarillo],
            'ciclos_completos'        => (int) $resultado['ciclos_completos'],
            'ciclos_considerados'     => (int) $resultado['ciclos_considerados'],
            'areas'                   => $areas,
            'senales'                 => array_values($resultado['senales'] ?? []),
            'tendencia_por_ciclo_pct' => array_values(array_map('intval', $resumen['tendencia'] ?? [])),
            'errores_frecuentes'      => $errores,
        ];
    }

    /**
     * Porcentaje de aciertos por ciclo (los últimos de la ventana) y conteo de tipos de error.
     * Solo cuentan viajes completos, válidos y confiables; sin calentamiento ni toques al azar.
     *
     * @return array{tendencia:int[], errores:array<string,int>}
     */
    private function resumenDeRespuestas(int $estudianteId): array
    {
        $detalles = DetalleNumeros::where('completa', true)->where('valida', true)
            ->whereHas('sesion', fn ($q) => $q->where('estudiante_id', $estudianteId)->where('estado', 'completada'))
            ->get(['id', 'sesion_id', 'ciclo', 'viaje', 'confiable']);

        // Ciclos completos (3 viajes válidos) → los últimos de la ventana.
        $completos = $detalles->groupBy('ciclo')
            ->filter(fn ($g) => $g->pluck('viaje')->unique()->count() >= TamizajeService::VIAJES_POR_CICLO)
            ->keys()->sort()->values()->slice(-TamizajeService::VENTANA_CICLOS)->values();

        $sesionACiclo = [];
        foreach ($detalles as $d) {
            if ($d->confiable && $completos->contains($d->ciclo)) {
                $sesionACiclo[$d->sesion_id] = (int) $d->ciclo;
            }
        }
        if (!$sesionACiclo) {
            return ['tendencia' => [], 'errores' => []];
        }

        $respuestas = RespuestaNumeros::whereIn('sesion_id', array_keys($sesionACiclo))
            ->where('parte', '!=', 1)->where('azar', false)
            ->get(['sesion_id', 'es_correcto', 'tipo_error']);

        $porCiclo = [];
        $errores  = [];
        foreach ($respuestas as $r) {
            $c = $sesionACiclo[$r->sesion_id] ?? null;
            if ($c === null) {
                continue;
            }
            $porCiclo[$c][1] = ($porCiclo[$c][1] ?? 0) + 1;
            $porCiclo[$c][0] = ($porCiclo[$c][0] ?? 0) + ($r->es_correcto ? 1 : 0);
            if (!$r->es_correcto && $r->tipo_error) {
                $errores[$r->tipo_error] = ($errores[$r->tipo_error] ?? 0) + 1;
            }
        }
        ksort($porCiclo);
        $tendencia = array_values(array_map(fn ($p) => (int) round(100 * $p[0] / max(1, $p[1])), $porCiclo));

        return ['tendencia' => $tendencia, 'errores' => $errores];
    }

    // =========================================================================
    // PROMPT (puro)
    // =========================================================================

    public static function construirPrompt(array $datos): string
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Sos un asistente pedagógico que acompaña a docentes de primer ciclo (1.°, 2.° y 3.° grado, niños de 6 a 9 años) de Paraguay.
Una docente usa el juego «Mati y los números», que muestra cómo trabaja un niño con los números. Es una herramienta de APOYO:
sirve para que la docente mire con más atención. NO es una evaluación ni un diagnóstico.

LO QUE NO DEBÉS HACER (muy importante):
- NO digas ni sugieras que el niño «tiene» una dificultad, un trastorno o una condición. No uses etiquetas clínicas
  (nada de discalculia, dislexia, TDAH ni parecidos) ni palabras como diagnóstico, trastorno, déficit, retraso, problema de aprendizaje.
- NO recomiendes derivar, ni menciones psicólogos, médicos, terapias o tratamientos. Esa decisión es de la docente y la familia.
- NO uses palabras alarmantes (urgente, grave, preocupante, riesgo) ni comparaciones con otros niños.
- NO inventes datos: usá solo los que están abajo. NO pongas nombres: el niño es «el niño» o «{nombre}» (solo en el texto para la familia).
- Si los datos son pocos, decilo con prudencia. Un resultado puede deberse al cansancio, a la distracción o a un mal día.

DATOS ANÓNIMOS DEL NIÑO:
{$json}

Cómo leer los datos: «porcentaje_aciertos» es el promedio de los últimos ciclos en cada área; «color» y «semaforo» usan los cortes indicados
(verde: sin señales; amarillo: mirar con más atención; rojo: mirar con más atención y, si la docente lo ve oportuno, conversar con la familia).
«tendencia_por_ciclo_pct» va del ciclo más viejo al más nuevo. «errores_frecuentes» cuenta los tipos de error (cero = confundió el cero,
parecido = número parecido, mas_menos_uno = uno de más o de menos, operacion_contraria = sumó en vez de restar o al revés,
lejano = respuesta lejana, comparacion = comparó mal).

TU TAREA: escribí tres textos en castellano sencillo y cálido.

FORMATO: respondé ÚNICAMENTE con un objeto JSON válido con exactamente estas tres claves, sin texto antes ni después, con comillas dobles:
{
  "lectura": "Para la docente (60 a 140 palabras, tuteo rioplatense con «vos»). Qué se ve en los datos, con cifras concretas, en tono prudente: «conviene mirar con más atención…». Cerrá aclarando que no es un diagnóstico.",
  "actividades": "Para la docente: 2 o 3 actividades concretas y cortas para el aula, con material simple (fichas, semillas, tarjetas, cinta numérica), pensadas para las áreas donde más conviene mirar. Numeralas así: 1) … 2) … 3) …",
  "texto_familia": "Borrador de mensaje breve (80 a 150 palabras) de la docente a la familia, con «ustedes», cálido y sin tecnicismos. Usá el marcador {nombre} para el nombre del niño. Contá que juega «Mati y los números» en la escuela, qué se está mirando con más atención, 1 o 2 juegos simples para hacer en casa, y que no es un diagnóstico ni una evaluación. NO nombres colores, semáforo, porcentajes ni puntajes. Terminá invitando a conversar."
}
PROMPT;
    }

    // =========================================================================
    // INTERPRETAR Y FILTRAR LA RESPUESTA (puro)
    // =========================================================================

    /**
     * @return array{0:?array{lectura:string,actividades:string,texto_familia:string}, 1:?string}
     *         [textos, motivo]; textos = null si hay que usar la plantilla (y el motivo dice por qué)
     */
    public static function interpretarRespuesta(string $textoApi): array
    {
        $limpio = trim(preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $textoApi));
        $ini = strpos($limpio, '{');
        $fin = strrpos($limpio, '}');
        if ($ini !== false && $fin !== false && $fin > $ini) {
            $limpio = substr($limpio, $ini, $fin - $ini + 1);
        }

        $datos = json_decode($limpio, true);
        if (!is_array($datos)) {
            return [null, 'La IA no devolvió un JSON válido'];
        }

        $textos = [];
        foreach (['lectura', 'actividades', 'texto_familia'] as $clave) {
            $valor = $datos[$clave] ?? null;
            if (is_array($valor)) {
                $valor = implode("\n", array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $valor));
            }
            if (!is_string($valor) || trim($valor) === '') {
                return [null, "Falta el campo «{$clave}» en la respuesta de la IA"];
            }
            $textos[$clave] = trim($valor);
        }

        foreach ($textos as $clave => $texto) {
            $problemas = FiltroFrases::problemas($texto, $clave === 'texto_familia');
            if ($problemas) {
                return [null, "Frase no permitida en «{$clave}»: " . implode(', ', $problemas)];
            }
        }

        // El texto de la familia debe poder llevar el nombre; si la IA no puso el marcador, se agrega un saludo.
        if (!str_contains($textos['texto_familia'], '{nombre}')) {
            $textos['texto_familia'] = 'Hola, familia de {nombre}. ' . $textos['texto_familia'];
        }

        return [$textos, null];
    }

    // =========================================================================
    // LLAMADA A LA API
    // =========================================================================

    /** @return array{exitoso:bool, texto:string, tokens:int, error:string} */
    private function llamarApi(string $prompt): array
    {
        $apiKey = config('services.claude.key');
        if (!$apiKey) {
            Log::error('TamizajeInformeService: CLAUDE_API_KEY no configurada en .env');
            return ['exitoso' => false, 'texto' => '', 'tokens' => 0, 'error' => 'API key no configurada'];
        }

        try {
            $respuesta = Http::timeout(30)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type'      => 'application/json',
                ])
                ->post(self::API_URL, [
                    'model'      => $this->modelo,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ]);

            if ($respuesta->failed()) {
                $error = 'HTTP ' . $respuesta->status();
                Log::error('TamizajeInformeService: error de la API', ['status' => $respuesta->status()]);
                return ['exitoso' => false, 'texto' => '', 'tokens' => 0, 'error' => $error];
            }

            $datos = $respuesta->json();

            return [
                'exitoso' => true,
                'texto'   => (string) ($datos['content'][0]['text'] ?? ''),
                'tokens'  => (int) ($datos['usage']['input_tokens'] ?? 0) + (int) ($datos['usage']['output_tokens'] ?? 0),
                'error'   => '',
            ];
        } catch (\Throwable $e) {
            Log::error('TamizajeInformeService: sin conexión o error inesperado', ['mensaje' => $e->getMessage()]);
            return ['exitoso' => false, 'texto' => '', 'tokens' => 0, 'error' => 'Sin conexión con la IA'];
        }
    }
}
