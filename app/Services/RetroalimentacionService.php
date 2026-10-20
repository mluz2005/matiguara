<?php

namespace App\Services;

use App\Models\MensajeRetro;
use App\Models\ProgresoEstudiante;
use App\Repositories\EstudianteRepository;
use App\Repositories\MensajeRetroRepository;

/**
 * SERVICIO: RetroalimentacionService
 * =========================================================================
 * Gestiona los mensajes de retroalimentación bilingüe que se muestran
 * al estudiante después de cada respuesta (RF-05, RN-06).
 *
 * SISTEMA DE NO-REPETICIÓN:
 * El campo progreso_estudiante.ultimo_mensaje_id registra el último mensaje
 * mostrado al estudiante en ese nivel. Este servicio lo excluye al
 * seleccionar el próximo mensaje, garantizando variedad.
 *
 * SELECCIÓN DE CONTEXTO:
 * El contexto del mensaje se determina automáticamente según la situación:
 *   'primer_intento' → el estudiante responde por primera vez el ejercicio
 *   'reintento'      → el estudiante ya intentó antes este ejercicio
 *   'maximo_racha'   → acaba de batir su racha máxima de aciertos
 *   'general'        → contexto estándar
 *
 * Referencia: CU-20 (Mostrar retroalimentación bilingüe)
 * RF: RF-05 (mensajes bilingüe simultáneos)
 * RN: RN-06 (bilingüismo simultáneo obligatorio)
 * =========================================================================
 */
class RetroalimentacionService
{
    public function __construct(
        private MensajeRetroRepository $mensajes,
        private EstudianteRepository $estudiantes
    ) {
    }

    /**
     * Obtiene el mensaje de retroalimentación apropiado para la situación.
     *
     * El mensaje se devuelve con ambos textos (español + guaraní) para
     * mostrarlos SIMULTÁNEAMENTE al estudiante (RN-06: no se elige un idioma,
     * ambos se muestran siempre al mismo tiempo).
     *
     * @param  string $tipo             'acierto', 'error', 'racha', 'nivel_superado'
     * @param  bool   $esPrimerIntento  ¿Es la primera vez que intenta este ejercicio?
     * @param  bool   $maximoRacha     ¿Acaba de batir su racha máxima de aciertos?
     * @param  int|null $excluirId      ID del último mensaje mostrado (anti-repetición)
     * @return MensajeRetro|null
     */
    public function obtenerMensaje(
        string $tipo,
        bool $esPrimerIntento = true,
        bool $maximoRacha = false,
        ?int $excluirId = null
    ): ?MensajeRetro {
        // Determinar el contexto más específico disponible
        $contexto = $this->determinarContexto($tipo, $esPrimerIntento, $maximoRacha);

        // Intentar primero con el contexto específico
        $mensaje = $this->mensajes->aleatorio($tipo, $contexto, $excluirId);

        // Si no hay mensaje para ese contexto específico, usar 'general'
        if (!$mensaje && $contexto !== 'general') {
            $mensaje = $this->mensajes->aleatorio($tipo, 'general', $excluirId);
        }

        // Si tampoco hay con exclusión, quitar el filtro de exclusión
        if (!$mensaje && $excluirId) {
            $mensaje = $this->mensajes->aleatorio($tipo, 'general', null);
        }

        return $mensaje;
    }

    /**
     * Obtiene un mensaje y registra su ID en el progreso del estudiante
     * para el sistema de no-repetición.
     *
     * Este método combina obtenerMensaje() con el registro automático.
     * Llamar desde el controller tras procesar cada respuesta.
     *
     * @param  string               $tipo
     * @param  ProgresoEstudiante  $progreso   El registro de progreso del nivel actual
     * @param  bool                 $esPrimerIntento
     * @param  bool                 $maximoRacha
     * @return MensajeRetro|null
     */
    public function obtenerYRegistrar(
        string $tipo,
        ProgresoEstudiante $progreso,
        bool $esPrimerIntento = true,
        bool $maximoRacha = false
    ): ?MensajeRetro {
        $mensaje = $this->obtenerMensaje(
            $tipo,
            $esPrimerIntento,
            $maximoRacha,
            $progreso->ultimo_mensaje_id  // Excluir el último mostrado
        );

        if ($mensaje) {
            // Registrar este mensaje como el último mostrado
            $this->estudiantes->guardarUltimoMensaje($progreso, $mensaje->id);
        }

        return $mensaje;
    }

    /**
     * Determina el contexto del mensaje según la situación pedagógica.
     *
     * @param  string $tipo
     * @param  bool   $esPrimerIntento
     * @param  bool   $maximoRacha
     * @return string  Contexto: 'general' | 'primer_intento' | 'reintento' | 'maximo_racha'
     */
    private function determinarContexto(
        string $tipo,
        bool $esPrimerIntento,
        bool $maximoRacha
    ): string {
        // Máxima racha tiene prioridad especial
        if ($tipo === 'racha' && $maximoRacha) {
            return 'maximo_racha';
        }

        // Para errores: distinguir primer intento de reintento
        if ($tipo === 'error') {
            return $esPrimerIntento ? 'primer_intento' : 'reintento';
        }

        // Para aciertos: primer intento vs reintento (después de errores)
        if ($tipo === 'acierto') {
            return $esPrimerIntento ? 'primer_intento' : 'reintento';
        }

        return 'general';
    }

    /**
     * Obtiene o crea el registro de progreso del estudiante para el nivel actual.
     * Usado por el controller antes de llamar a obtenerYRegistrar().
     *
     * @param  int $estudianteId
     * @param  int $juegoId
     * @param  int $nivelId
     * @param  int $grado
     * @return ProgresoEstudiante
     */
    public function obtenerProgreso(
        int $estudianteId,
        int $juegoId,
        int $nivelId,
        int $grado
    ): ProgresoEstudiante {
        return $this->estudiantes->obtenerOCrearProgreso($estudianteId, $juegoId, $nivelId, $grado);
    }
}
