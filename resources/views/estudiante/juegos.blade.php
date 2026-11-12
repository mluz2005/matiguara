@extends('layouts.estudiante')

@section('titulo', '¡A Jugar!')

{{-- Esta pantalla tiene su propia música (MP3): el layout no arranca la generada por código --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/juegos-v2.blade.php  (REDISEÑO VISUAL — menú de los tres juegos)
    =========================================================================
    Reemplaza a juegos.blade.php. Mismas variables ($juegos, $matricula,
    $rachas, $nivelesAbiertos), mismas rutas, misma lógica de niveles
    abiertos/bloqueados, mismo botón "¿Cómo se juega?", mismo aviso de
    candado y misma música. Cambios visuales:
      · íconos propios: Suma 🍎, Flash ⚡, Encuentra el par 🃏 (antes Suma y Flash eran ➕)
      · cada juego con su mundo de color; "Encuentra el par" con mapa de mundos
      · los tres lado a lado en pantallas anchas
    =========================================================================
    MENÚ v3 (12/11/2026): la pantalla ya no muestra todos los niveles juntos.
      · Al entrar: solo una tarjeta grande por juego (ícono, nombre en castellano
        y guaraní, racha) → pantalla despejada.
      · Al tocar un juego se abre SU vista (misma página, sin recargar) con la
        ayuda '¿Cómo se juega?' y los niveles; botón grande '← Volver'.
      · El botón Atrás del celular cierra la vista (history.pushState).
      · Al volver de jugar un nivel se reabre la vista de ese mismo juego.
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/juegos-v2.css') }}?v=2">
<link rel="stylesheet" href="{{ asset('css/laberinto-v2.css') }}?v=1">
@endpush

@section('contenido')

{{-- El aviso de 'cambio_nivel' (subiste / nivel cerrado) lo muestra el layout. --}}

<div class="mv2-menu">
@if($matricula)
    <div class="grado">
        <b><i class="bi bi-mortarboard-fill me-1"></i> {{ $matricula->grado }}° Grado</b>
        <small>{{ $matricula->institucion->nombre ?? '' }}</small>
    </div>
@endif

@if($juegos->isEmpty())
    <div class="text-center py-5">
        <div style="font-size:4rem">😴</div>
        <h4 class="fw-bold mt-3" style="color:#1B3361">
            Tu maestra todavía no habilitó juegos.
        </h4>
        <p class="text-muted">Preguntale a tu maestra.</p>
    </div>
@else

    @php
        $mundosMenu = [
            1 => ['ico' => '🌴', 'gn' => "Ka'aguy", 'es' => 'Selva'],
            2 => ['ico' => '🌊', 'gn' => 'Ysyry',   'es' => 'Río'],
            3 => ['ico' => '🌻', 'gn' => 'Ñu',      'es' => 'Campo'],
            4 => ['ico' => '🌈', 'gn' => 'Yvága',   'es' => 'Cielo'],
            5 => ['ico' => '🕸️', 'gn' => 'Ñandutí', 'es' => 'Encaje'],
        ];
    @endphp

    {{-- Datos de cada juego (se calculan una vez y se usan en la tarjeta y en su vista) --}}
    @php
        $grado = $matricula?->grado ?? 1;
        $datosJuegos = $juegos->map(function ($juego) use ($rachas, $grado) {
            $tipoMec     = $juego->tipoMecanica?->tipo ?? '';
            $esMemoria   = $tipoMec === 'memoria';
            $esFlashMenu = $juego->nombre_es === 'Flash Matemático';
            $esLaberinto = $tipoMec === 'laberinto';
            return (object) [
                'juego'       => $juego,
                'racha'       => ($rachas[$juego->id] ?? null)?->racha_actual_dias ?? 0,
                'esMemoria'   => $esMemoria,
                'esFlash'     => $esFlashMenu,
                'esLaberinto' => $esLaberinto,
                // Cada juego tiene su propio mundo de color
                'mundo'       => $esMemoria ? 'mv2-w5' : ($esLaberinto ? 'mv2-wL' : ($esFlashMenu ? 'mv2-wF' : 'mv2-wS')),
                'icono'       => $esFlashMenu ? '⚡' : match($tipoMec) {
                    'suma' => '🍎', 'resta' => '➖', 'multiplicacion' => '✖️', 'division' => '➗',
                    'memoria' => '🃏', 'laberinto' => '🏫', 'mixto' => '🔢', default => '🎯',
                },
                'niveles'     => $juego->nivelesPorGrado($grado)->orderBy('numero_nivel')->get(),
            ];
        });
    @endphp

    {{-- ══ VISTA 1: una tarjeta grande por juego ══════════════════════════ --}}
    <div id="vistaInicio">
        <h4 class="titulo-juegos">🎮 ¿A qué querés jugar hoy?</h4>

        <div class="jgrid">
            @foreach($datosJuegos as $d)
                <button type="button"
                        class="jcard {{ $d->mundo }}"
                        data-abrir="juego-{{ $d->juego->id }}"
                        aria-controls="juego-{{ $d->juego->id }}"
                        aria-expanded="false">
                    <span class="jcard-ico" aria-hidden="true">{{ $d->icono }}</span>
                    <span class="jcard-es">{{ $d->juego->nombre_es }}</span>
                    <span class="jcard-gn">{{ $d->juego->nombre_gn }}</span>
                    @if($d->racha > 0)
                        <span class="jcard-racha">🔥 {{ $d->racha }} día{{ $d->racha > 1 ? 's' : '' }}</span>
                    @endif
                    <span class="jcard-jugar" aria-hidden="true">▶ ¡Jugar!</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══ VISTA 2: los niveles de cada juego (una a la vez) ══════════════ --}}
    @foreach($datosJuegos as $d)
        @php
            $juego    = $d->juego;
            $niveles  = $d->niveles;
            $compacto = $niveles->count() > 6;   // Flash (10 niveles): cinco columnas
        @endphp

        <section id="juego-{{ $juego->id }}" class="jvista" data-vista hidden
                 aria-labelledby="jt-{{ $juego->id }}" tabindex="-1">

            <div class="jvista-barra">
                {{-- Guaraní: borrador ('Ejevy' = volvé), validar con una persona hablante (RN-07) --}}
                <button type="button" class="jvolver" data-volver>
                    <span aria-hidden="true">⬅️</span>
                    <span>Volver <small>Ejevy</small></span>
                </button>
            </div>

            <article class="mv2-juego {{ $d->mundo }}{{ $d->esMemoria ? ' memoria' : '' }}">
                <div class="mv2-juego-cab">
                    <div class="mv2-juego-ico" aria-hidden="true">{{ $d->icono }}</div>
                    <h3 id="jt-{{ $juego->id }}">{{ $juego->nombre_es }}</h3>
                    <h3 class="gn">{{ $juego->nombre_gn }}</h3>

                    {{-- Ayuda del juego (late hasta que el niño la abre por primera vez) --}}
                    <a href="{{ route('estudiante.ayuda', $juego->id) }}"
                       class="btn-como-se-juega"
                       data-juego-ayuda="{{ $juego->id }}">
                        ❓ ¿Cómo se juega?
                        <small>¿Mba'éichapa ñembosarái?</small>
                    </a>

                    @if($d->racha > 0)
                        <div class="mv2-juego-racha">
                            <span>🔥 {{ $d->racha }} día{{ $d->racha > 1 ? 's' : '' }} seguido{{ $d->racha > 1 ? 's' : '' }}</span>
                        </div>
                    @endif
                </div>

                <div class="mv2-juego-cuerpo">
                @if($d->esMemoria)
                    {{-- ── MAPA DE MUNDOS (solo "Encuentra el par") ─────────────────── --}}
                    <div class="mapa">
                        @forelse($niveles->chunk(2) as $par)
                            @php $nMundo = min(5, $loop->iteration); $mw = $mundosMenu[$nMundo]; @endphp
                            <div class="mundo mv2-w{{ $nMundo }}">
                                <h5><span aria-hidden="true">{{ $mw['ico'] }}</span> {{ $mw['gn'] }} <small>({{ $mw['es'] }})</small></h5>
                                <div class="pasos">
                                    @foreach($par as $nivel)
                                        @php
                                            $abierto = isset($nivelesAbiertos)
                                                ? in_array($nivel->id, $nivelesAbiertos, true)
                                                : (bool) $nivel->desbloqueado_por_defecto;
                                        @endphp
                                        <div class="paso">
                                            <form method="POST" action="{{ route('estudiante.memoria.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
                                                @csrf
                                                <button type="{{ $abierto ? 'submit' : 'button' }}"
                                                        class="{{ $abierto ? '' : 'cerrado' }}"
                                                        title="Nivel {{ $nivel->numero_nivel }} · Dificultad {{ $nivel->dificultad }} de 5{{ $nivel->tiempo_limite_seg ? ' · ' . $nivel->tiempo_limite_seg . ' segundos' : '' }}"
                                                        @if(!$abierto)
                                                        aria-disabled="true"
                                                        data-bloqueado="1"
                                                        data-requisito="{{ max(1, $nivel->numero_nivel - 1) }}"
                                                        @endif>
                                                    {{ $abierto ? '' : '🔒' }} {{ $nivel->numero_nivel }}
                                                    <small>{{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}</small>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small text-center mb-0">Sin niveles</p>
                        @endforelse
                    </div>
                @else
                    {{-- ── NIVELES (Suma de Frutas / Flash Matemático / El camino a la escuela) ── --}}
                    @if($niveles->isEmpty())
                        <p class="text-muted small text-center mb-0">Sin niveles</p>
                    @else
                    <div class="mv2-niveles{{ $compacto ? ' flash' : '' }}">
                        @foreach($niveles as $nivel)
                            @php
                                // Abierto = habilitado de entrada, o ya desbloqueado jugando (ver App\Support\DesbloqueoNiveles)
                                $abierto = isset($nivelesAbiertos)
                                    ? in_array($nivel->id, $nivelesAbiertos, true)
                                    : (bool) $nivel->desbloqueado_por_defecto;
                            @endphp
                            <form method="POST"
                                  action="{{ $d->esLaberinto
                                        ? route('estudiante.laberinto.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id])
                                        : route('estudiante.sesion.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
                                @csrf
                                <button type="{{ $abierto ? 'submit' : 'button' }}"
                                        class="{{ $abierto ? '' : 'cerrado' }}"
                                        @if(!$abierto)
                                        aria-disabled="true"
                                        data-bloqueado="1"
                                        data-requisito="{{ max(1, $nivel->numero_nivel - 1) }}"
                                        @endif
                                        title="Nivel {{ $nivel->numero_nivel }} · Dificultad {{ $nivel->dificultad }} de 5{{ $nivel->tiempo_mostrar_ms ? ' · ' . intval($nivel->tiempo_mostrar_ms / 1000) . ' segundos para memorizar' : '' }}">
                                    {{ $abierto ? '' : '🔒' }} {{ $nivel->numero_nivel }}
                                    <small>
                                        @if($nivel->tiempo_mostrar_ms)
                                            ⏱ {{ intval($nivel->tiempo_mostrar_ms / 1000) }} s
                                        @elseif($d->esLaberinto)
                                            {{ ['🌱','💧','🐄','🏘️'][max(0, min(3, (int) ceil($nivel->numero_nivel / 2) - 1))] }}
                                        @else
                                            {{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}
                                        @endif
                                    </small>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    @endif
                @endif
                </div>
            </article>
        </section>
    @endforeach
@endif
</div>{{-- /.mv2-menu --}}

{{-- ════ MÚSICA DE FONDO DEL MENÚ DE JUEGOS ═══════════════════════════
     Suena mientras el estudiante elige juego. Al entrar a un juego la
     página se cambia, por lo que esta música se detiene sola y pasa a
     sonar la música propia de ese juego.
     ═════════════════════════════════════════════════════════════════ --}}
{{-- Aviso al tocar un nivel con candado --}}
<div id="avisoBloqueo" role="status" aria-live="polite"
     style="display:none; position:fixed; left:50%; bottom:92px; transform:translateX(-50%);
            z-index:1050; width:calc(100% - 32px); max-width:420px; text-align:center;
            background:#1B3361; color:#fff; border:3px solid #FFC94D; border-radius:20px;
            padding:12px 16px; font-weight:700; font-size:1.1rem; line-height:1.35;
            box-shadow:0 5px 0 rgba(0,0,0,.25)">
    <div id="avisoBloqueoEs"></div>
    <div id="avisoBloqueoGn"></div>
</div>
<style>
@keyframes temblor { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }
.btn-temblor { animation: temblor .4s ease; }
.btn-como-se-juega { display:block; background:#fff; color:#1B3361; border:3px solid #1B3361; border-radius:16px;
                     padding:6px 8px; font-weight:800; font-size:.95rem; line-height:1.2; text-decoration:none;
                     min-height:44px; box-shadow:0 3px 0 #1B3361; }
.btn-como-se-juega small { display:block; font-weight:700; font-size:.875rem; }
.btn-como-se-juega:hover { background:#FFF3C4; color:#1B3361; }
@keyframes late { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
.btn-como-se-juega.nuevo { animation: late 1.4s ease-in-out infinite; background:#FFF3C4; }
@media (prefers-reduced-motion: reduce) { .btn-como-se-juega.nuevo { animation:none; } }
@media (prefers-reduced-motion: reduce) { .btn-temblor { animation: none; } }
</style>

<audio id="musicaDashboard" loop preload="none">
    <source src="{{ asset('audio/musica/dashboard_juegos.mp3') }}" type="audio/mpeg">
</audio>

@endsection

@push('scripts')
<script>
(function () {
    /* ════════════════════════════════════════════════════════════════
       1) MÚSICA DEL MENÚ — activa por defecto
          El navegador solo deja sonar después de un toque del niño, así que
          arranca en el primer toque (cualquiera). Si el navegador la rechaza,
          se reintenta en el siguiente toque. Solo queda silenciada si el niño
          la apagó con el botón 🎵 en ESTA sesión (al ingresar se reactiva).
       ════════════════════════════════════════════════════════════════ */
    const musica = document.getElementById('musicaDashboard');
    musica.volume = 0.3;

    const quiereMusica = () => {
        try { return localStorage.getItem('matiguara_musica') !== '0'; } catch (e) { return true; }
    };
    let activa = quiereMusica();
    let sonando = false;

    function arrancar() {
        if (!activa || sonando || !musica.paused) return;
        const p = musica.play();
        if (p && p.then) {
            p.then(() => { sonando = true; }).catch(() => { sonando = false; });   // se reintenta en el próximo toque
        } else { sonando = true; }
    }
    // pointerup/touchend/click: gestos completos (Chrome del celular no acepta pointerdown/touchstart)
    ['pointerup', 'touchend', 'click', 'keydown'].forEach(ev => document.addEventListener(ev, arrancar, true));

    // Botón 🎵 del encabezado
    window.MusicaPropia = {
        alternar() {
            activa = !activa;
            if (activa) { sonando = false; arrancar(); } else { musica.pause(); sonando = false; }
            return activa;
        }
    };

    // Si la pestaña queda en segundo plano, pausar; al volver, retomar.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { musica.pause(); sonando = false; }
        else if (activa && musica.currentTime > 0) { arrancar(); }
    });
    // Al salir de la pantalla (entrar a un juego) se corta.
    window.addEventListener('pagehide', () => musica.pause());

    if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(activa);

    /* ════════════════════════════════════════════════════════════════
       2) VISTAS: tarjetas de juegos  ⇄  niveles de un juego
       ════════════════════════════════════════════════════════════════ */
    const inicio  = document.getElementById('vistaInicio');
    const vistas  = Array.from(document.querySelectorAll('[data-vista]'));
    const tarjetas = Array.from(document.querySelectorAll('[data-abrir]'));
    let abiertaId = null;
    let empujamos = false;     // ¿esta vista se abrió con un toque (hay entrada en el historial)?

    function mostrar(id) {
        const v = document.getElementById(id);
        if (!v) return false;
        vistas.forEach(x => { x.hidden = (x !== v); });
        inicio.hidden = true;
        abiertaId = id;
        tarjetas.forEach(t => t.setAttribute('aria-expanded', t.dataset.abrir === id ? 'true' : 'false'));
        window.scrollTo(0, 0);
        try { v.focus({ preventScroll: true }); } catch (e) {}
        return true;
    }
    function mostrarInicio() {
        vistas.forEach(x => { x.hidden = true; });
        inicio.hidden = false;
        const previa = tarjetas.find(t => t.dataset.abrir === abiertaId);
        abiertaId = null;
        tarjetas.forEach(t => t.setAttribute('aria-expanded', 'false'));
        window.scrollTo(0, 0);
        if (previa) { try { previa.focus({ preventScroll: true }); } catch (e) {} }
    }

    tarjetas.forEach(t => t.addEventListener('click', () => {
        const id = t.dataset.abrir;
        if (mostrar(id)) {
            try { history.pushState({ panel: id }, '', '#' + id); empujamos = true; } catch (e) { empujamos = false; }
        }
    }));

    document.querySelectorAll('[data-volver]').forEach(b => b.addEventListener('click', () => {
        if (empujamos) { history.back(); }                // el popstate muestra el inicio
        else {
            mostrarInicio();
            try { history.replaceState(null, '', location.pathname + location.search); } catch (e) {}
        }
    }));

    // Botón Atrás del celular / del navegador
    window.addEventListener('popstate', e => {
        empujamos = false;
        if (e.state && e.state.panel && mostrar(e.state.panel)) { empujamos = true; }
        else { mostrarInicio(); }
    });

    // Al empezar un nivel recordamos el juego: al volver del juego se reabre su vista
    document.addEventListener('submit', e => {
        const v = e.target.closest && e.target.closest('[data-vista]');
        if (v) { try { sessionStorage.setItem('matiguara_reabrir', v.id); } catch (err) {} }
    });

    // Entrada: si la dirección trae #juego-N, o venimos de jugar, se abre esa vista
    (function () {
        let id = null;
        const h = location.hash.match(/^#(juego-\d+)$/);
        if (h) { id = h[1]; }
        else {
            try {
                const r = sessionStorage.getItem('matiguara_reabrir');
                if (r) { id = r; sessionStorage.removeItem('matiguara_reabrir'); }
            } catch (e) {}
        }
        if (id && mostrar(id)) {
            try { history.replaceState({ panel: id }, '', '#' + id); } catch (e) {}
        }
    })();

    /* ════════════════════════════════════════════════════════════════
       3) AYUDA Y NIVELES CON CANDADO (igual que antes)
       ════════════════════════════════════════════════════════════════ */
    // Ayuda de cada juego: late hasta que el niño la abre por primera vez
    document.querySelectorAll('a[data-juego-ayuda]').forEach(a => {
        const clave = 'matiguaraAyudaVista_' + a.dataset.juegoAyuda;
        let vista = false;
        try { vista = localStorage.getItem(clave) === '1'; } catch (e) {}
        if (!vista) a.classList.add('nuevo');
        a.addEventListener('click', () => { try { localStorage.setItem(clave, '1'); } catch (e) {} });
    });

    // Niveles con candado: al tocarlos, tiemblan y explican qué hacer
    // Guaraní: borrador, validar con una persona hablante.
    const aviso = document.getElementById('avisoBloqueo');
    let ocultar = null;
    document.querySelectorAll('button[data-bloqueado]').forEach(btn => {
        btn.addEventListener('click', () => {
            const n = btn.dataset.requisito;
            document.getElementById('avisoBloqueoEs').textContent = '🔒 Primero superá el Nivel ' + n;
            document.getElementById('avisoBloqueoGn').textContent = "Eñepyrũ Nivel " + n + "-pe";
            aviso.style.display = 'block';
            btn.classList.remove('btn-temblor'); void btn.offsetWidth; btn.classList.add('btn-temblor');
            clearTimeout(ocultar);
            ocultar = setTimeout(() => { aviso.style.display = 'none'; }, 3000);
        });
    });
})();
</script>
@endpush
