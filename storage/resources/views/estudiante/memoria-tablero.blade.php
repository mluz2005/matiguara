@extends('layouts.estudiante')

@section('titulo', 'Encuentra el par — MatiGuará')

{{-- Tiene su propia música (MP3): el layout no arranca la generada por código --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/memoria-tablero.blade.php
    =========================================================================
    Tablero completo del juego "Encuentra el par". A diferencia de los
    otros juegos (que recargan página por cada ejercicio), esta vista
    renderiza el mazo COMPLETO una sola vez y toda la interacción
    (voltear cartas, comparar, temporizador) ocurre en el navegador con
    JavaScript. El servidor solo recibe llamadas fetch() para registrar
    cada intento y para cerrar la sesión al final — evita recargas de
    página que romperían la fluidez de un juego de memoria visual.

    Variables recibidas:
      $sesion          SesionJuego activa
      $nivel           Nivel (incluye tiempo_limite_seg)
      $juego           Juego
      $mazo            array de cartas mezcladas (tarjeta_id, modo_render,
                        valor, nombre_es, nombre_gn, emoji, audio_es/gn)
      $totalPares      int
      $tiempoLimiteSeg int
      $bonusTiempoSeg  int — segundos que se suman por cada acierto

    CORRECCIÓN (Semana 8 — parche):
      1) Se agrega un "vistazo" inicial de 4 segundos: al entrar al
         tablero, todas las cartas se muestran boca arriba antes de
         empezar a jugar y antes de que arranque el cronómetro.
      2/3) Se corrige que el juego quedara "trabado" al terminar (ya sea
         completando todos los pares o agotando el tiempo): antes,
         finalizarJuego() dependía de una sola llamada al servidor sin
         límite de tiempo ni plan B — si esa llamada fallaba o tardaba,
         el niño se quedaba mirando el tablero congelado indefinidamente.
         Ahora se agrega: (a) un aviso visual inmediato apenas termina el
         juego, (b) un límite de 5s por intento con reintento automático,
         y (c) si aun así no hay respuesta, se lo lleva de vuelta a la
         lista de juegos en vez de dejarlo esperando para siempre.
    =========================================================================
--}}

@push('styles')
<style>
/* ── Header del juego de memoria ─────────────────────────────────────── */
.memoria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 14px;
}

/* ── Anillo de temporizador (reutiliza el patrón de Flash Matemático) ─── */
.timer-ring { position: relative; width: 70px; height: 70px; flex-shrink: 0; }
.timer-ring svg { transform: rotate(-90deg); }
.timer-ring .pista { fill: none; stroke: rgba(27,51,97,.15); stroke-width: 6; }
.timer-ring .progreso {
    fill: none; stroke: #177040; stroke-width: 6; stroke-linecap: round;
    stroke-dasharray: 195; stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear, stroke .4s;
}
.timer-ring .numero {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    font-size:.875rem; font-weight: 700; color: #177040;
}

/* ── Grilla de cartas — adaptativa según cantidad ────────────────────── */
.tablero-memoria {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
    gap: 10px;
    max-width: 560px;
    margin: 0 auto;
}

/* ── Carta individual — flip 3D ──────────────────────────────────────── */
.carta-memoria {
    aspect-ratio: 1;
    perspective: 800px;
    cursor: pointer;
}
.carta-memoria-inner {
    position: relative;
    width: 100%; height: 100%;
    transform-style: preserve-3d;
    transition: transform .45s cubic-bezier(.4,.2,.2,1);
    border-radius: 14px;
}
.carta-memoria.volteada .carta-memoria-inner { transform: rotateY(180deg); }

.carta-cara {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    border-radius: 14px;
    backface-visibility: hidden;
    font-size: 1.8rem;
    font-weight: 700;
    box-shadow: 0 3px 8px rgba(0,0,0,.12);
}

.carta-dorso {
    background: linear-gradient(135deg, #1B3361, #0E6B7D);
    color: #fff;
}
.carta-dorso .guacamayo-dorso { font-size: 1.6rem; }

.carta-frente {
    background: #fff;
    border: 3px solid #177040;
    transform: rotateY(180deg);
    flex-direction: column;
    gap: 2px;
}
.carta-frente .cantidad-puntos {
    font-size: .9rem;
    letter-spacing: 2px;
    color: #1B3361;
    line-height: 1.1;
    text-align: center;
    padding: 0 4px;
}

/* Estado: par encontrado (queda fija, con brillo) */
.carta-memoria.encontrada .carta-frente {
    border-color: #FAC775;
    background: #FFF9E8;
    animation: brilloPar .6s ease;
}
@keyframes brilloPar {
    0%   { box-shadow: 0 0 0 rgba(250,199,117,0); }
    50%  { box-shadow: 0 0 22px rgba(250,199,117,.9); }
    100% { box-shadow: 0 3px 8px rgba(0,0,0,.12); }
}

/* Estado: par incorrecto (shake) */
.carta-memoria.error .carta-memoria-inner { animation: shakeCarta .4s ease; }
@keyframes shakeCarta {
    0%, 100% { transform: rotateY(180deg) translateX(0); }
    25%      { transform: rotateY(180deg) translateX(-6px); }
    75%      { transform: rotateY(180deg) translateX(6px); }
}

/* ── Overlay de cambio de nivel / cierre / vistazo inicial ────────────── */
.overlay-transicion {
    position: fixed; inset: 0;
    background: rgba(27,51,97,.92);
    display: flex; align-items: center; justify-content: center;
    flex-direction: column; gap: 10px;
    color: #fff; text-align: center; z-index: 2000;
    animation: apareceOverlay .3s ease;
    padding: 20px;
}
@keyframes apareceOverlay { from { opacity: 0 } to { opacity: 1 } }
.overlay-transicion .emoji-grande { font-size: 3.5rem; }
.overlay-transicion .contador-vistazo {
    font-size: 2.2rem; font-weight: 800; color: #FAC775;
}

/* ── Botón música ─────────────────────────────────────────────────────── */
.btn-musica-juego {
    background: rgba(255,255,255,.5);
    border: 1.5px solid #1B3361;
    border-radius: 20px;
    padding: 4px 10px;
    font-size:.875rem;
    color: #1B3361;
    cursor: pointer;
}
</style>
@endpush

@section('contenido')

{{-- Mensaje de cambio de nivel (si venimos de una subida/bajada) --}}
@if(session('cambio_nivel'))
    <div class="alert rounded-4 text-center fw-bold py-2 mb-3"
         style="background:#DAE8FC;border:2px solid #1B3361;color:#1B3361">
        {{ session('cambio_nivel') }}
    </div>
@endif

{{-- ── Header: nivel, temporizador, puntaje ─────────────────────────── --}}
<div class="memoria-header">
    <div>
        <small class="text-muted d-block">
            🃏 {{ $juego->nombre_es }} · Nivel {{ $nivel->numero_nivel }}
            · {{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}
        </small>
        <div class="d-flex align-items-center gap-2 mt-1">
            <span class="badge rounded-pill" style="background:#8A5A00">
                ⭐ <span id="puntajeActual">0</span>
            </span>
            <span class="badge rounded-pill bg-success">
                ✅ <span id="paresEncontrados">0</span>/{{ $totalPares }}
            </span>
            <button type="button" class="btn-musica-juego" id="btnMusicaJuego" onclick="toggleMusicaJuego()">
                🔇 Música
            </button>
        </div>
    </div>

    <div class="timer-ring" id="timerRing">
        <svg width="70" height="70" viewBox="0 0 70 70">
            <circle class="pista"    cx="35" cy="35" r="31"/>
            <circle class="progreso" cx="35" cy="35" r="31" id="timerCircle"/>
        </svg>
        <div class="numero" id="timerNumero">{{ gmdate('i:s', $tiempoLimiteSeg) }}</div>
    </div>
</div>

{{-- Instrucción breve bilingüe --}}
<div class="text-center mb-3" style="color:#1B3361;font-size:1.05rem;line-height:1.35;font-weight:600">
    <div>{{ $juego->instrucciones_es }}</div>
    <div>{{ $juego->instrucciones_gn }}</div>
</div>

{{-- ── Tablero de cartas ─────────────────────────────────────────────── --}}
<div class="tablero-memoria" id="tableroMemoria">
    @foreach($mazo as $carta)
        <div class="carta-memoria"
             data-tarjeta-id="{{ $carta['tarjeta_id'] }}"
             data-posicion="{{ $carta['posicion'] }}">
            <div class="carta-memoria-inner">
                <div class="carta-cara carta-dorso">
                    <span class="guacamayo-dorso">🦜</span>
                </div>
                <div class="carta-cara carta-frente">
                    @if($carta['modo_render'] === 'numeral')
                        <span style="font-size:2rem">{{ $carta['valor'] }}</span>
                    @elseif($carta['modo_render'] === 'cantidad')
                        <span class="cantidad-puntos">{{ str_repeat('●', (int) $carta['valor']) ?: '·' }}</span>
                    @else
                        <span>{{ $carta['emoji'] ?? '❓' }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Audio de fondo (específico de este juego) --}}
<audio id="musicaFondoJuego" loop preload="none">
    <source src="{{ asset('audio/musica/encuentra_el_par.mp3') }}" type="audio/mpeg">
</audio>

{{--
    Efectos de sonido de acierto/error — EXCLUSIVOS de "Encuentra el par".
    A propósito NO usan AudioService (el servicio compartido con Suma de
    Frutas y Flash Matemático): así este cambio no afecta a los otros dos
    juegos. El sonido de "nivel superado" sigue siendo el de siempre
    (AudioService), no se cambió.
--}}
<audio id="sonidoAcierto" preload="auto">
    <source src="{{ asset('audio/efectos/memoria_acierto.mp3') }}" type="audio/mpeg">
</audio>
<audio id="sonidoError" preload="auto">
    <source src="{{ asset('audio/efectos/memoria_error.mp3') }}" type="audio/mpeg">
</audio>

@endsection

@push('scripts')
<script>
/**
 * LÓGICA DEL JUEGO "ENCUENTRA EL PAR"
 * =========================================================================
 * Todo el flip de cartas y el temporizador corren en el cliente para
 * mantener la fluidez. El servidor solo recibe fetch() para:
 *   - registrar cada intento de par (para el algoritmo 3/2 y estadísticas)
 *   - cerrar la sesión al completar el tablero o agotarse el tiempo
 * =========================================================================
 */

const SESION_ID          = {{ $sesion->id }};
const TOTAL_PARES        = {{ $totalPares }};
const TIEMPO_LIMITE_SEG  = {{ $tiempoLimiteSeg }};
const BONUS_TIEMPO_SEG   = {{ $bonusTiempoSeg }};
const CSRF_TOKEN         = document.querySelector('meta[name="csrf-token"]').content;
const URL_LISTA_JUEGOS   = "{{ route('estudiante.juegos') }}";
const NIVEL_NUMERO       = {{ $nivel->numero_nivel }};

const DURACION_MENSAJE_MS = 1500; // el aviso "Memorizá bien" se ve, breve, y desaparece del todo

// Tiempo para ver las cartas solas: empieza en 10s en el Nivel 1 y baja
// 0.5s por cada nivel (Nivel 2 = 9.5s, Nivel 3 = 9s, ...), porque en los
// niveles más difíciles se espera que el estudiante ya memorice más rápido.
// Nunca baja de 2s, para que siempre quede tiempo real de mirar el tablero.
const DURACION_CARTAS_MS = Math.max(2000, 10000 - (NIVEL_NUMERO - 1) * 500);

let tiempoRestante     = TIEMPO_LIMITE_SEG;
let paresEncontrados   = 0;
let puntajeTotal       = 0;
let cartasVolteadas    = [];   // máx 2 elementos: {el, tarjetaId, tiempoInicio}
let bloqueado          = false; // evita clics durante la comparación
let timerInterval      = null;
let juegoTerminado     = false;
let vistazoActivo      = true;  // true mientras se muestran las cartas al inicio
let finalizandoEnCurso = false; // evita disparar finalizarJuego() dos veces
let desenlaceDecidido  = false; // evita que "tablero completo" y "cambio de nivel" se disparen a la vez

// Marca que ya se decidió cómo termina esta sesión (por tablero completo,
// tiempo agotado, o cambio de nivel). Devuelve true solo la primera vez
// que se llama, para que si dos caminos "quieren" cerrar la sesión casi
// al mismo tiempo, solo uno de ellos actúe.
function marcarDesenlace() {
    if (desenlaceDecidido) return false;
    desenlaceDecidido = true;
    return true;
}

const circunferencia = 2 * Math.PI * 31;
document.getElementById('timerCircle').style.strokeDasharray = circunferencia;

// Reproduce uno de los sonidos propios de este juego (acierto/error/nivel
// superado), reiniciándolo desde el principio por si se dispara varias
// veces seguidas (por ejemplo, dos errores rápidos uno detrás del otro).
function reproducirSonidoPropio(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.currentTime = 0;
    el.play().catch(() => {}); // el navegador puede bloquear audio antes del primer clic del usuario
}

// ── Overlay genérico (vistazo inicial, transición de nivel, cierre) ─────
function mostrarOverlay(id, html) {
    let overlay = document.getElementById(id);
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = id;
        overlay.className = 'overlay-transicion';
        document.body.appendChild(overlay);
    }
    overlay.innerHTML = html;
    return overlay;
}

function quitarOverlay(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.remove();
}

// ── Vistazo inicial: muestra todas las cartas boca arriba unos segundos ──
// Se hace en DOS etapas para que el niño realmente llegue a ver las
// cartas (antes, el aviso tapaba el tablero durante todo el vistazo):
//   Etapa 1: aparece el aviso "Memorizá bien las cartas" tapando la
//            pantalla, breve (1.5s), solo para dar la instrucción.
//   Etapa 2: el aviso desaparece POR COMPLETO — el tablero queda a la
//            vista, sin nada encima, con las cartas boca arriba, el
//            tiempo suficiente para memorizarlas de verdad.
//   Luego:   las cartas se dan vuelta y arranca el cronómetro.
function iniciarVistazo() {
    document.querySelectorAll('.carta-memoria').forEach(c => c.classList.add('volteada'));

    // Etapa 1: aviso breve
    mostrarOverlay('overlayVistazo', `
        <div class="emoji-grande">🧠</div>
        <h3>¡Memorizá bien las cartas!</h3>
        <p style="font-size:1.05rem;font-style:italic">Jaikuaapaite umi kuatia...</p>
    `);

    setTimeout(() => {
        // Etapa 2: se quita el aviso del todo — el tablero queda visible y libre
        quitarOverlay('overlayVistazo');

        setTimeout(() => {
            document.querySelectorAll('.carta-memoria').forEach(c => c.classList.remove('volteada'));
            vistazoActivo = false;
            iniciarTemporizador();
        }, DURACION_CARTAS_MS);

    }, DURACION_MENSAJE_MS);
}

// ── Temporizador principal ──────────────────────────────────────────────
function iniciarTemporizador() {
    timerInterval = setInterval(() => {
        tiempoRestante--;
        actualizarDisplayTiempo();

        if (tiempoRestante <= 0) {
            clearInterval(timerInterval);
            if (marcarDesenlace()) finalizarJuego(false); // se acabó el tiempo
        }
    }, 1000);
}

function actualizarDisplayTiempo() {
    const min = Math.floor(Math.max(0, tiempoRestante) / 60);
    const seg = Math.max(0, tiempoRestante) % 60;
    document.getElementById('timerNumero').textContent =
        String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0');

    const ratio = Math.max(0, tiempoRestante) / TIEMPO_LIMITE_SEG;
    document.getElementById('timerCircle').style.strokeDashoffset = circunferencia * (1 - ratio);

    const circle = document.getElementById('timerCircle');
    if (ratio <= 0.2)      circle.style.stroke = '#E24B4A';
    else if (ratio <= 0.4) circle.style.stroke = '#EF9F27';
    else                    circle.style.stroke = '#177040';
}

function agregarBonusTiempo() {
    tiempoRestante += BONUS_TIEMPO_SEG;
    actualizarDisplayTiempo();
}

// ── Manejo de clic en carta ─────────────────────────────────────────────
document.querySelectorAll('.carta-memoria').forEach(carta => {
    carta.addEventListener('click', () => manejarClicCarta(carta));
});

function manejarClicCarta(carta) {
    if (vistazoActivo || bloqueado || juegoTerminado) return;
    if (carta.classList.contains('volteada') || carta.classList.contains('encontrada')) return;
    if (cartasVolteadas.length >= 2) return;

    // La música de fondo arranca sola apenas el estudiante hace su primera
    // jugada real (no antes, para respetar la restricción de los
    // navegadores de no reproducir sonido sin una acción del usuario).
    intentarIniciarMusicaAuto();

    carta.classList.add('volteada');
    AudioService.reproducirFeedback('clic');

    const tarjetaId = parseInt(carta.dataset.tarjetaId);
    cartasVolteadas.push({ el: carta, tarjetaId, tiempoInicio: Date.now() });

    if (cartasVolteadas.length === 2) {
        bloqueado = true;
        setTimeout(compararCartas, 550); // esperar a que termine la animación de flip
    }
}

function compararCartas() {
    const [a, b] = cartasVolteadas;
    const esAcierto = a.tarjetaId === b.tarjetaId;
    const tiempoMs  = Date.now() - a.tiempoInicio;

    if (esAcierto) {
        a.el.classList.add('encontrada');
        b.el.classList.add('encontrada');
        paresEncontrados++;
        document.getElementById('paresEncontrados').textContent = paresEncontrados;
        reproducirSonidoPropio('sonidoAcierto');
        agregarBonusTiempo();
    } else {
        a.el.classList.add('error');
        b.el.classList.add('error');
        reproducirSonidoPropio('sonidoError');
        setTimeout(() => {
            a.el.classList.remove('volteada', 'error');
            b.el.classList.remove('volteada', 'error');
        }, 500);
    }

    // Registrar el movimiento en el servidor (no bloquea la UI)
    registrarMovimiento(a.tarjetaId, b.tarjetaId, tiempoMs);

    cartasVolteadas = [];
    bloqueado = false;

    // ¿Se completó el tablero?
    if (paresEncontrados >= TOTAL_PARES) {
        clearInterval(timerInterval);
        setTimeout(() => {
            if (marcarDesenlace()) finalizarJuego(true);
        }, 700);
    }
}

// ── Comunicación con el servidor ────────────────────────────────────────

// Envoltorio de fetch() con límite de tiempo: si el servidor no responde
// dentro de "timeoutMs", se corta el pedido y se trata como error, en vez
// de quedar esperando indefinidamente.
async function fetchConLimite(url, opciones, timeoutMs) {
    const controlador = new AbortController();
    const timer = setTimeout(() => controlador.abort(), timeoutMs);
    try {
        const resp = await fetch(url, { ...opciones, signal: controlador.signal });
        clearTimeout(timer);
        if (!resp.ok) throw new Error('Respuesta HTTP ' + resp.status);
        return await resp.json();
    } catch (e) {
        clearTimeout(timer);
        throw e;
    }
}

async function registrarMovimiento(tarjetaAId, tarjetaBId, tiempoMs) {
    try {
        const datos = await fetchConLimite(`/estudiante/memoria/sesion/${SESION_ID}/movimiento`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                tarjeta_a_id: tarjetaAId,
                tarjeta_b_id: tarjetaBId,
                tiempo_ms: tiempoMs,
            }),
        }, 6000);

        puntajeTotal += datos.puntos || 0;
        document.getElementById('puntajeActual').textContent = puntajeTotal;

        // Cambio de nivel detectado por el algoritmo 3/2 del servidor
        // (el servidor ya no manda esto si el tablero quedó completo con
        // este mismo movimiento, pero se protege igual del lado cliente)
        if (datos.cambio_nivel && datos.redirect_url && marcarDesenlace()) {
            juegoTerminado = true;
            clearInterval(timerInterval);
            mostrarTransicion(datos.cambio_nivel, datos.redirect_url);
        }
    } catch (e) {
        console.warn('No se pudo registrar el movimiento:', e);
        // No se corta el juego por esto: el punto ya se ve reflejado en
        // pantalla localmente aunque no se haya podido guardar en el acto.
    }
}

function mostrarTransicion(tipo, redirectUrl) {
    const emoji   = tipo === 'sube' ? '🎉' : '💪';
    const mensaje = tipo === 'sube' ? '¡Subiste de nivel!' : 'Vamos a practicar un poco más';

    mostrarOverlay('overlayTransicion', `
        <div class="emoji-grande">${emoji}</div>
        <h3>${mensaje}</h3>
    `);

    setTimeout(() => { window.location.href = redirectUrl; }, 1800);
}

// Cierra el juego (por tablero completo o por tiempo agotado) de forma
// robusta: nunca deja al estudiante mirando una pantalla congelada.
//   1. Corta el cronómetro y muestra de inmediato un aviso de "guardando".
//   2. Intenta cerrar la sesión en el servidor, con un límite de 5s.
//   3. Si falla, reintenta una vez más.
//   4. Si de nuevo falla, lo lleva a la lista de juegos con un aviso —
//      en vez de dejarlo esperando para siempre.
async function finalizarJuego(completadoATiempo) {
    if (finalizandoEnCurso) return;
    finalizandoEnCurso = true;
    juegoTerminado = true;
    clearInterval(timerInterval);

    mostrarOverlay('overlayCierre', `
        <div class="emoji-grande">⏳</div>
        <h3>${completadoATiempo ? '¡Encontraste todos los pares! 🎉' : '¡Se acabó el tiempo!'}</h3>
        <p>Guardando tu resultado...</p>
    `);

    if (completadoATiempo) {
        AudioService.reproducirFeedback('nivel_superado');
    }

    const opciones = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ completado_a_tiempo: completadoATiempo }),
    };
    const url = `/estudiante/memoria/sesion/${SESION_ID}/cerrar`;

    for (let intento = 1; intento <= 2; intento++) {
        try {
            const datos = await fetchConLimite(url, opciones, 5000);
            if (datos.redirect_url) {
                mostrarResultadoNivel(datos, completadoATiempo);
                setTimeout(() => { window.location.href = datos.redirect_url; }, 2200);
                return;
            }
            break; // respuesta sin redirect_url (no debería pasar) → cae al respaldo
        } catch (e) {
            console.warn(`No se pudo cerrar la sesión (intento ${intento}):`, e);
            if (intento < 2) await new Promise(r => setTimeout(r, 500));
        }
    }

    // Respaldo: nunca dejar al niño trabado esperando indefinidamente.
    mostrarOverlay('overlayCierre', `
        <div class="emoji-grande">🙂</div>
        <h3>No pudimos guardar el resultado ahora</h3>
        <p>Pero, ¡jugaste muy bien! Volviendo a los juegos...</p>
    `);
    setTimeout(() => { window.location.href = URL_LISTA_JUEGOS; }, 2500);
}

// Mensaje de cierre de partida — SIEMPRE felicita/anima al estudiante,
// y si todavía no le toca subir o bajar de nivel, muestra el progreso de
// la racha (solo del lado positivo: cuántas ganadas seguidas lleva para
// subir). No se muestra un "contador de fallos" hacia bajar de nivel,
// para no generar presión — la idea es motivar, no advertir.
function mostrarResultadoNivel(datos, completadoATiempo) {
    let emoji, titulo, detalle = '';

    if (datos.cambio_nivel === 'sube') {
        emoji  = '🎉';
        titulo = '¡Subiste de nivel!';
    } else if (datos.cambio_nivel === 'baja') {
        emoji  = '💪';
        titulo = 'Vamos a practicar un poco más';
    } else if (completadoATiempo) {
        emoji   = '⭐';
        titulo  = '¡Muy bien, lo lograste!';
        detalle = `<p>Vas ${datos.racha_consecutivas} de ${datos.racha_meta} partidas seguidas para subir de nivel.</p>`;
    } else {
        emoji  = '💪';
        titulo = '¡Casi! Seguí practicando.';
    }

    mostrarOverlay('overlayCierre', `
        <div class="emoji-grande">${emoji}</div>
        <h3>${titulo}</h3>
        ${detalle}
    `);
}

// ── Música de fondo (específica de este juego) ──────────────────────────
let musicaActiva = false;
const musicaEl = document.getElementById('musicaFondoJuego');
musicaEl.volume = 0.35; // volumen bajo — no compite con la voz del enunciado

function toggleMusicaJuego() {
    const btn = document.getElementById('btnMusicaJuego');
    if (musicaActiva) {
        musicaEl.pause();
        btn.textContent = '🔇 Música';
        musicaActiva = false;
    } else {
        musicaEl.play().catch(() => console.warn('No se pudo iniciar la música'));
        btn.textContent = '🎵 Música';
        musicaActiva = true;
    }
    musicaAutoIntentada = true; // si la apagó a mano, no forzar que arranque sola después
    try { localStorage.setItem('matiguara_musica', musicaActiva ? '1' : '0'); } catch (e) {}
    if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(musicaActiva);
    return musicaActiva;
}

// El botón 🎵 del encabezado controla esta misma música
window.MusicaPropia = { alternar: toggleMusicaJuego };

// La música arranca sola en cuanto el estudiante hace su primera jugada
// (ver manejarClicCarta), sin que tenga que buscar el botón. Si el
// navegador la bloquea igual (puede pasar), el botón sigue disponible
// para prenderla con un toque.
let musicaAutoIntentada = false;
function intentarIniciarMusicaAuto() {
    if (musicaAutoIntentada) return;
    musicaAutoIntentada = true;

    // Si el niño la silenció antes (botón 🔇), se respeta.
    try { if (localStorage.getItem('matiguara_musica') === '0') return; } catch (e) {}

    musicaEl.play().then(() => {
        musicaActiva = true;
        const btn = document.getElementById('btnMusicaJuego');
        if (btn) btn.textContent = '🎵 Música';
        if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(true);
    }).catch(() => {
        // Bloqueada por el navegador — el botón sigue funcionando a mano.
    });
}

// ── Inicio ───────────────────────────────────────────────────────────────
actualizarDisplayTiempo();
iniciarVistazo();
</script>
@endpush
