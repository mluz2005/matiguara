@extends('layouts.estudiante')

@section('titulo', '¡A Jugar!')

{{-- Esta pantalla tiene su propia música (MP3): el layout no arranca la generada por código --}}
@section('musica_propia', '1')

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

@if(session('cambio_nivel'))
    <div class="alert rounded-4 text-center fw-bold py-3 mb-3"
         style="background:#DAE8FC; border:3px solid #1B3361; color:#1B3361; font-size:1.2rem">
        {{ session('cambio_nivel') }}
    </div>
@endif

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

    <h4 class="fw-bold mb-3" style="color:#1B3361">
        🎮 ¿A qué querés jugar hoy?
    </h4>

    <div class="row g-3">
        @foreach($juegos as $juego)
            @php
                $racha       = $rachas[$juego->id] ?? null;
                $rachaActual = $racha?->racha_actual_dias ?? 0;
                $tipoMec     = $juego->tipoMecanica?->tipo ?? '';
                $esMemoria   = $tipoMec === 'memoria';

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

            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden"
                     style="cursor:pointer; transition:transform .2s;"
                     onmouseover="this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.transform='translateY(0)'">

                    <div class="p-3 text-center"
                         style="background:linear-gradient(135deg, #DAE8FC, #D5E8D4);">
                        <div style="font-size:3rem">{{ $iconoJuego }}</div>
                        <div class="fw-bold mt-1" style="color:#1B3361; font-size:1.05rem; line-height:1.25">
                            {{ $juego->nombre_es }}
                        </div>
                        <div class="fw-bold" style="color:#1B3361; font-size:1.05rem; line-height:1.25">
                            {{ $juego->nombre_gn }}
                        </div>
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

                    <div class="card-body pt-2 pb-3">
                        @forelse($niveles as $nivel)
                            <form method="POST"
                                  action="{{ $esMemoria
                                        ? route('estudiante.memoria.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id])
                                        : route('estudiante.sesion.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
                                @csrf
                                <button type="submit"
                                        class="btn w-100 mb-1 rounded-3 fw-bold"
                                        style="font-size:.9rem;
                                               background:{{ $nivel->desbloqueado_por_defecto ? '#D5E8D4' : '#f8f9fa' }};
                                               color:{{ $nivel->desbloqueado_por_defecto ? '#177040' : '#999' }};
                                               border:2px solid {{ $nivel->desbloqueado_por_defecto ? '#177040' : '#dee2e6' }};">
                                    @if($nivel->desbloqueado_por_defecto)
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
                                </button>
                            </form>
                        @empty
                            <p class="text-muted small text-center mb-0">Sin niveles</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif


{{-- ════ MÚSICA DE FONDO DEL MENÚ DE JUEGOS ═══════════════════════════
     Suena mientras el estudiante elige juego. Al entrar a un juego la
     página se cambia, por lo que esta música se detiene sola y pasa a
     sonar la música propia de ese juego.
     ═════════════════════════════════════════════════════════════════ --}}
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
})();
</script>
@endpush
