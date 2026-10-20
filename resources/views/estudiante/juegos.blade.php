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
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/juegos-v2.css') }}?v=1">
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

    <h4 class="titulo-juegos">🎮 ¿A qué querés jugar hoy?</h4>

    @php
        $mundosMenu = [
            1 => ['ico' => '🌴', 'gn' => "Ka'aguy", 'es' => 'Selva'],
            2 => ['ico' => '🌊', 'gn' => 'Ysyry',   'es' => 'Río'],
            3 => ['ico' => '🌻', 'gn' => 'Ñu',      'es' => 'Campo'],
            4 => ['ico' => '🌈', 'gn' => 'Yvága',   'es' => 'Cielo'],
            5 => ['ico' => '🕸️', 'gn' => 'Ñandutí', 'es' => 'Encaje'],
        ];
    @endphp

    <div class="mv2-juegos">
        @foreach($juegos as $juego)
            @php
                $racha       = $rachas[$juego->id] ?? null;
                $rachaActual = $racha?->racha_actual_dias ?? 0;
                $tipoMec     = $juego->tipoMecanica?->tipo ?? '';
                $esMemoria   = $tipoMec === 'memoria';
                $esFlashMenu = $juego->nombre_es === 'Flash Matemático';

                // Cada juego tiene su propio mundo de color
                $claseMundo = $esMemoria ? 'mv2-w5' : ($esFlashMenu ? 'mv2-wF' : 'mv2-wS');

                $iconoJuego = $esFlashMenu ? '⚡' : match($tipoMec) {
                    'suma'           => '🍎',
                    'resta'          => '➖',
                    'multiplicacion' => '✖️',
                    'division'       => '➗',
                    'memoria'        => '🃏',
                    'mixto'          => '🔢',
                    default          => '🎯',
                };

                $grado = $matricula?->grado ?? 1;
                $niveles = $juego->nivelesPorGrado($grado)->orderBy('numero_nivel')->get();
                // Con muchos niveles (Flash, 10) los botones se muestran en cinco columnas
                $compacto = $niveles->count() > 6;
            @endphp

            <article class="mv2-juego {{ $claseMundo }}{{ $esMemoria ? ' memoria' : '' }}">
                <div class="mv2-juego-cab">
                    <div class="mv2-juego-ico" aria-hidden="true">{{ $iconoJuego }}</div>
                    <h3>{{ $juego->nombre_es }}</h3>
                    <h3 class="gn">{{ $juego->nombre_gn }}</h3>

                    {{-- Ayuda del juego (late hasta que el niño la abre por primera vez) --}}
                    <a href="{{ route('estudiante.ayuda', $juego->id) }}"
                       class="btn-como-se-juega"
                       data-juego-ayuda="{{ $juego->id }}">
                        ❓ ¿Cómo se juega?
                        <small>¿Mba'éichapa ñembosarái?</small>
                    </a>

                    @if($rachaActual > 0)
                        <div class="mv2-juego-racha">
                            <span>🔥 {{ $rachaActual }} día{{ $rachaActual > 1 ? 's' : '' }} seguido{{ $rachaActual > 1 ? 's' : '' }}</span>
                        </div>
                    @endif
                </div>

                <div class="mv2-juego-cuerpo">
                @if($esMemoria)
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
                    {{-- ── NIVELES (Suma de Frutas / Flash Matemático) ──────────────── --}}
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
                                  action="{{ route('estudiante.sesion.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
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
