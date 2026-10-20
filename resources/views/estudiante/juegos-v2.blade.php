@extends('layouts.estudiante')

@section('titulo', '¡A Jugar!')

{{-- Esta pantalla tiene su propia música (MP3): el layout no arranca la generada por código --}}
@section('musica_propia', '1')

{{-- v2: estilos nuevos y aislados (solo se cargan en esta vista) --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
@endpush

@section('contenido')

{{--
    VISTA: estudiante/juegos.blade.php — Semana 8 (actualizada)
    =========================================================================
    Se agrega soporte para el juego "Encuentra el par" (mecánica 'memoria'),
    que usa una ruta de inicio distinta (estudiante.memoria.iniciar) porque
    su flujo es de una sola pantalla, no de ejercicio-por-ejercicio.

    El resto de los juegos (mecánica 'suma', etc.) siguen usando
    estudiante.sesion.iniciar sin ningún cambio.
    =========================================================================
--}}

{{-- El aviso de 'cambio_nivel' (subiste / nivel cerrado) lo muestra el layout. --}}

<div class="mv2-menu">
@if($matricula)
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge rounded-pill fs-6 px-3 py-2" style="background:#1B3361">
            <i class="bi bi-mortarboard-fill me-1"></i>
            {{ $matricula->grado }}° Grado
        </span>
        <small class="text-muted">{{ $matricula->institucion->nombre ?? '' }}</small>
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

    <h4 class="titulo-sticker mb-3">
        🎮 ¿A qué querés jugar hoy?
    </h4>

    <div class="row g-3">
        @foreach($juegos as $juego)
            @php
                $racha       = $rachas[$juego->id] ?? null;
                $rachaActual = $racha?->racha_actual_dias ?? 0;
                $tipoMec     = $juego->tipoMecanica?->tipo ?? '';
                $esMemoria   = $tipoMec === 'memoria';

                // Cada juego tiene su propio color alegre (texto siempre azul marino, contraste > 9:1)
                $paletas = [
                    ['#FFE0E0', '#FFB8B8', '#D9534F'],   // rosa
                    ['#FFF1C2', '#FFE08A', '#B38600'],   // amarillo
                    ['#D6F5DD', '#A9E8BA', '#2E9B5A'],   // verde
                    ['#D5ECFF', '#A9D6FF', '#3B8FD6'],   // celeste
                    ['#E8DEFF', '#CDB8FF', '#8E6BD1'],   // lila
                ];
                $pal = $paletas[$loop->index % count($paletas)];

                $iconoJuego  = match($tipoMec) {
                    'suma'           => '➕',
                    'resta'          => '➖',
                    'multiplicacion' => '✖️',
                    'division'       => '➗',
                    'memoria'        => '🃏',
                    'mixto'          => '🔢',
                    default          => '🎯',
                };
            @endphp

            <div class="{{ $esMemoria ? 'col-12' : 'col-6' }} col-md-4">
                <div class="card rounded-4 h-100 overflow-hidden"
                     style="cursor:pointer; transition:transform .2s;
                            border:4px solid #1B3361; box-shadow:0 6px 0 #1B3361;"
                     onmouseover="this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.transform='translateY(0)'">

                    <div class="p-3 text-center"
                         style="background:linear-gradient(135deg, {{ $pal[0] }}, {{ $pal[1] }});
                                border-bottom:4px dashed #1B3361;">
                        <div class="d-inline-flex align-items-center justify-content-center"
                             style="font-size:2.4rem; width:72px; height:72px; background:#fff;
                                    border:3px solid #1B3361; border-radius:50%; box-shadow:0 3px 0 #1B3361">
                            {{ $iconoJuego }}
                        </div>
                        <div class="fw-bold mt-1" style="color:#1B3361; font-size:1.05rem; line-height:1.25">
                            {{ $juego->nombre_es }}
                        </div>
                        <div class="fw-bold" style="color:#1B3361; font-size:1.05rem; line-height:1.25">
                            {{ $juego->nombre_gn }}
                        </div>

                        {{-- Ayuda del juego (late hasta que el niño la abre por primera vez) --}}
                        <a href="{{ route('estudiante.ayuda', $juego->id) }}"
                           class="btn-como-se-juega mt-2"
                           data-juego-ayuda="{{ $juego->id }}">
                            ❓ ¿Cómo se juega?
                            <small>¿Mba'éichapa ñembosarái?</small>
                        </a>
                    </div>

                    @if($rachaActual > 0)
                        <div class="px-3 pt-2 text-center">
                            <span class="badge rounded-pill" style="background:#8A5A00; font-size:.875rem">
                                🔥 {{ $rachaActual }} día{{ $rachaActual > 1 ? 's' : '' }} seguido{{ $rachaActual > 1 ? 's' : '' }}
                            </span>
                        </div>
                    @endif

                    @php
                        $grado = $matricula?->grado ?? 1;
                        $niveles = $juego->nivelesPorGrado($grado)->orderBy('numero_nivel')->get();
                    @endphp

                    @if($esMemoria)
                    {{-- ── v2: MAPA DE MUNDOS (solo "Encuentra el par") ─────────────────
                         Misma lógica de apertura/bloqueo y mismas rutas que antes; solo
                         cambia cómo se ve. Se quita el tiempo en segundos del botón
                         (queda en el title) y se muestran estrellas de dificultad. --}}
                    @php
                        $mundosMenu = [
                            1 => ['ico' => '🌴', 'gn' => "Ka'aguy", 'es' => 'Selva'],
                            2 => ['ico' => '🌊', 'gn' => 'Ysyry',   'es' => 'Río'],
                            3 => ['ico' => '🌻', 'gn' => 'Ñu',      'es' => 'Campo'],
                            4 => ['ico' => '🌈', 'gn' => 'Yvága',   'es' => 'Cielo'],
                            5 => ['ico' => '🕸️', 'gn' => 'Ñandutí', 'es' => 'Encaje'],
                        ];
                    @endphp
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
                    {{-- Si el juego tiene muchos niveles (ej.: Flash Matemático, 10), los botones
                         se muestran compactos en dos columnas para no hacer una lista larguísima. --}}
                    @php $compacto = $niveles->count() > 6; @endphp

                    <div class="card-body pt-2 pb-3">
                        @if($compacto)<div class="row g-2">@endif
                        @forelse($niveles as $nivel)
                            @if($compacto)<div class="col-6">@endif
                            <form method="POST"
                                  action="{{ $esMemoria
                                        ? route('estudiante.memoria.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id])
                                        : route('estudiante.sesion.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
                                @csrf
                                @php
                                    // Abierto = habilitado de entrada, o ya desbloqueado jugando (ver App\Support\DesbloqueoNiveles)
                                    $abierto = isset($nivelesAbiertos)
                                        ? in_array($nivel->id, $nivelesAbiertos, true)
                                        : (bool) $nivel->desbloqueado_por_defecto;
                                @endphp
                                <button type="{{ $abierto ? 'submit' : 'button' }}"
                                        @if(!$abierto)
                                        aria-disabled="true"
                                        data-bloqueado="1"
                                        data-requisito="{{ max(1, $nivel->numero_nivel - 1) }}"
                                        @endif
                                        class="btn w-100 {{ $compacto ? 'px-1 py-2' : 'mb-2' }} rounded-4 fw-bold"
                                        @if($compacto)
                                        title="Nivel {{ $nivel->numero_nivel }} · Dificultad {{ $nivel->dificultad }} de 5{{ $nivel->tiempo_mostrar_ms ? ' · ' . intval($nivel->tiempo_mostrar_ms / 1000) . ' segundos para memorizar' : '' }}"
                                        @endif
                                        style="font-size:.95rem; min-height:44px;
                                               background:{{ $abierto ? '#fff' : '#EEF1F6' }};
                                               color:#1B3361;
                                               border:3px solid {{ $abierto ? $pal[2] : '#8896B3' }};
                                               box-shadow:0 3px 0 {{ $abierto ? $pal[2] : '#8896B3' }};">
                                    @if($compacto)
                                        {{ $abierto ? '⭐' : '🔒' }} {{ $nivel->numero_nivel }}
                                        <small class="d-block" style="font-weight:normal; font-size:.875rem">
                                            @if($esMemoria && $nivel->tiempo_limite_seg)
                                                ⏱ {{ $nivel->tiempo_limite_seg }} s
                                            @elseif($nivel->tiempo_mostrar_ms)
                                                ⏱ {{ intval($nivel->tiempo_mostrar_ms / 1000) }} s
                                            @else
                                                ★ {{ $nivel->dificultad }}
                                            @endif
                                        </small>
                                    @else
                                        @if($abierto)
                                            ⭐ Nivel {{ $nivel->numero_nivel }}
                                        @else
                                            🔒 Nivel {{ $nivel->numero_nivel }}
                                        @endif
                                        <small class="d-block" style="font-weight:normal">
                                            Dificultad: {{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}
                                            @if($esMemoria && $nivel->tiempo_limite_seg)
                                                · ⏱ {{ $nivel->tiempo_limite_seg }}s
                                            @endif
                                        </small>
                                    @endif
                                </button>
                            </form>
                            @if($compacto)</div>@endif
                        @empty
                            <p class="text-muted small text-center mb-0">Sin niveles</p>
                        @endforelse
                        @if($compacto)</div>@endif
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
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
    const musica = document.getElementById('musicaDashboard');
    musica.volume = 0.3;

    const quiereMusica = () => {
        try { return localStorage.getItem('matiguara_musica') !== '0'; } catch (e) { return true; }
    };
    let activa = quiereMusica();

    // El navegador solo permite sonido después de un toque del niño:
    // arrancamos en el primer toque/clic, si no la silenció antes.
    function arrancar() {
        if (activa && musica.paused) {
            musica.play().catch(() => {});
        }
    }
    document.addEventListener('click', arrancar);
    document.addEventListener('touchstart', arrancar);

    // Botón 🎵 del encabezado
    window.MusicaPropia = {
        alternar() {
            activa = !activa;
            if (activa) musica.play().catch(() => {}); else musica.pause();
            return activa;
        }
    };

    // Si la pestaña queda en segundo plano, pausar; al volver, retomar.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) musica.pause(); else if (activa && musica.currentTime > 0) musica.play().catch(() => {});
    });
    // Al salir de la pantalla (entrar a un juego) se corta.
    window.addEventListener('pagehide', () => musica.pause());

    if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(activa);

    // ── Ayuda de cada juego: late hasta que el niño la abre por primera vez ──
    document.querySelectorAll('a[data-juego-ayuda]').forEach(a => {
        const clave = 'matiguaraAyudaVista_' + a.dataset.juegoAyuda;
        let vista = false;
        try { vista = localStorage.getItem(clave) === '1'; } catch (e) {}
        if (!vista) a.classList.add('nuevo');
        a.addEventListener('click', () => { try { localStorage.setItem(clave, '1'); } catch (e) {} });
    });

    // ── Niveles con candado: al tocarlos, tiemblan y explican qué hacer ──
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
