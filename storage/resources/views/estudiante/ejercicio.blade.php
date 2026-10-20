@extends('layouts.estudiante')

@section('titulo', 'MatiGuará — Ejercicio')

{{-- Suma de Frutas tiene su propia música (MP3): el layout no arranca la generada por código.
     Flash Matemático NO la declara, así que conserva la música generada. --}}
@if($sesion->juego->nombre_es === 'Suma de Frutas')
    @section('musica_propia', '1')
@endif

{{--
    VISTA: estudiante/ejercicio.blade.php — Semana 4
    =========================================================================
    Ejercicio bilingüe con:
      - Countdown visual para Flash Matemático (tiempo_mostrar_ms)
      - Botón de audio TTS en castellano (Web Speech API)
      - Retroalimentación bilingüe animada
      - Sonidos de feedback (acierto/error)
      - Mascota Guacamayo de apoyo emocional

    Variables:
      $sesion           SesionJuego activa
      $ejercicio        ContenidoNivel
      $nivel            Nivel (tiene tiempo_mostrar_ms)
      $opciones         Colección de opciones mezcladas
      $respondidos      int — ejercicios ya respondidos
      $totalEjercicios  int
      $porcentaje       int
      $puntaje          int
      $aciertos         int
    =========================================================================
--}}

@push('styles')
<style>
/* ── Countdown ring ───────────────────────────────────────────────── */
.countdown-ring {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 12px;
}
.countdown-ring svg {
    transform: rotate(-90deg);
}
.countdown-ring .pista {
    fill: none;
    stroke: rgba(27,51,97,.18);
    stroke-width: 6;
}
.countdown-ring .progreso {
    fill: none;
    stroke: #177040;
    stroke-width: 6;
    stroke-linecap: round;
    stroke-dasharray: 220;
    stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear, stroke .5s;
}
.countdown-ring .numero {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 24px;
    font-weight: 700;
    color: #1B3361;
}

/* ── Operandos que desaparecen ───────────────────────────────────── */
.operandos-flash {
    transition: opacity .5s ease;
}
.operandos-flash.oculto {
    opacity: 0;
    pointer-events: none;
}

/* ── Retroalimentación ───────────────────────────────────────────── */
@keyframes popIn {
    0%   { transform: scale(.5); opacity: 0; }
    70%  { transform: scale(1.08); }
    100% { transform: scale(1); opacity: 1; }
}
.retro-box { animation: popIn .4s ease forwards; }

/* ── Mascota Guacamayo ───────────────────────────────────────────── */
@keyframes flotar {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-5px); }
}
.guacamayo { animation: flotar 2.5s ease-in-out infinite; }

/* ── Botón opción seleccionada ───────────────────────────────────── */
/* Al tocar una respuesta el botón cambia de color y una barrita se llena
   durante el retardo (--retardo) antes de enviarla. Si el niño se equivocó,
   puede tocar otra opción y el retardo vuelve a empezar. */
.btn-opcion {
    transition: all .15s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    min-height: 68px;
}
.btn-opcion.seleccionada {
    background: #1B3361 !important;
    border-color: #1B3361 !important;
    color: #fff !important;
    transform: scale(1.04);
    box-shadow: 0 0 0 4px rgba(27,51,97,.25);
}
.btn-opcion.seleccionada::after {
    content: '';
    position: absolute;
    left: 0; bottom: 0;
    height: 8px; width: 0;
    background: #FAC775;
    animation: llenarBarra var(--retardo, 1200ms) linear forwards;
}
.btn-opcion.enviando { pointer-events: none; }
@keyframes llenarBarra { from { width: 0; } to { width: 100%; } }
@media (prefers-reduced-motion: reduce) {
    .btn-opcion.seleccionada::after { animation: none; width: 100%; }
}
.ayuda-respuesta { color: #1B3361; font-size: .95rem; min-height: 1.4em; }

/* ── Escena de frutas (Suma de Frutas) ───────────────────────────── */
.escena-frutas { margin-bottom: 8px; }
.grupo-frutas {
    display: flex; flex-wrap: wrap; justify-content: center;
    gap: 4px 6px;
    padding: 10px 12px;
    background: rgba(255,255,255,.75);
    border: 2px solid #B8C4D8;
    border-radius: 18px;
    font-size: 2.3rem; line-height: 1.1;
}
.grupo-frutas.chico { font-size: 1.9rem; }
.signo-escena {
    font-size: 2.6rem; font-weight: 800; color: #0E6B7D; line-height: 1.1; margin: 2px 0;
}
.fruta-sacada { position: relative; display: inline-block; }
.fruta-sacada .fruta { opacity: .35; filter: grayscale(1); display: inline-block; }
.fruta-sacada::after {
    content: '✕';
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    color: #C0392B; font-weight: 900; font-size: 1.6em; line-height: 1;
}
.numero-grande {
    font-size: 3.2rem; font-weight: 700; color: #1B3361; line-height: 1.1; margin-bottom: 10px;
}

/* ── Textos bilingües: castellano y guaraní con MISMO tamaño e importancia ── */
.texto-bilingue   { font-size: 1.2rem; line-height: 1.35; font-weight: 700; }
.etiqueta-idioma  { font-size: .875rem; }

/* ── Botón música de fondo (solo Suma de Frutas) ─────────────────── */
.btn-musica-ejercicio {
    background: rgba(255,255,255,.5);
    border: 1.5px solid #1B3361;
    border-radius: 20px;
    padding: 8px 14px;
    min-height: 44px;
    font-size: .95rem;
    color: #1B3361;
    cursor: pointer;
}
</style>
@endpush

@section('contenido')

@php
    // Con la escena de frutas la pregunta cambia según la fruta ("¿Cuántas naranjas hay en total?").
    // En los demás juegos se usa el enunciado guardado, como siempre.
    $textoEs = !empty($escena) ? $escena['texto_es'] : $ejercicio->enunciado_es;
    $textoGn = !empty($escena) ? $escena['texto_gn'] : $ejercicio->enunciado_gn;
@endphp

{{-- Retroalimentación del ejercicio anterior ──────────────────────── --}}
@if(session('ultimo_resultado'))
    @php $ult = session('ultimo_resultado'); @endphp
    <div class="retro-box rounded-4 p-3 mb-3 text-center fw-bold
         {{ $ult['es_correcto'] ? '' : '' }}"
         style="background:{{ $ult['es_correcto'] ? '#D5E8D4' : '#FFF2CC' }};
                border:3px solid {{ $ult['es_correcto'] ? '#177040' : '#8A5A00' }}">

        <div style="font-size:2.2rem">{{ $ult['es_correcto'] ? '⭐' : '💪' }}</div>

        @if($ult['es_correcto'])
            <div class="texto-bilingue" style="color:#14532D">{{ $ult['mensaje_es'] ?? '¡Muy bien!' }}</div>
            <div class="texto-bilingue" style="color:#14532D">{{ $ult['mensaje_gn'] ?? '' }}</div>
            @if(($ult['puntos'] ?? 0) > 0)
                <div class="mt-1">
                    <span class="badge rounded-pill"
                          style="background:#8A5A00;color:#fff;font-size:.9rem">
                        +{{ $ult['puntos'] }} puntos
                    </span>
                </div>
            @endif
        @else
            <div class="texto-bilingue" style="color:#6B4400">{{ $ult['mensaje_es'] ?? '¡Intentalo de nuevo!' }}</div>
            <div class="texto-bilingue" style="color:#6B4400">{{ $ult['mensaje_gn'] ?? '' }}</div>
        @endif
    </div>
@endif

{{-- Barra superior de sesión ─────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <small class="text-muted" style="white-space:nowrap;font-size:.875rem">
        {{ $respondidos + 1 }}/{{ $totalEjercicios }}
    </small>
    <div class="progress flex-grow-1 rounded-3" style="height:10px">
        <div class="progress-bar rounded-3"
             style="width:{{ $porcentaje }}%;
                    background:linear-gradient(90deg,#177040,#0E6B7D)">
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill" style="background:#8A5A00">⭐ {{ $puntaje }}</span>
        <span class="badge rounded-pill bg-success">✓ {{ $aciertos }}</span>
    </div>
</div>

{{-- Juego y nivel ────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <small class="text-muted d-block">
        🎮 {{ $sesion->juego->nombre_es }}
        · Nivel {{ $nivel->numero_nivel }}
        · {{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}
    </small>

    {{-- Música de fondo — EXCLUSIVA de Suma de Frutas, no afecta a Flash Matemático --}}
    @if($sesion->juego->nombre_es === 'Suma de Frutas')
        <button type="button" class="btn-musica-ejercicio" id="btnMusicaSumaFrutas" onclick="toggleMusicaSumaFrutas()">
            🔇 Música
        </button>
        <audio id="musicaSumaFrutas" loop preload="none">
            <source src="{{ asset('audio/musica/suma_de_frutas.mp3') }}" type="audio/mpeg">
        </audio>
    @endif
</div>

{{-- ════ TARJETA DEL EJERCICIO ═══════════════════════════════════════ --}}
<div class="rounded-4 p-3 mb-3 text-center"
     style="background:linear-gradient(135deg,#EAF3DE,#DAE8FC)">

    {{-- COUNTDOWN — solo para Flash Matemático ───────────────────── --}}
    @if($nivel->tiempo_mostrar_ms)
        <div class="countdown-ring" id="countdownRing">
            <svg width="80" height="80" viewBox="0 0 80 80">
                <circle class="pista"   cx="40" cy="40" r="35"/>
                <circle class="progreso" cx="40" cy="40" r="35" id="countdownCircle"/>
            </svg>
            <div class="numero" id="countdownNumero">
                {{ intval($nivel->tiempo_mostrar_ms / 1000) }}
            </div>
        </div>
        <div class="badge rounded-pill mb-2" style="background:#1B3361;font-size:.875rem">
            ⚡ Flash Matemático — ¡Memorizá los números!
        </div>
    @endif

    {{-- Operación matemática — se oculta en Flash ────────────────── --}}
    <div class="operandos-flash" id="operandosFlash">
        @if(!empty($escena) && $escena['modo'] === 'frutas')
            {{-- ═ Suma de Frutas: se dibujan las frutas ═ --}}
            @php
                $e = $escena;
                $claseGrupo = max($e['a'], $e['b']) > 6 ? 'chico' : '';
            @endphp
            <div class="escena-frutas" role="img" aria-label="{{ $e['descripcion'] }}">
                @if($e['operacion'] === 'suma')
                    <div class="grupo-frutas {{ $claseGrupo }}">
                        @for($i = 0; $i < $e['a']; $i++)<span class="fruta">{{ $e['emoji'] }}</span>@endfor
                    </div>
                    <div class="signo-escena">+</div>
                    <div class="grupo-frutas {{ $claseGrupo }}">
                        @for($i = 0; $i < $e['b']; $i++)<span class="fruta">{{ $e['emoji'] }}</span>@endfor
                    </div>
                @else
                    <div class="grupo-frutas {{ $claseGrupo }}">
                        @for($i = 0; $i < $e['a'] - $e['b']; $i++)<span class="fruta">{{ $e['emoji'] }}</span>@endfor
                        @for($i = 0; $i < $e['b']; $i++)<span class="fruta-sacada"><span class="fruta">{{ $e['emoji'] }}</span></span>@endfor
                    </div>
                @endif
            </div>
        @elseif(!empty($escena))
            {{-- Números grandes: se muestran como números (con una fruta de apoyo) --}}
            <div class="numero-grande" role="img" aria-label="{{ $escena['descripcion'] }}">
                {{ $escena['emoji'] }}
                {{ $escena['a'] }}
                <span style="color:#0E6B7D">{{ $escena['operacion'] === 'suma' ? '+' : '−' }}</span>
                {{ $escena['b'] }}
                <span style="color:#8A5A00">?</span>
            </div>
        @elseif($ejercicio->valor_a !== null && $ejercicio->operador && $ejercicio->valor_b !== null)
            <div style="font-size:3.5rem;font-weight:500;color:#1B3361;letter-spacing:8px;margin-bottom:8px">
                {{ $ejercicio->valor_a }}
                <span style="color:#0E6B7D">{{ $ejercicio->operador }}</span>
                {{ $ejercicio->valor_b }}
                <span style="color:#595959;font-size:2.5rem"> = </span>
                <span style="color:#8A5A00">?</span>
            </div>
        @endif
    </div>

    {{-- Enunciado CASTELLANO ─────────────────────────────────────── --}}
    <div class="mb-2">
        <span class="badge rounded-pill mb-1 etiqueta-idioma" style="background:#1B3361">
            🇵🇾 Castellano
        </span>
        <p class="fw-bold mb-0 texto-bilingue" style="color:#1B3361">
            {{ $textoEs }}
        </p>
    </div>

    <hr style="border-color:#0E6B7D;opacity:.25">

    {{-- Enunciado GUARANÍ (simultáneo, siempre — RN-06) ─────────── --}}
    <div class="mb-2">
        <span class="badge rounded-pill mb-1 etiqueta-idioma" style="background:#0E6B7D">
            🟢 Guaraní
        </span>
        <p class="fw-bold mb-0 texto-bilingue" style="color:#1B3361">
            {{ $textoGn }}
        </p>
    </div>

    {{-- Botones de audio (RF-06) ────────────────────────────────── --}}
    <div class="d-flex justify-content-center gap-2 mt-2">
        <button type="button"
                class="btn rounded-pill"
                style="background:#DAE8FC;border:1.5px solid #1B3361;color:#1B3361;font-size:.95rem;min-height:44px"
                onclick="AudioService.leerEnunciado(@js($textoEs), 'es')"
                title="Escuchar en castellano">
            🔊 Castellano
        </button>

        @if($ejercicio->audio_gn && empty($escena))
            {{-- Archivo MP3 guaraní disponible (Semana 7) --}}
            <button type="button"
                    class="btn rounded-pill"
                    style="background:#D9F0F5;border:1.5px solid #0E6B7D;color:#0A5160;font-size:.95rem;min-height:44px"
                    onclick="reproducirAudioGn('{{ asset($ejercicio->audio_gn) }}')"
                    title="Escuchar en guaraní">
                🔊 Guaraní
            </button>
        @else
            {{-- Placeholder hasta tener las grabaciones --}}
            <button type="button"
                    class="btn rounded-pill"
                    style="background:#f8f9fa;border:1.5px solid #adb5bd;color:#595959;font-size:.95rem;min-height:44px"
                    title="Audio guaraní — disponible en Semana 7"
                    disabled>
                🎙 Guaraní (próx.)
            </button>
        @endif
    </div>
</div>

{{-- ════ FORMULARIO DE RESPUESTA ══════════════════════════════════════ --}}
<form method="POST"
      id="formRespuesta"
      action="{{ route('estudiante.sesion.responder', $sesion->id) }}">
    @csrf
    <input type="hidden" name="contenido_id" value="{{ $ejercicio->id }}">
    <input type="hidden" name="tiempo_ms" id="tiempoMs">
    <input type="hidden" name="respuesta" id="respuestaSeleccionada">

    <h5 class="fw-bold text-center mb-3" style="color:#1B3361;font-size:1.1rem">
        👇 Elegí la respuesta correcta:
    </h5>

    {{-- Opciones de respuesta ──────────────────────────────────────── --}}
    @if($opciones->isNotEmpty())
        <div class="row g-3 justify-content-center mb-3">
            @foreach($opciones as $opcion)
                <div class="col-6 col-sm-3">
                    <button type="button"
                            class="btn-opcion w-100 rounded-4 fw-bold text-center py-3"
                            style="font-size:1.8rem;background:#fff;
                                   border:2.5px solid #dee2e6;color:var(--bs-body-color)"
                            onclick="seleccionar('{{ $opcion }}', this)">
                        {{ $opcion }}
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Sin botón "Esa es mi respuesta": al tocar una opción se envía sola
             después de un instante (ver script), por si el niño quiere corregir. --}}
        <p class="text-center fw-semibold ayuda-respuesta mb-0" id="ayudaRespuesta" aria-live="polite">
            Tocá tu respuesta. Si te equivocaste, tocá otra.
        </p>
    @else
        {{-- Respuesta libre ──────────────────────────────────────────── --}}
        <div class="row justify-content-center mb-3">
            <div class="col-8 col-md-4">
                <input type="text"
                       class="form-control text-center fw-bold rounded-4"
                       style="font-size:1.8rem;height:70px;border:3px solid #1B3361"
                       placeholder="?"
                       id="respuestaTexto"
                       inputmode="numeric"
                       autocomplete="off"
                       oninput="document.getElementById('respuestaSeleccionada').value=this.value">
            </div>
        </div>
        <div class="d-grid">
            <button type="submit"
                    class="btn fw-bold rounded-4 py-3"
                    style="font-size:1.1rem;background:#177040;color:#fff">
                <i class="bi bi-check-circle me-1"></i> Confirmar
            </button>
        </div>
    @endif
</form>

{{-- Mascota Guacamayo de apoyo ───────────────────────────────────── --}}
<div class="text-center mt-3">
    @if(isset($mascota))
        <img src="{{ asset('images/guacamayo-alentar.svg') }}"
             alt="Guacamayo animando"
             class="guacamayo"
             style="width:60px;height:60px">
    @else
        <span class="guacamayo d-inline-block" style="font-size:2.5rem"
              title="¡Guacamayo Guará te alienta!">🦜</span>
    @endif
    <p class="text-muted small mt-1 mb-0">¡Vos podés! • ¡Ikatu nde!</p>
</div>

@endsection

@push('scripts')
{{-- Audio Service --}}
<script src="{{ asset('js/audio-service.js') }}"></script>

{{-- Tone.js como fallback de audio (CDN) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.49/Tone.js"></script>

<script>
/**
 * LÓGICA DE LA VISTA DE EJERCICIO — Semana 4
 */

// ── Registro de tiempo de respuesta ────────────────────────────────────
const tiempoInicio = Date.now();

document.getElementById('formRespuesta').addEventListener('submit', function() {
    document.getElementById('tiempoMs').value = Date.now() - tiempoInicio;
});

// ── Selección de opción múltiple con ENVÍO AUTOMÁTICO ───────────────────
// Al tocar una respuesta: el botón cambia de color, se llena una barrita y,
// pasado RETARDO_ENVIO_MS, la respuesta se envía sola. Si el niño toca otra
// opción antes de que termine, se cambia la selección y el tiempo vuelve a
// empezar (así puede corregirse sin apretar ningún botón extra).
const RETARDO_ENVIO_MS = 1200;
let temporizadorEnvio = null;
let respuestaEnviada  = false;
let tiempoAlSeleccionar = null;   // el tiempo de respuesta se mide hasta el toque, no hasta el envío

function seleccionar(valor, boton) {
    if (respuestaEnviada) return;
    clearTimeout(temporizadorEnvio);

    // Desmarcar todos
    document.querySelectorAll('.btn-opcion').forEach(b => {
        b.classList.remove('seleccionada');
        b.setAttribute('aria-pressed', 'false');
    });

    // Marcar el seleccionado (el reflow reinicia la animación de la barrita)
    void boton.offsetWidth;
    boton.style.setProperty('--retardo', RETARDO_ENVIO_MS + 'ms');
    boton.classList.add('seleccionada');
    boton.setAttribute('aria-pressed', 'true');

    document.getElementById('respuestaSeleccionada').value = valor;
    tiempoAlSeleccionar = Date.now() - tiempoInicio;

    const ayuda = document.getElementById('ayudaRespuesta');
    if (ayuda) ayuda.textContent = 'Enviando tu respuesta… ¿Te equivocaste? Tocá otra.';

    // Sonido de clic suave
    AudioService.reproducirFeedback('clic');

    temporizadorEnvio = setTimeout(enviarRespuesta, RETARDO_ENVIO_MS);
}

// Si el niño vuelve atrás con el botón del navegador, recargamos para no mostrar
// una pantalla "ya enviada".
window.addEventListener('pageshow', function(e) { if (e.persisted) location.reload(); });

function enviarRespuesta() {
    if (respuestaEnviada) return;
    const valor = document.getElementById('respuestaSeleccionada').value;
    if (valor === '') return;
    respuestaEnviada = true;
    clearTimeout(temporizadorEnvio);
    document.getElementById('tiempoMs').value = tiempoAlSeleccionar ?? (Date.now() - tiempoInicio);
    document.querySelectorAll('.btn-opcion').forEach(b => b.classList.add('enviando'));
    const ayuda = document.getElementById('ayudaRespuesta');
    if (ayuda) ayuda.textContent = '¡Enviado!';
    document.getElementById('formRespuesta').submit();
}

// ── COUNTDOWN para Flash Matemático ────────────────────────────────────
@if($nivel->tiempo_mostrar_ms)
(function() {
    const totalMs   = {{ $nivel->tiempo_mostrar_ms }};
    const totalSeg  = totalMs / 1000;
    const circum    = 2 * Math.PI * 35; // radio = 35px
    const circle    = document.getElementById('countdownCircle');
    const numero    = document.getElementById('countdownNumero');
    const operandos = document.getElementById('operandosFlash');
    let restante    = totalMs;
    const intervalo = 50; // actualizar cada 50ms para suavidad

    circle.style.strokeDasharray  = circum;
    circle.style.strokeDashoffset = 0;

    const timer = setInterval(() => {
        restante -= intervalo;

        if (restante <= 0) {
            // Tiempo agotado — ocultar los operandos
            clearInterval(timer);
            operandos.classList.add('oculto');
            circle.style.stroke = '#C0392B'; // Rojo cuando se acaba
            numero.textContent  = '⚡';

            // Sonido de "¡ya!" cuando desaparecen los números
            AudioService.reproducirFeedback('clic');
            return;
        }

        // Actualizar anillo y número
        const ratio = restante / totalMs;
        circle.style.strokeDashoffset = circum * (1 - ratio);
        numero.textContent = Math.ceil(restante / 1000);

        // Cambiar color según tiempo restante
        if (restante <= totalMs * 0.3) {
            circle.style.stroke = '#C0392B'; // Rojo — ¡apurate!
        } else if (restante <= totalMs * 0.6) {
            circle.style.stroke = '#B35C00'; // Naranja oscuro — atención (3:1 sobre el fondo)
        }
    }, intervalo);
})();
@endif

// ── Música de fondo — SOLO Suma de Frutas ───────────────────────────────
// Esta vista se recarga por completo en cada pregunta (a diferencia de
// "Encuentra el par", que es una sola pantalla), así que una música de
// fondo común se reiniciaría desde el segundo 0 cada vez que respondés.
// Para que no se sienta cortada, guardamos en localStorage si la música
// estaba prendida y en qué segundo iba, y al cargar la próxima pregunta
// la retomamos desde ahí en vez de arrancarla de nuevo.
@if($sesion->juego->nombre_es === 'Suma de Frutas')
(function() {
    const CLAVE_ACTIVA = 'sumaFrutasMusicaActiva';
    const CLAVE_TIEMPO = 'sumaFrutasMusicaTiempo';
    const musicaEl = document.getElementById('musicaSumaFrutas');
    const btn      = document.getElementById('btnMusicaSumaFrutas');
    musicaEl.volume = 0.35; // volumen bajo — no compite con el enunciado

    // Estado inicial: si el niño la apagó/prendió a mano, se respeta; si nunca
    // eligió, arranca sola en el primer toque (igual que "Encuentra el par"),
    // salvo que haya silenciado la música desde el encabezado.
    const guardado = localStorage.getItem(CLAVE_ACTIVA);
    let musicaActiva = guardado === '1'
        || (guardado === null && localStorage.getItem('matiguara_musica') !== '0');

    function actualizarBoton() {
        btn.textContent = musicaActiva ? '🎵 Música' : '🔇 Música';
    }

    function guardarProgreso() {
        if (!musicaEl.paused) {
            localStorage.setItem(CLAVE_TIEMPO, String(musicaEl.currentTime));
        }
    }

    if (musicaActiva) {
        const tiempoGuardado = parseFloat(localStorage.getItem(CLAVE_TIEMPO) || '0');
        musicaEl.addEventListener('loadedmetadata', () => {
            if (tiempoGuardado > 0 && tiempoGuardado < musicaEl.duration) {
                musicaEl.currentTime = tiempoGuardado;
            }
        }, { once: true });
        musicaEl.play().catch(() => {
            // El navegador puede bloquear el autoplay — se reintenta en el
            // primer toque (abajo) y el botón sigue disponible.
        });
    }
    actualizarBoton();

    // El navegador solo permite sonido tras un toque: si la música debería
    // estar sonando y todavía no lo hace, la arrancamos en el primer toque.
    function arrancarEnPrimerToque() {
        if (musicaActiva && musicaEl.paused) {
            musicaEl.play().catch(() => {});
        }
    }
    document.addEventListener('click', arrancarEnPrimerToque);
    document.addEventListener('touchstart', arrancarEnPrimerToque);

    window.toggleMusicaSumaFrutas = function() {
        if (musicaActiva) {
            musicaEl.pause();
            musicaActiva = false;
        } else {
            musicaEl.play().catch(() => console.warn('No se pudo iniciar la música'));
            musicaActiva = true;
        }
        localStorage.setItem(CLAVE_ACTIVA, musicaActiva ? '1' : '0');
        localStorage.setItem('matiguara_musica', musicaActiva ? '1' : '0');
        actualizarBoton();
        if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(musicaActiva);
        return musicaActiva;
    };

    // El botón 🎵 del encabezado controla esta misma música
    window.MusicaPropia = { alternar: window.toggleMusicaSumaFrutas };
    if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(musicaActiva);

    // Guardamos el progreso periódicamente y justo antes de que la página
    // se recargue al enviar una respuesta, para retomar bien la próxima vez.
    setInterval(guardarProgreso, 1000);
    window.addEventListener('beforeunload', guardarProgreso);
    document.getElementById('formRespuesta').addEventListener('submit', guardarProgreso);
})();
@endif

// ── Audio guaraní con archivo MP3 ──────────────────────────────────────
function reproducirAudioGn(url) {
    const audio = new Audio(url);
    audio.volume = 0.85;
    audio.play().catch(e => console.warn('Error reproduciendo guaraní:', e));
}

// ── Reproducir feedback al volver de procesar respuesta ────────────────
@if(session('ultimo_resultado'))
    @if(session('ultimo_resultado.es_correcto'))
        AudioService.reproducirFeedback('acierto');
    @else
        AudioService.reproducirFeedback('error');
    @endif
@endif

// ── Teclas numéricas para responder (accesibilidad tablet con teclado) ─
document.addEventListener('keydown', function(e) {
    const enfocadoEnInput = ['INPUT','TEXTAREA'].includes(document.activeElement.tagName);
    if (enfocadoEnInput) return;

    // Teclas 1-4 seleccionan la opción correspondiente
    if (e.key >= '1' && e.key <= '4') {
        const opciones = document.querySelectorAll('.btn-opcion');
        const idx = parseInt(e.key) - 1;
        if (opciones[idx]) opciones[idx].click();
    }

    // Enter envía ya mismo la respuesta seleccionada (sin esperar el retardo)
    if (e.key === 'Enter') {
        if (document.querySelector('.btn-opcion.seleccionada')) {
            enviarRespuesta();
        }
    }
});
</script>
@endpush
