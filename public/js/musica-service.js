/**
 * SERVICIO DE MÚSICA — MatiGuará
 * =============================================================================
 * Genera música de fondo procedural con Tone.js.
 * No usa archivos MP3 — la música se genera en tiempo real con JavaScript.
 *
 * DECISIÓN DE DISEÑO (sep 2026):
 * Se eligió Tone.js en lugar de MIDI porque:
 *   - MIDI no tiene soporte nativo en navegadores modernos
 *   - Tone.js genera música sin archivos externos (cero copyright)
 *   - La música puede REACCIONAR al juego (tempo, armonía, efectos)
 *   - Funciona en todos los navegadores y celulares
 *
 * ESCALA PENTATÓNICA (Do mayor):
 * C4, D4, E4, G4, A4, C5, D5, E5, G5, A5
 * Siempre suena agradable — ninguna combinación disuena.
 * Ideal para niños: simple, alegre, no cansa después de escucharla muchas veces.
 *
 * CAPAS DE MÚSICA:
 *   1. Melodía  → notas aleatorias de la escala pentatónica
 *   2. Bajo     → notas graves que marcan el pulso
 *   3. Arpegio  → notas rápidas decorativas (en Flash Matemático)
 *
 * REACCIONES AL JUEGO:
 *   acierto()    → acorde brillante ascendente
 *   error()      → nota breve descendente
 *   nivelSubir() → fanfarria corta
 *   logro()      → arpeggio ascendente completo
 *   countdown()  → aumenta el tempo cuando el tiempo se acaba
 *
 * USO EN BLADE:
 *   <script src="{{ asset('js/musica-service.js') }}"></script>
 *   <script>
 *     MusicaService.iniciar();
 *     MusicaService.acierto();
 *   </script>
 *
 * Referencia: Decisión de diseño Semana 6 | RF-03 (gamificación)
 * =============================================================================
 */

const MusicaService = (function () {

    // ── Configuración ──────────────────────────────────────────────────────
    const CONFIG = {
        bpm:            88,      // Tempo base — agradable para niños
        volumenMelodía: -18,     // dB — no compite con la voz del enunciado
        volumenBajo:    -22,     // dB
        volumenEfectos: -10,     // dB
        habilitado:     true,
        iniciado:       false,   // Tone.js requiere interacción del usuario primero
    };

    // Escala pentatónica de Do mayor
    const ESCALA = ['C4','D4','E4','G4','A4','C5','D5','E5','G5','A5'];
    const ESCALA_ALTA = ['C5','E5','G5','A5','C6'];

    // ── Sintetizadores ────────────────────────────────────────────────────
    let sintetizadorMelodia  = null;
    let sintetizadorBajo     = null;
    let sintetizadorEfectos  = null;
    let secuenciaMelodia     = null;
    let secuenciaBajo        = null;

    // ── Inicialización ────────────────────────────────────────────────────

    /**
     * Inicializa Tone.js y comienza la música de fondo.
     * DEBE llamarse desde un evento de usuario (click, touch) — restricción del navegador.
     */
    async function iniciar() {
        if (!CONFIG.habilitado) return;
        if (!esToneDisponible()) return;
        if (CONFIG.iniciado) return;

        try {
            await Tone.start();
            Tone.Transport.bpm.value = CONFIG.bpm;

            _crearSintetizadores();
            _iniciarMelodia();
            _iniciarBajo();

            Tone.Transport.start();
            CONFIG.iniciado = true;

        } catch (e) {
            console.warn('MusicaService: no se pudo iniciar Tone.js', e);
        }
    }

    /**
     * Detiene toda la música.
     */
    function detener() {
        if (!CONFIG.iniciado) return;
        Tone.Transport.stop();
        secuenciaMelodia?.stop();
        secuenciaBajo?.stop();
        CONFIG.iniciado = false;
    }

    /**
     * Pausa la música (ej: durante el countdown del Flash Matemático).
     */
    function pausar() {
        if (CONFIG.iniciado) Tone.Transport.pause();
    }

    /**
     * Reanuda la música después de una pausa.
     */
    function reanudar() {
        if (CONFIG.iniciado) Tone.Transport.start();
    }

    // ── Efectos de feedback ───────────────────────────────────────────────

    /**
     * Efecto sonoro al acertar un ejercicio.
     * Dos notas brillantes ascendentes.
     */
    function acierto() {
        if (!CONFIG.iniciado || !sintetizadorEfectos) return;
        const ahora = Tone.now();
        sintetizadorEfectos.triggerAttackRelease('E5', '16n', ahora);
        sintetizadorEfectos.triggerAttackRelease('G5', '16n', ahora + 0.15);
    }

    /**
     * Efecto sonoro al errar un ejercicio.
     * Nota breve descendente — neutro, no penalizante.
     */
    function error() {
        if (!CONFIG.iniciado || !sintetizadorEfectos) return;
        sintetizadorEfectos.triggerAttackRelease('A3', '8n', Tone.now());
    }

    /**
     * Fanfarria al subir de nivel.
     * Arpegio ascendente de 4 notas.
     */
    function nivelSubir() {
        if (!CONFIG.iniciado || !sintetizadorEfectos) return;
        const ahora = Tone.now();
        ['C5', 'E5', 'G5', 'C6'].forEach((nota, i) => {
            sintetizadorEfectos.triggerAttackRelease(nota, '8n', ahora + i * 0.12);
        });
    }

    /**
     * Celebración al obtener un logro.
     * Arpegio completo + nota larga final.
     */
    function logro() {
        if (!CONFIG.iniciado || !sintetizadorEfectos) return;
        const ahora  = Tone.now();
        const notas  = ['C5','E5','G5','A5','C6','E6'];
        notas.forEach((nota, i) => {
            sintetizadorEfectos.triggerAttackRelease(
                nota,
                i === notas.length - 1 ? '4n' : '16n',
                ahora + i * 0.1
            );
        });
    }

    /**
     * Aumenta el tempo cuando el countdown está por agotarse.
     * Crea tensión sonora para el Flash Matemático.
     * @param {number} segundosRestantes
     */
    function countdownTension(segundosRestantes) {
        if (!CONFIG.iniciado) return;
        if (segundosRestantes <= 2) {
            Tone.Transport.bpm.rampTo(120, 0.5); // Acelera el tempo
        } else if (segundosRestantes <= 4) {
            Tone.Transport.bpm.rampTo(100, 1);
        }
    }

    /**
     * Restaura el tempo normal después del countdown.
     */
    function restaurarTempo() {
        if (!CONFIG.iniciado) return;
        Tone.Transport.bpm.rampTo(CONFIG.bpm, 1);
    }

    // ── Control de usuario ────────────────────────────────────────────────

    /**
     * Activa o desactiva toda la música.
     * La preferencia se guarda en localStorage.
     */
    function alternar() {
        CONFIG.habilitado = !CONFIG.habilitado;
        localStorage.setItem('matiguara_musica', CONFIG.habilitado ? '1' : '0');

        if (CONFIG.habilitado) {
            iniciar();
        } else {
            detener();
        }

        return CONFIG.habilitado;
    }

    function estaActiva() {
        return CONFIG.habilitado && CONFIG.iniciado;
    }

    // ── Construcción de sintetizadores ────────────────────────────────────

    function _crearSintetizadores() {
        // Melodía: sonido suave tipo flauta
        sintetizadorMelodia = new Tone.Synth({
            oscillator: { type: 'triangle' },
            envelope:   { attack: 0.05, decay: 0.3, sustain: 0.4, release: 0.8 },
            volume:     CONFIG.volumenMelodía,
        }).toDestination();

        // Bajo: sonido cálido redondeado
        sintetizadorBajo = new Tone.Synth({
            oscillator: { type: 'sine' },
            envelope:   { attack: 0.1, decay: 0.2, sustain: 0.6, release: 1.0 },
            volume:     CONFIG.volumenBajo,
        }).toDestination();

        // Efectos: sonido brillante para feedback
        sintetizadorEfectos = new Tone.Synth({
            oscillator: { type: 'square4' },
            envelope:   { attack: 0.01, decay: 0.1, sustain: 0.3, release: 0.5 },
            volume:     CONFIG.volumenEfectos,
        }).toDestination();
    }

    function _iniciarMelodia() {
        let posicion = 0;
        // Patrón de notas: avanza en la escala con variación aleatoria
        const patron = _generarPatronMelodia();

        secuenciaMelodia = new Tone.Sequence(
            (time, nota) => {
                if (nota !== 'r') { // 'r' = silencio
                    sintetizadorMelodia.triggerAttackRelease(nota, '8n', time);
                }
            },
            patron,
            '4n' // Una nota cada negra
        );
        secuenciaMelodia.start(0);
    }

    function _iniciarBajo() {
        // Bajo: patrón simple que marca el pulso (raíz y quinta)
        const patronBajo = ['C3', 'r', 'G3', 'r', 'C3', 'r', 'A3', 'r'];

        secuenciaBajo = new Tone.Sequence(
            (time, nota) => {
                if (nota !== 'r') {
                    sintetizadorBajo.triggerAttackRelease(nota, '4n', time);
                }
            },
            patronBajo,
            '4n'
        );
        secuenciaBajo.start(0);
    }

    /**
     * Genera un patrón melódico aleatorio pero musical
     * usando solo notas de la escala pentatónica.
     */
    function _generarPatronMelodia() {
        const patron = [];
        for (let i = 0; i < 16; i++) {
            // 30% de probabilidad de silencio para dar aire a la melodía
            if (Math.random() < 0.3) {
                patron.push('r');
            } else {
                const escalaUsar = Math.random() < 0.7 ? ESCALA : ESCALA_ALTA;
                patron.push(escalaUsar[Math.floor(Math.random() * escalaUsar.length)]);
            }
        }
        return patron;
    }

    function esToneDisponible() {
        if (typeof Tone === 'undefined') {
            console.warn('MusicaService: Tone.js no está cargado.');
            return false;
        }
        return true;
    }

    // ── Inicialización automática ─────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        // Leer preferencia del usuario
        const pref = localStorage.getItem('matiguara_musica');
        if (pref === '0') CONFIG.habilitado = false;
    });

    // ── API pública ───────────────────────────────────────────────────────
    return {
        iniciar,
        detener,
        pausar,
        reanudar,
        acierto,
        error,
        nivelSubir,
        logro,
        countdownTension,
        restaurarTempo,
        alternar,
        estaActiva,
    };

})();

window.MusicaService = MusicaService;
