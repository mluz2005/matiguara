<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'MatiGuará — ¡A Jugar!')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-size: 1.05rem;
            background: linear-gradient(160deg, #e8f4fd 0%, #fef9e7 50%, #e8f5e9 100%);
            min-height: 100vh;
        }
        .header-estudiante {
            background: linear-gradient(90deg, #1B3361 0%, #0E6B7D 70%, #177040 100%);
            padding: 10px 16px 8px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 16px rgba(27,51,97,.25);
        }
        .card-juego {
            border-radius: 20px;
            border: 3px solid transparent;
            transition: all .2s ease;
            cursor: pointer;
        }
        .card-juego:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,.15); }
        .btn-opcion {
            border-radius: 16px;
            padding: 16px 10px;
            font-size: 1.6rem;
            font-weight: 500;
            min-height: 68px;
            border: 2.5px solid #dee2e6;
            background: #fff;
            transition: all .15s ease;
            cursor: pointer;
            width: 100%;
        }
        .btn-opcion:hover  { border-color: #1B3361; background: #DAE8FC; transform: scale(1.04); }
        .btn-opcion.seleccionada { background:#DAE8FC!important; border-color:#1B3361!important; color:#1B3361!important; transform:scale(1.03); }
        .retro-box { border-radius: 20px; padding: 14px; font-size: 1.1rem; text-align: center; }
        @keyframes flotar  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }
        @keyframes aparecer { from{opacity:0;transform:scale(.5)} to{opacity:1;transform:scale(1)} }
        .guacamayo { animation: flotar 2.5s ease-in-out infinite; display: inline-block; }
        .nav-inferior {
            position: sticky; bottom: 0;
            background: #fff; border-top: 1px solid #dee2e6;
            padding: 6px 0; box-shadow: 0 -2px 12px rgba(0,0,0,.08);
        }
        .nav-inferior a { text-decoration:none; color:#595959; font-size:.875rem; transition:color .15s; }
        .nav-inferior a.activo, .nav-inferior a:hover { color:#1B3361; }
        .nav-inferior .icono { font-size:1.3rem; display:block; margin-bottom:2px; }

        /* Botón de música */
        .btn-audio-ctrl {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 50%;
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.1rem; cursor: pointer;
            transition: background .15s;
        }
        .btn-audio-ctrl:hover { background: rgba(255,255,255,.25); }
    </style>

    @stack('styles')
</head>
<body>

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    @auth('estudiante')
        @php $est = Auth::guard('estudiante')->user(); @endphp
        <div class="header-estudiante d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                {{-- Mascota Guacamayo --}}
                <span class="guacamayo" style="font-size:1.8rem" title="¡Guará te alienta!">🦜</span>
                <div>
                    <div style="font-size:1rem;font-weight:500;color:#fff">MatiGuará</div>
                    <div style="font-size:.875rem;color:rgba(255,255,255,.92)">
                        ¡Hola, {{ $est->nombre }}!
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Racha --}}
                @php $rachaHoy = $est->rachas()->max('racha_actual_dias') ?? 0; @endphp
                @if($rachaHoy > 0)
                    <span style="background:rgba(250,199,117,.25);border:1px solid #FAC775;
                                 border-radius:20px;padding:3px 8px;
                                 font-size:.875rem;color:#FAC775">
                        🔥 {{ $rachaHoy }}d
                    </span>
                @endif

                {{-- Control de música --}}
                <button type="button"
                        class="btn-audio-ctrl"
                        id="btnMusica"
                        title="Activar música"
                        onclick="toggleMusica(this)">
                    🎵
                </button>

                {{-- Control de audio --}}
                <button type="button"
                        class="btn-audio-ctrl"
                        id="btnAudio"
                        title="Activar audio"
                        onclick="toggleAudio(this)">
                    🔊
                </button>

                {{-- Salir --}}
                <form method="POST" action="{{ route('estudiante.logout') }}" class="m-0">
                    @csrf
                    <button type="submit"
                            class="btn btn-sm rounded-pill py-1 px-2"
                            style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);
                                   color:#fff;font-size:.875rem"
                            onclick="return confirm('¿Querés salir del juego?')">
                        <i class="bi bi-door-open"></i> Salir
                    </button>
                </form>
            </div>
        </div>
    @endauth

    {{-- ── ALERTAS FLASH ───────────────────────────────────────────── --}}
    @if(session('bienvenida'))
        <div class="container-fluid px-3 mt-3">
            <div class="alert rounded-4 text-center fw-bold mb-0 py-2"
                 style="background:#D5E8D4;border:2px solid #177040;color:#177040;font-size:1.1rem">
                🦜 {{ session('bienvenida') }}
            </div>
        </div>
    @endif

    @if(session('cambio_nivel'))
        <div class="container-fluid px-3 mt-3">
            <div class="alert rounded-4 text-center fw-bold mb-0 py-2"
                 style="background:#DAE8FC;border:2px solid #1B3361;color:#1B3361;font-size:1.1rem">
                {{ session('cambio_nivel') }}
            </div>
        </div>
    @endif

    {{-- ── CONTENIDO ──────────────────────────────────────────────── --}}
    <main class="container-fluid px-3 py-3"
          style="max-width:600px;margin:0 auto;padding-bottom:80px!important">
        @yield('contenido')
    </main>

    {{-- ── NAV INFERIOR ────────────────────────────────────────────── --}}
    @auth('estudiante')
        <nav class="nav-inferior">
            <div class="d-flex justify-content-around">
                <a href="{{ route('estudiante.juegos') }}"
                   class="{{ request()->routeIs('estudiante.juegos') ? 'activo' : '' }}">
                    <span class="icono text-center d-block">🎮</span>
                    Juegos
                </a>
                <a href="{{ route('estudiante.progreso') }}"
                   class="{{ request()->routeIs('estudiante.progreso') ? 'activo' : '' }}">
                    <span class="icono text-center d-block">📊</span>
                    Progreso
                </a>
                <a href="{{ route('estudiante.logros') }}"
                   class="{{ request()->routeIs('estudiante.logros') ? 'activo' : '' }}">
                    <span class="icono text-center d-block">🏅</span>
                    Logros
                </a>
            </div>
        </nav>
    @endauth

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmh4qXU1WXZ7g9KGHGhxK4hRFwOt"
            crossorigin="anonymous"></script>

    {{-- Tone.js para música + efectos --}}
   <script src="{{ asset('js/Tone.js') }}"></script>

    {{-- AudioService y MusicaService --}}
    <script src="{{ asset('js/audio-service.js') }}"></script>
    <script src="{{ asset('js/musica-service.js') }}"></script>

    <script>
    /**
     * La música se inicia en el primer toque/clic del usuario.
     * Esto es obligatorio en todos los navegadores modernos —
     * el audio no puede iniciarse sin interacción del usuario.
     */
    let musicaIniciada = false;

    /**
     * Algunas pantallas (el menú de juegos, "Encuentra el par" y
     * "Suma de Frutas") tienen su PROPIA música en MP3. Esas pantallas
     * declaran @section('musica_propia') y definen window.MusicaPropia.
     * En ellas NO arrancamos la música generada por código, para que
     * nunca suenen dos músicas a la vez.
     */
    const PAGINA_CON_MUSICA_PROPIA = @hasSection('musica_propia') true @else false @endif;

    function iniciarMusicaAlTocar() {
        if (PAGINA_CON_MUSICA_PROPIA) return;
        if (!musicaIniciada && typeof MusicaService !== 'undefined') {
            const pref = localStorage.getItem('matiguara_musica');
            if (pref !== '0') {
                MusicaService.iniciar();
                musicaIniciada = true;
            }
        }
    }

    // Iniciar música en el primer toque o clic
    document.addEventListener('click',     iniciarMusicaAlTocar, { once: false });
    document.addEventListener('touchstart', iniciarMusicaAlTocar, { once: false });

    // Actualiza el ícono del botón de música del encabezado
    function actualizarBotonMusica(activa) {
        const btn = document.getElementById('btnMusica');
        if (!btn) return;
        btn.textContent = activa ? '🎵' : '🔇';
        btn.title = activa ? 'Silenciar música' : 'Activar música';
    }

    function toggleMusica(btn) {
        // Pantalla con música propia: el botón del encabezado la controla a ella.
        if (PAGINA_CON_MUSICA_PROPIA && window.MusicaPropia) {
            const activa = window.MusicaPropia.alternar();
            try { localStorage.setItem('matiguara_musica', activa ? '1' : '0'); } catch (e) {}
            actualizarBotonMusica(activa);
            return;
        }
        if (typeof MusicaService !== 'undefined') {
            const activa = MusicaService.alternar();
            actualizarBotonMusica(activa);
            if (activa) MusicaService.iniciar();
        }
    }

    function toggleAudio(btn) {
        if (typeof AudioService !== 'undefined') {
            const activo = AudioService.alternarAudio();
            btn.textContent = activo ? '🔊' : '🔕';
            btn.title = activo ? 'Silenciar audio' : 'Activar audio';
        }
    }

    // Estado inicial de los botones
    document.addEventListener('DOMContentLoaded', () => {
        const prefMusica = localStorage.getItem('matiguara_musica');
        const prefAudio  = localStorage.getItem('matiguara_audio');
        const btnM = document.getElementById('btnMusica');
        const btnA = document.getElementById('btnAudio');
        if (btnM && prefMusica === '0') { btnM.textContent = '🔇'; btnM.title = 'Activar música'; }
        if (btnA && prefAudio  === '0') { btnA.textContent = '🔕'; btnA.title = 'Activar audio'; }
    });
    </script>

    @stack('scripts')
</body>
</html>
