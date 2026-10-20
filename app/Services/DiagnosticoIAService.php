<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Estudiante;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\SesionRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SERVICIO: DiagnosticoIAService
 * =========================================================================
 * Genera diagnósticos pedagógicos usando la API Claude (Anthropic).
 *
 * FLUJO COMPLETO (CU-11, CU-23, CU-24):
 *   1. El docente solicita el diagnóstico desde el panel (CU-11)
 *   2. Este servicio recopila métricas del estudiante ANONIMIZADAS (RN-01)
 *   3. Construye un prompt pedagógico estructurado
 *   4. Llama a la API Claude y solicita respuesta en JSON
 *   5. Valida el JSON recibido (RF-11)
 *   6. Guarda el resultado en la tabla diagnosticos
 *   7. Si la API falla, activa tolerancia a fallos (RF-12, CU-24)
 *
 * PRIVACIDAD ABSOLUTA (RN-01 / UNICEF 2021):
 *   La API Claude NUNCA recibe:
 *     ❌ Nombre del estudiante
 *     ❌ Código de acceso
 *     ❌ ID interno de la base de datos
 *   Solo recibe:
 *     ✅ id_anonimizado (UUID v4)
 *     ✅ Métricas numéricas agregadas
 *     ✅ Patrones de error (sin identificar al estudiante)
 *
 * RESTRICCIÓN CLÍNICA (RN-02):
 *   El prompt instruye explícitamente a la API para que NO emita
 *   diagnósticos médicos, clínicos ni psicopedagógicos.
 *   Solo patrones de desempeño matemático.
 *
 * SOLO BAJO DEMANDA (RN-05):
 *   Este servicio NUNCA se llama automáticamente.
 *   Solo desde DocenteController::solicitarDiagnostico() por acción
 *   explícita del docente.
 *
 * Referencia: CU-11, CU-12, CU-23, CU-24
 * RF: RF-10, RF-11, RF-12, RF-15
 * RN: RN-01, RN-02, RN-05
 * =========================================================================
 */
class DiagnosticoIAService
{
    /**
     * URL base de la API de Anthropic.
     * Configurada en .env: CLAUDE_API_KEY
     */
    private const API_URL    = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    /**
     * Modelo a usar. claude-sonnet-4-6 es el modelo de producción actual.
     * Configurable en .env: CLAUDE_MODEL
     */
    private string $modelo;

    /**
     * Máximo de tokens en la respuesta. 1000 es suficiente para el diagnóstico.
     * Configurable en .env: CLAUDE_MAX_TOKENS
     */
    private int $maxTokens;

    public function __construct(
        private SesionRepository $sesiones,
        private EstudianteRepository $estudiantes,
        private DiagnosticoRepository $diagnosticos
    ) {
        $this->modelo    = config('services.claude.model', 'claude-sonnet-4-6');
        $this->maxTokens = config('services.claude.max_tokens', 1000);
    }

    // =========================================================================
    // MÉTODO PRINCIPAL
    // =========================================================================

    /**
     * Genera un diagnóstico pedagógico completo para un estudiante.
     *
     * @param  Estudiante $estudiante  El estudiante a diagnosticar
     * @param  int        $docenteId  El docente que solicita el diagnóstico
     * @param  int|null   $juegoId    Filtrar por juego específico (null = todos)
     * @return Diagnostico            El diagnóstico guardado en la BD
     */
    public function generar(
        Estudiante $estudiante,
        int $docenteId,
        ?int $juegoId = null
    ): Diagnostico {
        // ── 1. Recopilar métricas del estudiante (anonimizadas) ───────────
        $metricas = $this->recopilarMetricas($estudiante, $juegoId);

        // ── 2. Calcular snapshot numérico (RF-15) ─────────────────────────
        $snapshot = $this->calcularSnapshot($metricas);

        // ── 3. Construir el prompt ────────────────────────────────────────
        $prompt = $this->construirPrompt($estudiante->id_anonimizado, $metricas);

        // ── 4. Llamar a la API Claude ─────────────────────────────────────
        $respuestaApi = $this->llamarApi($prompt);

        // ── 5. Validar y procesar la respuesta (RF-11) ───────────────────
        if ($respuestaApi['exitoso']) {
            $contenido = $this->validarYExtraer($respuestaApi['texto']);
        } else {
            // RF-12: tolerancia a fallos — activar respaldo
            $contenido = $this->generarRespaldoLocal($metricas, $respuestaApi['error']);
        }

        // ── 6. Guardar en la base de datos ────────────────────────────────
        return $this->diagnosticos->crear([
            'estudiante_id'       => $estudiante->id,
            'docente_id'          => $docenteId,
            'id_anonimizado'      => $estudiante->id_anonimizado, // RN-01: copia del UUID
            'fecha_generacion'    => now(),
            'puntaje_promedio'    => $snapshot['puntaje_promedio'],
            'porcentaje_aciertos' => $snapshot['porcentaje_aciertos'],
            'nivel_max_alcanzado' => $snapshot['nivel_max_alcanzado'],
            'fortalezas'          => $contenido['fortalezas'],
            'dificultades'        => $contenido['dificultades'],
            'recomendacion'       => $contenido['recomendacion'],
            'tokens_usados'       => $respuestaApi['tokens'] ?? null,
            'estado'              => $respuestaApi['exitoso'] ? 'exitoso' : 'fallido',
        ]);
    }

    // =========================================================================
    // RECOPILACIÓN DE MÉTRICAS (ANONIMIZADAS)
    // =========================================================================

    /**
     * Recopila todas las métricas del estudiante sin incluir datos personales.
     * Solo datos numéricos y patrones de comportamiento.
     *
     * @param  Estudiante $estudiante
     * @param  int|null   $juegoId
     * @return array
     */
    private function recopilarMetricas(Estudiante $estudiante, ?int $juegoId): array
    {
        // Sesiones completadas del estudiante (opcionalmente de un solo juego)
        $sesiones = $this->sesiones->completadasDe($estudiante->id, $juegoId);

        if ($sesiones->isEmpty()) {
            return $this->metricasVacias();
        }

        // ── Estadísticas generales ─────────────────────────────────────────
        $totalSesiones     = $sesiones->count();
        $totalAciertos     = $sesiones->sum('aciertos');
        $totalErrores      = $sesiones->sum('errores');
        $totalRespuestas   = $totalAciertos + $totalErrores;
        $porcentajeAciertos = $totalRespuestas > 0
            ? round(($totalAciertos / $totalRespuestas) * 100, 1)
            : 0;
        $puntajePromedio   = round($sesiones->avg('puntaje_obtenido'), 1);
        $nivelMaxAlcanzado = $sesiones->max(fn($s) => $s->nivel->numero_nivel ?? 0);

        // ── Análisis de errores por tipo ──────────────────────────────────
        $sesionIds = $sesiones->pluck('id');
        $erroresPorTipo = $this->sesiones->erroresPorTipo($sesionIds);

        // ── Tiempo de respuesta promedio ──────────────────────────────────
        $tiempoPromedio = $this->sesiones->tiempoRespuestaPromedioMs($sesionIds);

        // ── Racha actual ──────────────────────────────────────────────────
        $rachaActual = $this->estudiantes->rachaActualMaximaEnDias($estudiante);

        // ── Niveles desbloqueados ──────────────────────────────────────────
        $nivelesDesbloqueados = $this->estudiantes->contarNivelesDesbloqueados($estudiante);

        // ── Rendimiento por rango de números ──────────────────────────────
        // (diferencia entre ejercicios fáciles 1-5 y difíciles 6-10)
        $movimientos = $this->sesiones->movimientosConEjercicio($sesionIds);

        $aciertosRangoFacil  = 0; $totalRangoFacil  = 0;
        $aciertosRangoDificil = 0; $totalRangoDificil = 0;

        foreach ($movimientos as $mov) {
            if (!$mov->ejercicio) continue;
            $maxOperando = max($mov->ejercicio->valor_a ?? 0, $mov->ejercicio->valor_b ?? 0);
            if ($maxOperando <= 5) {
                $totalRangoFacil++;
                if ($mov->es_correcto) $aciertosRangoFacil++;
            } else {
                $totalRangoDificil++;
                if ($mov->es_correcto) $aciertosRangoDificil++;
            }
        }

        return [
            'total_sesiones'         => $totalSesiones,
            'total_aciertos'         => $totalAciertos,
            'total_errores'          => $totalErrores,
            'porcentaje_aciertos'    => $porcentajeAciertos,
            'puntaje_promedio'       => $puntajePromedio,
            'nivel_max_alcanzado'    => $nivelMaxAlcanzado,
            'niveles_desbloqueados'  => $nivelesDesbloqueados,
            'racha_actual_dias'      => $rachaActual,
            'tiempo_respuesta_prom_ms' => round($tiempoPromedio ?? 0),
            'errores_por_tipo'       => $erroresPorTipo,
            'pct_aciertos_rango_1_5' => $totalRangoFacil > 0
                ? round(($aciertosRangoFacil / $totalRangoFacil) * 100, 1) : null,
            'pct_aciertos_rango_6_10'=> $totalRangoDificil > 0
                ? round(($aciertosRangoDificil / $totalRangoDificil) * 100, 1) : null,
        ];
    }

    /**
     * Métricas vacías cuando el estudiante no tiene sesiones.
     */
    private function metricasVacias(): array
    {
        return [
            'total_sesiones'          => 0,
            'total_aciertos'          => 0,
            'total_errores'           => 0,
            'porcentaje_aciertos'     => 0,
            'puntaje_promedio'        => 0,
            'nivel_max_alcanzado'     => 0,
            'niveles_desbloqueados'   => 0,
            'racha_actual_dias'       => 0,
            'tiempo_respuesta_prom_ms'=> 0,
            'errores_por_tipo'        => [],
            'pct_aciertos_rango_1_5'  => null,
            'pct_aciertos_rango_6_10' => null,
        ];
    }

    /**
     * Calcula el snapshot numérico para guardar en la BD (RF-15).
     * Estos valores son ESTÁTICOS — no cambian aunque el estudiante mejore.
     */
    private function calcularSnapshot(array $metricas): array
    {
        return [
            'puntaje_promedio'    => $metricas['puntaje_promedio'],
            'porcentaje_aciertos' => $metricas['porcentaje_aciertos'],
            'nivel_max_alcanzado' => $metricas['nivel_max_alcanzado'],
        ];
    }

    // =========================================================================
    // CONSTRUCCIÓN DEL PROMPT
    // =========================================================================

    /**
     * Construye el prompt para la API Claude.
     *
     * El prompt es en español, pedagógico, y solicita JSON estructurado.
     * El id_anonimizado es el ÚNICO identificador que incluimos (RN-01).
     *
     * DISEÑO DEL PROMPT:
     * - Rol claro: asistente pedagógico, NO diagnóstico clínico (RN-02)
     * - Datos anonimizados: solo UUID + métricas numéricas
     * - Solicitud de JSON con formato exacto para validación (RF-11)
     * - Instrucción de lenguaje: accesible para docentes de primaria
     * - Límite de longitud por sección: evita respuestas demasiado largas
     *
     * @param  string $idAnonimizado  UUID del estudiante
     * @param  array  $metricas
     * @return string
     */
    private function construirPrompt(string $idAnonimizado, array $metricas): string
    {
        // Formatear tipos de error para el prompt
        $erroresTexto = empty($metricas['errores_por_tipo'])
            ? 'Sin errores clasificados aún.'
            : collect($metricas['errores_por_tipo'])
                ->map(fn($cant, $tipo) => "  - {$tipo}: {$cant} ocurrencias")
                ->join("\n");

        // Formatear rendimiento por rango
        $rangoFacil   = $metricas['pct_aciertos_rango_1_5']  !== null
            ? "{$metricas['pct_aciertos_rango_1_5']}% de aciertos" : 'Sin datos';
        $rangoDificil = $metricas['pct_aciertos_rango_6_10'] !== null
            ? "{$metricas['pct_aciertos_rango_6_10']}% de aciertos" : 'Sin datos';

        // Tiempo de respuesta en segundos (más legible)
        $tiempoSeg = $metricas['tiempo_respuesta_prom_ms'] > 0
            ? round($metricas['tiempo_respuesta_prom_ms'] / 1000, 1) . ' segundos'
            : 'No disponible';

        return <<<PROMPT
Sos un asistente pedagógico especializado en matemáticas del primer ciclo
de la Educación Escolar Básica de Paraguay (1°, 2° y 3° grado, niños de 6 a 9 años).

IMPORTANTE — LO QUE NO DEBÉS HACER:
- NO emitas diagnósticos médicos, clínicos ni psicopedagógicos.
- NO identifiques al estudiante. Solo usás el ID anónimo.
- NO uses lenguaje técnico complejo. El docente es de primaria.
- NO inventes datos. Solo analizás los que se te proveen.

TU TAREA:
Analizá los datos de desempeño matemático del estudiante y generá
un informe pedagógico breve en tres secciones.

--- DATOS DEL ESTUDIANTE (completamente anonimizados) ---
ID anónimo: {$idAnonimizado}
Sesiones completadas: {$metricas['total_sesiones']}
Total de respuestas: {$metricas['total_aciertos']} correctas / {$metricas['total_errores']} incorrectas
Porcentaje de aciertos: {$metricas['porcentaje_aciertos']}%
Puntaje promedio por sesión: {$metricas['puntaje_promedio']} puntos
Nivel máximo alcanzado: Nivel {$metricas['nivel_max_alcanzado']}
Niveles desbloqueados: {$metricas['niveles_desbloqueados']}
Racha de días activos: {$metricas['racha_actual_dias']} días consecutivos
Tiempo de respuesta promedio: {$tiempoSeg}

Rendimiento por rango de operandos:
  - Números 1 al 5: {$rangoFacil}
  - Números 6 al 10: {$rangoDificil}

Clasificación de errores:
{$erroresTexto}

--- FORMATO DE RESPUESTA REQUERIDO ---
Respondé ÚNICAMENTE con un objeto JSON válido con exactamente estas tres claves.
No agregues texto antes ni después del JSON.
No uses comillas simples. Usá comillas dobles para todas las cadenas.
Cada valor debe tener entre 80 y 200 palabras.

{
  "fortalezas": "Párrafo describiendo qué hace bien el estudiante, con datos concretos de las métricas. Lenguaje positivo y motivador.",
  "dificultades": "Párrafo describiendo los patrones de dificultad detectados en las métricas. Sin diagnóstico clínico. Solo patrones matemáticos observables.",
  "recomendacion": "Párrafo con 2 o 3 sugerencias concretas y prácticas para el docente. Actividades específicas para el aula que complementen la práctica digital."
}
PROMPT;
    }

    // =========================================================================
    // LLAMADA A LA API CLAUDE
    // =========================================================================

    /**
     * Llama a la API de Anthropic y retorna la respuesta.
     *
     * @param  string $prompt
     * @return array ['exitoso' => bool, 'texto' => string, 'tokens' => int, 'error' => string]
     */
    private function llamarApi(string $prompt): array
    {
        $apiKey = config('services.claude.key');

        if (!$apiKey) {
            Log::error('DiagnosticoIAService: CLAUDE_API_KEY no configurada en .env');
            return [
                'exitoso' => false,
                'texto'   => '',
                'tokens'  => 0,
                'error'   => 'API key no configurada',
            ];
        }

        try {
            $respuesta = Http::timeout(30) // 30 segundos máximo (RF-12)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type'      => 'application/json',
                ])
                ->post(self::API_URL, [
                    'model'      => $this->modelo,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if ($respuesta->failed()) {
                $errorMsg = "HTTP {$respuesta->status()}: " . $respuesta->body();
                Log::error('DiagnosticoIAService: API error', ['error' => $errorMsg]);
                return ['exitoso' => false, 'texto' => '', 'tokens' => 0, 'error' => $errorMsg];
            }

            $datos  = $respuesta->json();
            $texto  = $datos['content'][0]['text'] ?? '';
            $tokens = ($datos['usage']['input_tokens'] ?? 0)
                    + ($datos['usage']['output_tokens'] ?? 0);

            return [
                'exitoso' => true,
                'texto'   => $texto,
                'tokens'  => $tokens,
                'error'   => '',
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout o sin conexión — RF-12 tolerancia a fallos
            Log::error('DiagnosticoIAService: timeout o sin conexión', [
                'mensaje' => $e->getMessage(),
            ]);
            return [
                'exitoso' => false,
                'texto'   => '',
                'tokens'  => 0,
                'error'   => 'Timeout o sin conexión: ' . $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('DiagnosticoIAService: error inesperado', [
                'mensaje' => $e->getMessage(),
            ]);
            return [
                'exitoso' => false,
                'texto'   => '',
                'tokens'  => 0,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // VALIDACIÓN Y EXTRACCIÓN (RF-11)
    // =========================================================================

    /**
     * Valida que la respuesta de la API tenga el formato JSON esperado
     * y extrae los tres campos requeridos (RF-11).
     *
     * Si el JSON no es válido o faltan campos, activa el respaldo.
     *
     * @param  string $textoApi  Texto devuelto por la API
     * @return array ['fortalezas', 'dificultades', 'recomendacion']
     */
    private function validarYExtraer(string $textoApi): array
    {
        // Limpiar posibles bloques de código Markdown (```json ... ```)
        $textoLimpio = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $textoApi);
        $textoLimpio = trim($textoLimpio);

        // Intentar decodificar el JSON
        $datos = json_decode($textoLimpio, true);

        // Validar que sea JSON válido con los tres campos requeridos (RF-11)
        $camposRequeridos = ['fortalezas', 'dificultades', 'recomendacion'];
        $tieneTodasLasClaves = $datos !== null
            && collect($camposRequeridos)->every(fn($c) => isset($datos[$c]) && !empty($datos[$c]));

        if (!$tieneTodasLasClaves) {
            Log::warning('DiagnosticoIAService: JSON inválido o incompleto', [
                'respuesta_cruda' => substr($textoApi, 0, 500),
            ]);
            // Activar respaldo con el texto recibido como contexto
            return $this->generarRespaldoLocal([], 'JSON inválido recibido de la API');
        }

        return [
            'fortalezas'   => $datos['fortalezas'],
            'dificultades' => $datos['dificultades'],
            'recomendacion'=> $datos['recomendacion'],
        ];
    }

    // =========================================================================
    // TOLERANCIA A FALLOS (RF-12, CU-24)
    // =========================================================================

    /**
     * Genera un diagnóstico de respaldo cuando la API falla.
     *
     * El diagnóstico de respaldo es genérico pero útil:
     * usa las métricas numéricas locales para construir un informe
     * básico sin depender de la API.
     *
     * @param  array  $metricas
     * @param  string $errorRazon
     * @return array
     */
    private function generarRespaldoLocal(array $metricas, string $errorRazon): array
    {
        Log::info('DiagnosticoIAService: activando respaldo local', ['razon' => $errorRazon]);

        // Si no hay métricas, devolver mensaje genérico
        if (empty($metricas) || ($metricas['total_sesiones'] ?? 0) === 0) {
            return [
                'fortalezas'   => 'El estudiante aún no tiene suficientes sesiones registradas para generar un análisis detallado. Se recomienda que complete al menos 3 sesiones antes de solicitar un diagnóstico.',
                'dificultades' => 'No hay suficientes datos de desempeño para identificar patrones de dificultad con precisión en este momento.',
                'recomendacion'=> 'Invite al estudiante a jugar al menos 3 sesiones completas. Una vez que haya datos suficientes, el diagnóstico IA podrá proporcionar análisis más precisos y recomendaciones personalizadas.',
            ];
        }

        $pct   = $metricas['porcentaje_aciertos'] ?? 0;
        $nivel = $metricas['nivel_max_alcanzado'] ?? 1;
        $racha = $metricas['racha_actual_dias'] ?? 0;

        // Construir fortalezas basadas en métricas
        $fortalezas = "El estudiante ha completado {$metricas['total_sesiones']} sesiones de práctica";
        if ($racha > 0) {
            $fortalezas .= " y mantiene una racha de {$racha} día(s) consecutivos de práctica, lo que demuestra constancia";
        }
        if ($pct >= 70) {
            $fortalezas .= ". Su porcentaje de aciertos del {$pct}% indica una comprensión sólida del contenido trabajado";
        }
        $fortalezas .= ". Ha alcanzado hasta el nivel {$nivel} del sistema adaptativo.";

        // Construir dificultades
        $dificultades = $pct < 60
            ? "El porcentaje de aciertos del {$pct}% sugiere que el estudiante aún está consolidando los conceptos de este nivel. "
            : "El estudiante muestra buen rendimiento general con {$pct}% de aciertos. ";

        if (!empty($metricas['errores_por_tipo'])) {
            $tipoMayoritario = array_key_first($metricas['errores_por_tipo']);
            $dificultades .= "Se observa mayor frecuencia de errores de tipo '{$tipoMayoritario}', lo que puede indicar un área de atención específica.";
        }

        // Recomendación genérica
        $recomendacion = $pct >= 70
            ? "El estudiante está listo para avanzar al siguiente nivel. Se recomienda mantener la práctica diaria para consolidar lo aprendido. Reforzar en el aula con material concreto (fichas, bloques) para anclar los conceptos digitales en experiencias físicas."
            : "Se recomienda consolidar el nivel actual antes de avanzar. Dedique tiempo en el aula a revisar los ejercicios donde el estudiante tuvo más dificultades, utilizando material concreto. La práctica constante en MatiGuará complementa el trabajo presencial.";

        // Nota aclaratoria sobre el origen del diagnóstico
        $nota = " (Nota: este análisis fue generado localmente por falta de conexión con el servicio de IA. Solicitá un nuevo diagnóstico cuando haya conectividad.)";

        return [
            'fortalezas'   => $fortalezas,
            'dificultades' => $dificultades . $nota,
            'recomendacion'=> $recomendacion,
        ];
    }
}
