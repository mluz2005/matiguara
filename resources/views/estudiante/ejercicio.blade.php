@extends('layouts.estudiante')

@section('titulo', 'MatiGuará — Ejercicio')

{{-- Cada juego de esta pantalla tiene su propia música (MP3): el layout no arranca la generada por código.
     Para cambiar una música basta con reemplazar el archivo (mismo nombre) en public/audio/musica/. --}}
@php
    $nombreJuegoMusica = $sesion->juego->nombre_es;
    $musicasPorJuego = [
        'Suma de Frutas'    => ['archivo' => 'suma_de_frutas.mp3',     'clave' => 'sumaFrutas'],
        'Flash Matemático'  => ['archivo' => 'flash_matematico.mp3',   'clave' => 'flashMatematico'],
    ];
    $musicaJuego = $musicasPorJuego[$nombreJuegoMusica] ?? null;
@endphp
@if($musicaJuego)
    @section('musica_propia', '1')
@endif

{{--
    VISTA: estudiante/ejercicio-v2.blade.php  (REDISEÑO VISUAL)
    =========================================================================
    Reemplaza a ejercicio.blade.php para Suma de Frutas y Flash Matemático.
    Recibe EXACTAMENTE las mismas variables ($sesion, $ejercicio, $nivel,
    $opciones, $respondidos, $totalEjercicios, $porcentaje, $puntaje,
    $aciertos, $escena, y —en Suma de Frutas— $esReintento y $pista) y mantiene los mismos ids, formulario, campos
    ocultos, funciones JS (seleccionar, enviarRespuesta, toggleMusicaJuego…),
    teclas 1-4, envío automático y audio. Solo cambia cómo se ve.

    Estilos: public/css/memoria-v2.css (base compartida) + juegos-v2.css.
    Guaraní: los textos nuevos son los mismos que ya tenía la pantalla
    ("¡Vos podés! • ¡Ikatu nde!"); no se agregó guaraní nuevo aquí.
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/juegos-v2.css') }}?v=1">
@endpush

@section('contenido')
@php
    // Segunda oportunidad con pista (Suma de Frutas): llegan del controlador; valores por defecto por si no vienen.
    $esReintento = $esReintento ?? false;
    $pista       = $pista ?? null;
    // Con la escena de frutas la pregunta cambia según la fruta ("¿Cuántas naranjas hay en total?").
    // En los demás juegos se usa el enunciado guardado, como siempre.
    $textoEs = !empty($escena) ? $escena['texto_es'] : $ejercicio->enunciado_es;
    $textoGn = !empty($escena) ? $escena['texto_gn'] : $ejercicio->enunciado_gn;
    // FLASH MATEMÁTICO: el enunciado guardado contiene los números ("¡Flash! 7 + 2."),
    // así que NO se muestra: mientras los números están a la vista se pide
    // memorizar, y cuando desaparecen el texto cambia a la pregunta. Así el niño
    // no puede leer la cuenta en el texto (ni escucharla con el botón 🔊).
    // Guaraní: frases tomadas de los ejercicios originales; validar con una persona hablante.
    $esFlash       = (bool) $nivel->tiempo_mostrar_ms;
    $flashOcultoEs = '¿Cuánto da?';
    $flashOcultoGn = "¿Mboy'e pa?";
    if ($esFlash) {
        $textoEs = '¡Mirá! ¡Memorizalo!';
        $textoGn = '¡Ehecha! ¡Eñangarekoite!';
    }
    $mundoEj = $esFlash ? 'F' : 'S';
    $ult     = session('ultimo_resultado');
    $escenaEj = $esFlash ? ['⚡','⭐','✨','💡','🚀','⚡'] : ['🌳','🐝','🍎','🌻','🐛','🦋'];
    $nombreCorto = Auth::guard('estudiante')->user()->nombre ?? 'amigo';
@endphp

<div class="mv2 mv2-ej mv2-w{{ $mundoEj }}" id="mv2Ej">

    <div class="mv2-escena" aria-hidden="true">
        @foreach($escenaEj as $i => $adorno)<span class="e{{ $i + 1 }}">{{ $adorno }}</span>@endforeach
    </div>

    {{-- ── MatiGuará: reacciona a la respuesta anterior ──────────────────── --}}
    <header class="mv2-ej-hud">
        <div class="mv2-guara-wrap">
            <div class="mv2-guara" id="guara" data-estado="{{ $ult ? ($ult['es_correcto'] ? 'feliz' : 'uy') : 'idle' }}" title="¡Guará te alienta!">
                @include('estudiante._mv2-guara')
            </div>
            @if($ult)
                <div class="mv2-burbuja {{ $ult['es_correcto'] ? 'bien' : 'casi' }}" id="burbuja" role="status" aria-live="polite">
                    <b>{{ $ult['es_correcto'] ? '⭐' : '💪' }} {{ $ult['mensaje_es'] ?? ($ult['es_correcto'] ? '¡Muy bien!' : '¡Intentalo de nuevo!') }}</b>
                    @if(!empty($ult['mensaje_gn']))<i>{{ $ult['mensaje_gn'] }}</i>@endif
                    @if($ult['es_correcto'] && ($ult['puntos'] ?? 0) > 0)<span class="pts">+{{ $ult['puntos'] }} puntos</span>@endif
                </div>
            @else
                <div class="mv2-burbuja" id="burbuja" role="status" aria-live="polite">
                    <b>¡Vos podés, {{ $nombreCorto }}!</b>
                    <i>¡Ikatu nde!</i>
                </div>
            @endif
        </div>
        <div class="mv2-ej-stats">
            <div class="mv2-chip mv2-pegatina" title="Puntos">⭐ {{ $puntaje }}</div>
            <div class="mv2-chip mv2-pegatina" title="Aciertos">✓ {{ $aciertos }}</div>
        </div>
    </header>

    {{-- ── Juego, nivel y camino de preguntas ───────────────────────────── --}}
    <div class="mv2-ej-prog">
        <div class="mv2-ej-juego mv2-pegatina">
            {{ $esFlash ? '⚡' : '🍎' }} {{ $sesion->juego->nombre_es }} · Nivel {{ $nivel->numero_nivel }}
            <span class="dif" aria-label="Dificultad {{ $nivel->dificultad }} de 5">{{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}</span>
        </div>
        <div class="mv2-camino" role="img" aria-label="Pregunta {{ $respondidos + 1 }} de {{ $totalEjercicios }}">
            <span class="num">{{ $respondidos + 1 }}/{{ $totalEjercicios }}</span>
            <div class="pista"><div style="width:{{ $porcentaje }}%"></div></div>
        </div>
        {{-- Música de fondo — propia de cada juego (Suma de Frutas y Flash Matemático) --}}
        @if($musicaJuego)
            <button type="button" class="btn-musica-ejercicio" id="btnMusicaJuego" onclick="toggleMusicaJuego()">
                🔇 Música
            </button>
            <audio id="musicaJuego" loop preload="none">
                <source src="{{ asset('audio/musica/' . $musicaJuego['archivo']) }}" type="audio/mpeg">
            </audio>
        @endif
    </div>

    {{-- ════ LA PIZARRA: la cuenta y la pregunta ════════════════════════ --}}
    <section class="mv2-pizarra mv2-pegatina">

        {{-- COUNTDOWN — solo para Flash Matemático ─────────────────────── --}}
        @if($nivel->tiempo_mostrar_ms)
            <div class="mv2-cuenta" id="countdownRing">
                <span class="et" id="countdownEtiqueta">⚡ ¡Memorizá!</span>
                <div class="pista"><div id="countdownBarra"></div></div>
                <b class="n" id="countdownNumero">{{ intval($nivel->tiempo_mostrar_ms / 1000) }}</b>
            </div>
        @endif

        {{-- Operación matemática — se oculta en Flash ──────────────────── --}}
        <div class="mv2-zona" id="mv2Zona">
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
                            @for($i = 0; $i < $e['a']; $i++)<span class="fruta" style="--k:{{ $i }}">{{ $e['emoji'] }}</span>@endfor
                        </div>
                        <div class="signo-escena">+</div>
                        <div class="grupo-frutas {{ $claseGrupo }}">
                            @for($i = 0; $i < $e['b']; $i++)<span class="fruta" style="--k:{{ $i }}">{{ $e['emoji'] }}</span>@endfor
                        </div>
                    @else
                        <div class="grupo-frutas {{ $claseGrupo }}">
                            @for($i = 0; $i < $e['a'] - $e['b']; $i++)<span class="fruta" style="--k:{{ $i }}">{{ $e['emoji'] }}</span>@endfor
                            @for($i = 0; $i < $e['b']; $i++)<span class="fruta-sacada"><span class="fruta">{{ $e['emoji'] }}</span></span>@endfor
                        </div>
                    @endif
                    <div class="mv2-igual" aria-hidden="true">=<i>?</i></div>
                </div>
            @elseif(!empty($escena))
                {{-- Números grandes: se muestran como fichas (con una fruta de apoyo) --}}
                <div class="mv2-fichas numero-grande" role="img" aria-label="{{ $escena['descripcion'] }}">
                    <span class="mv2-ficha fruta-ico">{{ $escena['emoji'] }}</span>
                    <span class="mv2-ficha a">{{ $escena['a'] }}</span>
                    <span class="mv2-signo">{{ $escena['operacion'] === 'suma' ? '+' : '−' }}</span>
                    <span class="mv2-ficha b">{{ $escena['b'] }}</span>
                    <span class="mv2-igual" aria-hidden="true">=<i>?</i></span>
                </div>
            @elseif($ejercicio->valor_a !== null && $ejercicio->operador && $ejercicio->valor_b !== null)
                @php
                    $tresNumeros = $ejercicio->valor_c !== null && $ejercicio->operador_2;
                    $palabraOp   = fn ($o) => $o === '+' ? 'más' : 'menos';
                    $etiquetaCuenta = $ejercicio->valor_a . ' ' . $palabraOp($ejercicio->operador) . ' ' . $ejercicio->valor_b
                        . ($tresNumeros ? ' ' . $palabraOp($ejercicio->operador_2) . ' ' . $ejercicio->valor_c : '');
                @endphp
                <div class="expresion-flash" role="img" aria-label="{{ $etiquetaCuenta }}">
                    <span class="pieza num">{{ $ejercicio->valor_a }}</span>
                    <span class="pieza signo">{{ $ejercicio->operador === '+' ? '+' : '−' }}</span>
                    <span class="pieza num">{{ $ejercicio->valor_b }}</span>
                    @if($tresNumeros)
                        <span class="pieza signo">{{ $ejercicio->operador_2 === '+' ? '+' : '−' }}</span>
                        <span class="pieza num">{{ $ejercicio->valor_c }}</span>
                    @endif
                    <span class="pieza igual">=</span>
                    <span class="pieza interrogante">?</span>
                </div>
            @endif
        </div>
        @if($esFlash)<div class="mv2-oculta" aria-hidden="true">🙈</div>@endif
        </div>

        {{-- Enunciado: castellano y guaraní SIEMPRE juntos y con el mismo peso (RN-06) --}}
        <div class="mv2-texto">
            <div class="linea es">
                <span class="badge rounded-pill etiqueta-idioma">🇵🇾 Castellano</span>
                <p class="texto-bilingue" id="textoEs">{{ $textoEs }}</p>
            </div>
            <div class="linea gn">
                <span class="badge rounded-pill etiqueta-idioma">🟢 Guaraní</span>
                <p class="texto-bilingue" id="textoGn">{{ $textoGn }}</p>
            </div>
        </div>

        {{-- Segunda oportunidad: pista de estrategia (solo Suma de Frutas, tras un primer error) --}}
        @if(!empty($pista))
            <div class="mv2-pista" role="note" aria-live="polite"
                 style="margin:.7rem auto;max-width:34rem;padding:.75rem 1rem;border-radius:1rem;background:#FFF6D6;border:2px dashed #E9B824;text-align:left;line-height:1.4;">
                <b>💡 Pista · ¡Tenés otra oportunidad!</b>
                <div style="font-size:1.08rem;">{{ $pista['es'] }}</div>
                <div style="font-size:1.08rem;font-style:italic;">{{ $pista['gn'] }}</div>
                <button type="button" class="mv2-btn-audio" style="margin-top:.4rem;"
                        onclick="AudioService.leerEnunciado({{ \Illuminate\Support\Js::from($pista['es']) }}, 'es')"
                        title="Escuchar la pista">
                    🔊 Escuchar la pista
                </button>
            </div>
        @endif

        {{-- Botones de audio (RF-06) --}}
        <div class="mv2-audio">
            <button type="button" class="mv2-btn-audio"
                    onclick="AudioService.leerEnunciado(window.textoEsActual, 'es')"
                    title="Escuchar en castellano">
                🔊 Castellano
            </button>

            @if($ejercicio->audio_gn && empty($escena) && !$esFlash)
                {{-- Archivo MP3 guaraní disponible (Semana 7) --}}
                <button type="button" class="mv2-btn-audio gn"
                        onclick="reproducirAudioGn('{{ asset($ejercicio->audio_gn) }}')"
                        title="Escuchar en guaraní">
                    🔊 Guaraní
                </button>
            @else
                {{-- Placeholder hasta tener las grabaciones --}}
                <button type="button" class="mv2-btn-audio gn"
                        title="Audio guaraní — disponible en Semana 7"
                        disabled>
                    🎙 Guaraní (próx.)
                </button>
            @endif
        </div>
    </section>

    {{-- ════ FORMULARIO DE RESPUESTA ══════════════════════════════════ --}}
    <form method="POST"
          id="formRespuesta"
          action="{{ route('estudiante.sesion.responder', $sesion->id) }}">
        @csrf
        <input type="hidden" name="contenido_id" value="{{ $ejercicio->id }}">
        <input type="hidden" name="tiempo_ms" id="tiempoMs">
        <input type="hidden" name="respuesta" id="respuestaSeleccionada">

        <div class="mv2-pregunta">👇 Elegí la respuesta correcta</div>

        @if($opciones->isNotEmpty())
            <div class="mv2-opciones">
                @foreach($opciones as $opcion)
                    <button type="button"
                            class="btn-opcion"
                            aria-pressed="false"
                            onclick="seleccionar('{{ $opcion }}', this)">
                        <span class="tecla" aria-hidden="true">{{ $loop->iteration }}</span>
                        {{ $opcion }}
                    </button>
                @endforeach
            </div>

            {{-- Sin botón "Esa es mi respuesta": al tocar una opción se envía sola
                 después de un instante (ver script), por si el niño quiere corregir. --}}
            <p class="ayuda-respuesta" id="ayudaRespuesta" aria-live="polite">
                Tocá tu respuesta. Si te equivocaste, tocá otra.
            </p>
        @else
            {{-- Respuesta libre --}}
            <input type="text"
                   class="mv2-libre"
                   placeholder="?"
                   id="respuestaTexto"
                   inputmode="numeric"
                   autocomplete="off"
                   oninput="document.getElementById('respuestaSeleccionada').value=this.value">
            <button type="submit" class="mv2-btn verde">
                <i class="bi bi-check-circle me-1"></i> Confirmar
            </button>
        @endif
    </form>
</div>

@endsection

@push('scripts')
{{-- Audio Service --}}
<script src="{{ asset('js/audio-service.js') }}"></script>

{{-- Tone.js como fallback de audio (CDN) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.49/Tone.js"></script>

<script>
/**
 * LÓGICA DE LA VISTA DE EJERCICIO (v2)
 * Es la misma de la vista original; solo cambian los elementos que el
 * countdown de Flash actualiza (ahora una barra) y se suma confeti al acertar.
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

// Texto que lee el botón 🔊 (en Flash cambia cuando desaparecen los números)
window.textoEsActual = @js($textoEs);

// ── COUNTDOWN para Flash Matemático ────────────────────────────────────
@if($nivel->tiempo_mostrar_ms)
(function() {
    const totalMs   = {{ $nivel->tiempo_mostrar_ms }};
    const barra     = document.getElementById('countdownBarra');
    const numero    = document.getElementById('countdownNumero');
    const etiqueta  = document.getElementById('countdownEtiqueta');
    const caja      = document.getElementById('countdownRing');
    const operandos = document.getElementById('operandosFlash');
    const zona      = document.getElementById('mv2Zona');
    let restante    = totalMs;
    const intervalo = 50; // actualizar cada 50ms para suavidad

    const timer = setInterval(() => {
        restante -= intervalo;

        if (restante <= 0) {
            // Tiempo agotado — ocultar los operandos
            clearInterval(timer);
            operandos.classList.add('oculto');
            operandos.setAttribute('aria-hidden', 'true'); // tampoco se lee en voz alta
            zona.classList.add('listo');
            // El texto pasa de "memorizá" a la pregunta
            document.getElementById('textoEs').textContent = @js($flashOcultoEs);
            document.getElementById('textoGn').textContent = @js($flashOcultoGn);
            window.textoEsActual = @js($flashOcultoEs);
            barra.style.width = '0%';
            barra.className = 'poco';
            caja.classList.add('fin');
            etiqueta.textContent = '¡Ya!';
            numero.textContent  = '⚡';

            // Sonido de "¡ya!" cuando desaparecen los números
            AudioService.reproducirFeedback('clic');
            return;
        }

        // Actualizar barra y número
        const ratio = restante / totalMs;
        barra.style.width = (ratio * 100).toFixed(1) + '%';
        numero.textContent = Math.ceil(restante / 1000);

        // Cambiar color según tiempo restante
        barra.className = restante <= totalMs * 0.3 ? 'poco' : (restante <= totalMs * 0.6 ? 'medio' : '');
    }, intervalo);
})();
@endif

// ── Música de fondo — propia de cada juego (Suma de Frutas / Flash Matemático) ───────────────────────────────
// Esta vista se recarga por completo en cada pregunta (a diferencia de
// "Encuentra el par", que es una sola pantalla), así que una música de
// fondo común se reiniciaría desde el segundo 0 cada vez que respondés.
// Para que no se sienta cortada, guardamos en localStorage si la música
// estaba prendida y en qué segundo iba, y al cargar la próxima pregunta
// la retomamos desde ahí en vez de arrancarla de nuevo.
@if($musicaJuego)
(function() {
    // Cada juego recuerda por separado si su música estaba prendida y en qué segundo iba
    const CLAVE_ACTIVA = @js($musicaJuego['clave'] . 'MusicaActiva');
    const CLAVE_TIEMPO = @js($musicaJuego['clave'] . 'MusicaTiempo');
    const musicaEl = document.getElementById('musicaJuego');
    const btn      = document.getElementById('btnMusicaJuego');
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

    window.toggleMusicaJuego = function() {
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
    window.MusicaPropia = { alternar: window.toggleMusicaJuego };
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
        mv2Confeti();
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

// ── v2: confeti de frutas al acertar (solo adorno; respeta "menos movimiento") ──
function mv2Confeti() {
    const origen = document.getElementById('guara');
    if (!origen || !origen.animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const r = origen.getBoundingClientRect();
    const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    const f = ['🍎','🍌','🍊','🍇','🍓','⭐'];
    for (let i = 0; i < 14; i++) {
        const s = document.createElement('span');
        s.className = 'mv2-confeti';
        s.textContent = f[Math.floor(Math.random() * f.length)];
        s.style.left = cx + 'px'; s.style.top = cy + 'px';
        document.body.appendChild(s);
        const a = Math.random() * Math.PI * 2, d = 70 + Math.random() * 130;
        const dx = Math.cos(a) * d, dy = Math.sin(a) * d - 40;
        const an = s.animate([
            { transform: 'translate(-50%,-50%) scale(.3)', opacity: 1 },
            { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px)) scale(1.1)', opacity: 1, offset: .55 },
            { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + (dy + 90) + 'px)) scale(.8)', opacity: 0 }
        ], { duration: 950 + Math.random() * 450, easing: 'cubic-bezier(.2,.7,.4,1)' });
        an.onfinish = function () { s.remove(); };
    }
}

</script>
@endpush
