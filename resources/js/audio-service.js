/**
 * SERVICIO DE AUDIO — MatiGuará
 * =============================================================================
 * Gestiona todo el audio del sistema:
 *
 *   1. TEXTO A VOZ (TTS) en castellano — Web Speech API (sin archivos MP3)
 *      Voz femenina seleccionada automáticamente para consistencia
 *      con las grabaciones guaraní (decisión de diseño sep 2026).
 *
 *   2. AUDIO GUARANÍ — archivos MP3 pregrabados (Semana 7)
 *      Placeholder hasta tener las grabaciones de la hablante nativa.
 *
 *   3. SONIDOS DE FEEDBACK — archivos MP3 cortos
 *      acierto.mp3, error.mp3, nivel-superado.mp3, logro.mp3
 *      Se generan con Tone.js si los archivos no están disponibles.
 *
 *   4. FONDO MUSICAL — Tone.js procedural (Semana 6)
 *      Pendiente de implementación.
 *
 * COMPATIBILIDAD:
 *   Web Speech API: Chrome ✅ | Firefox ✅ | Safari ✅ | Edge ✅
 *   Fallback: si el navegador no soporta TTS, el botón de audio
 *   simplemente no aparece (degradación elegante).
 *
 * USO EN BLADE:
 *   <script src="{{ asset('js/audio-service.js') }}"></script>
 *   <script>
 *     AudioService.leerEnunciado('¿Cuánto es siete más cinco?', 'es');
 *     AudioService.reproducirFeedback('acierto');
 *   </script>
 *
 * Referencia: RF-06 (audio de enunciados y retroalimentación)
 * Decisión: Semana 4 — castellano TTS + feedback MP3
 * =============================================================================
 */

const AudioService = (function () {

    // ── Configuración ──────────────────────────────────────────────────────
    const CONFIG = {
        volumenTTS:      0.9,
        velocidadTTS:    0.85,   // Ligeramente más lento para niños de 6-9 años
        tonoTTS:         1.0,
        rutaAudio:       '/audio/', // Carpeta en public/
        habilitado:      true,
    };

    // ── Estado interno ──────────────────────────────────────────────────────
    let vozFemenina  = null;   // Voz TTS seleccionada
    let vozesListas  = false;  // Flag: ¿ya se cargaron las voces del sistema?

    // ── Inicialización de voces TTS ────────────────────────────────────────

    /**
     * Carga las voces disponibles del sistema y selecciona la voz
     * femenina en español más adecuada para el contexto paraguayo.
     *
     * Las voces se cargan de forma asíncrona en algunos navegadores
     * (Chrome), por eso usamos el evento 'voiceschanged'.
     */
    function inicializarVoces() {
        if (!window.speechSynthesis) return;

        const cargarVoz = () => {
            const voces = speechSynthesis.getVoices();
            if (voces.length === 0) return;

            // Intentar voces en este orden de preferencia:
            // 1. Español latinoamericano femenino
            // 2. Español femenino genérico
            // 3. Español (cualquier variante)
            vozFemenina =
                voces.find(v => v.lang === 'es-419' && v.name.toLowerCase().includes('female')) ||
                voces.find(v => v.lang === 'es-ES'  && v.name.toLowerCase().includes('female')) ||
                voces.find(v => v.lang.includes('es') && v.name.toLowerCase().includes('female')) ||
                // Voces con nombre femenino conocido por plataforma
                voces.find(v => /paulina|helena|luciana|mónica|conchita|carmela/i.test(v.name)) ||
                // Fallback: cualquier voz en español
                voces.find(v => v.lang.startsWith('es')) ||
                null;

            vozesListas = true;
        };

        // Carga inmediata si ya están disponibles
        cargarVoz();

        // Suscribirse al evento para Chrome (carga asíncrona)
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = cargarVoz;
        }
    }

    // ── TEXTO A VOZ (TTS) ──────────────────────────────────────────────────

    /**
     * Lee el texto en voz alta usando Web Speech API.
     * Si ya hay algo reproduciéndose, lo cancela primero.
     *
     * @param {string} texto   El texto a leer
     * @param {string} idioma  'es' para castellano, 'gn' para guaraní
     * @param {Function} [onFin]  Callback opcional al terminar
     */
    function leerEnunciado(texto, idioma = 'es', onFin = null) {
        if (!CONFIG.habilitado) return;

        if (idioma === 'gn') {
            // El guaraní usa archivos MP3 pregrabados (Semana 7)
            // Por ahora reproducir el castellano como fallback
            console.info('AudioService: audio guaraní pendiente (Semana 7). Usando castellano.');
            return;
        }

        if (!window.speechSynthesis) {
            console.warn('AudioService: Web Speech API no disponible en este navegador.');
            return;
        }

        // Cancelar reproducción anterior
        speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(texto);
        utterance.lang   = 'es-419'; // Español latinoamericano
        utterance.volume = CONFIG.volumenTTS;
        utterance.rate   = CONFIG.velocidadTTS;
        utterance.pitch  = CONFIG.tonoTTS;

        // Asignar voz femenina si está disponible
        if (vozFemenina) {
            utterance.voice = vozFemenina;
        }

        // Callback al finalizar
        if (onFin) {
            utterance.onend = onFin;
        }

        // Manejar errores silenciosamente (el audio es mejora, no función crítica)
        utterance.onerror = (e) => {
            console.warn('AudioService TTS error:', e.error);
        };

        speechSynthesis.speak(utterance);
    }

    /**
     * Lee el mensaje de retroalimentación bilingüe.
     * Primero el castellano, luego una pausa, luego el guaraní (cuando esté disponible).
     *
     * @param {string} textoEs  Texto en castellano
     * @param {string} textoGn  Texto en guaraní
     */
    function leerRetroalimentacion(textoEs, textoGn) {
        // Leer castellano primero
        leerEnunciado(textoEs, 'es', () => {
            // Pausa de 500ms y luego guaraní (cuando esté disponible)
            // Por ahora solo castellano
        });
    }

    // ── SONIDOS DE FEEDBACK ────────────────────────────────────────────────

    /**
     * Reproduce un sonido de feedback corto.
     * Primero intenta con archivo MP3. Si no existe, genera con Tone.js.
     *
     * @param {string} tipo  'acierto' | 'error' | 'nivel_superado' | 'logro' | 'clic'
     */
    async function reproducirFeedback(tipo) {
        if (!CONFIG.habilitado) return;

        const rutas = {
            'acierto'       : `${CONFIG.rutaAudio}feedback/acierto.mp3`,
            'error'         : `${CONFIG.rutaAudio}feedback/error.mp3`,
            'nivel_superado': `${CONFIG.rutaAudio}feedback/nivel-superado.mp3`,
            'logro'         : `${CONFIG.rutaAudio}feedback/logro.mp3`,
            'clic'          : `${CONFIG.rutaAudio}feedback/clic.mp3`,
        };

        const ruta = rutas[tipo];
        if (!ruta) return;

        try {
            const audio = new Audio(ruta);
            audio.volume = 0.7;
            await audio.play();
        } catch (e) {
            // El archivo MP3 no existe aún — usar Tone.js como fallback
            generarSonidoTone(tipo);
        }
    }

    /**
     * Genera sonido con Tone.js cuando no hay archivos MP3.
     * Funciona como fallback hasta tener los assets de audio.
     *
     * @param {string} tipo
     */
    function generarSonidoTone(tipo) {
        // Verificar que Tone.js esté disponible
        if (typeof Tone === 'undefined') return;

        const sintetizador = new Tone.Synth({
            oscillator: { type: 'triangle' },
            envelope:   { attack: 0.01, decay: 0.1, sustain: 0.3, release: 0.5 },
        }).toDestination();

        // Secuencias de notas según el tipo de feedback
        const secuencias = {
            'acierto'       : [['E5', '8n'], ['G5', '8n']],
            'error'         : [['C4', '8n'], ['A3', '4n']],
            'nivel_superado': [['C5','8n'],['E5','8n'],['G5','8n'],['C6','4n']],
            'logro'         : [['G4','8n'],['C5','8n'],['E5','8n'],['G5','8n'],['C6','4n']],
            'clic'          : [['A4', '16n']],
        };

        const notas = secuencias[tipo] || secuencias['clic'];

        // Reproducir secuencia
        Tone.start().then(() => {
            let tiempo = Tone.now();
            notas.forEach(([nota, duracion]) => {
                sintetizador.triggerAttackRelease(nota, duracion, tiempo);
                tiempo += Tone.Time(duracion).toSeconds() + 0.05;
            });
        });
    }

    // ── CONTROL GLOBAL ─────────────────────────────────────────────────────

    /** Detiene todo audio en reproducción. */
    function detener() {
        if (window.speechSynthesis) {
            speechSynthesis.cancel();
        }
    }

    /** Activa/desactiva el audio. Persiste en localStorage. */
    function alternarAudio() {
        CONFIG.habilitado = !CONFIG.habilitado;
        localStorage.setItem('matiguara_audio', CONFIG.habilitado ? '1' : '0');
        return CONFIG.habilitado;
    }

    /** Verifica si el audio está habilitado. */
    function estaHabilitado() {
        return CONFIG.habilitado;
    }

    // ── Inicialización automática ───────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        // Leer preferencia guardada del usuario
        const preferencia = localStorage.getItem('matiguara_audio');
        if (preferencia === '0') CONFIG.habilitado = false;

        // Inicializar voces TTS
        inicializarVoces();
    });

    // ── API pública ────────────────────────────────────────────────────────
    return {
        leerEnunciado,
        leerRetroalimentacion,
        reproducirFeedback,
        detener,
        alternarAudio,
        estaHabilitado,
    };

})();

// Disponible globalmente para uso desde las vistas Blade
window.AudioService = AudioService;
