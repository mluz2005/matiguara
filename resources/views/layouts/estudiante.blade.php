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
        /* ── TEMA INFANTIL (2026-10-11) ─────────────────────────────────────
           Fondo de cielo con un patrón MUY suave de frutas, estrellas y
           círculos de colores. El patrón es de baja intensidad para que el
           texto oscuro (#1B3361) conserve siempre un contraste alto (> 7:1). */
        body {
            font-size: 1.05rem;
            background-color: #CDEEFF;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='240' height='240'%3E%3Cg font-size='30' opacity='.2'%3E%3Ctext x='18' y='48'%3E%E2%AD%90%3C/text%3E%3Ctext x='150' y='40'%3E%F0%9F%8D%8E%3C/text%3E%3Ctext x='84' y='118' font-size='26'%3E%F0%9F%8D%8C%3C/text%3E%3Ctext x='190' y='132'%3E%F0%9F%8D%8A%3C/text%3E%3Ctext x='24' y='176'%3E%F0%9F%8D%87%3C/text%3E%3Ctext x='120' y='214'%3E%E2%AD%90%3C/text%3E%3Ctext x='196' y='224' font-size='24'%3E%F0%9F%8D%93%3C/text%3E%3C/g%3E%3Cg opacity='.28'%3E%3Ccircle cx='70' cy='40' r='7' fill='%23FFD54F'/%3E%3Ccircle cx='214' cy='84' r='6' fill='%23FF8A80'/%3E%3Ccircle cx='52' cy='132' r='6' fill='%2381C784'/%3E%3Ccircle cx='150' cy='170' r='7' fill='%2364B5F6'/%3E%3Ccircle cx='96' cy='224' r='6' fill='%23BA68C8'/%3E%3C/g%3E%3C/svg%3E"),
                              linear-gradient(180deg, #BFE6FF 0%, #E3F5FF 45%, #FFF6D6 100%);
            background-attachment: scroll, fixed;
            min-height: 100vh;
        }
        /* Adornos flotantes en los costados (solo en pantallas anchas: en el
           celular taparían el contenido). No se leen en voz alta ni se mueven
           si el sistema pide "menos movimiento". */
        .decoracion-lateral { display: none; }
        @media (min-width: 768px) {
            .decoracion-lateral { display: block; position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
            .decoracion-lateral span { position: absolute; font-size: 2.6rem; animation: flotaLateral 6s ease-in-out infinite; }
            .decoracion-lateral .l1 { top: 14%; left: 4%; }
            .decoracion-lateral .l2 { top: 42%; left: 9%; animation-delay: 1.2s; font-size: 2.2rem; }
            .decoracion-lateral .l3 { bottom: 12%; left: 5%; animation-delay: 2.4s; }
            .decoracion-lateral .l4 { top: 18%; right: 5%; animation-delay: .6s; font-size: 3rem; }
            .decoracion-lateral .l5 { top: 48%; right: 8%; animation-delay: 1.8s; }
            .decoracion-lateral .l6 { bottom: 10%; right: 4%; animation-delay: 3s; font-size: 3rem; }
        }
        @keyframes flotaLateral { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @media (prefers-reduced-motion: reduce) { .decoracion-lateral span, .guacamayo { animation: none !important; } }
        main.container-fluid { position: relative; z-index: 1; }

        .header-estudiante {
            background: linear-gradient(90deg, #1B3361 0%, #0E6B7D 70%, #177040 100%);
            padding: 10px 16px 8px;
            border-radius: 0 0 28px 28px;
            border-bottom: 5px solid #FFC94D;
            box-shadow: 0 4px 16px rgba(27,51,97,.25);
            position: relative; z-index: 2;
        }
        /* Franja de frutas bajo el encabezado (solo adorno) */
        .franja-frutas {
            text-align: center; font-size: 1.15rem; letter-spacing: .6rem;
            padding: 6px 0 0; line-height: 1; user-select: none; position: relative; z-index: 1;
        }
        /* Tarjeta con borde grueso y "relieve" tipo juguete */
        .tarjeta-infantil {
            border: 3px solid #1B3361 !important;
            border-radius: 22px !important;
            box-shadow: 0 5px 0 #1B3361 !important;
            overflow: hidden;
        }
        /* "Sticker": título de cada pantalla */
        .titulo-sticker {
            display: inline-block;
            background: #fff;
            color: #1B3361;
            border: 3px solid #1B3361;
            border-radius: 20px;
            padding: 6px 16px;
            box-shadow: 0 4px 0 #1B3361;
            font-weight: 800;
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
            position: sticky; bottom: 0; z-index: 3;
            background: #fff; border-top: 4px solid #1B3361;
            border-radius: 24px 24px 0 0;
            padding: 8px 10px 6px; box-shadow: 0 -4px 14px rgba(27,51,97,.18);
        }
        .nav-inferior a {
            text-decoration:none; color:#1B3361; font-size:.95rem; font-weight:700;
            padding: 4px 10px; border-radius: 16px; border: 3px solid transparent;
            transition: background .15s;
        }
        .nav-inferior a.activo { background:#FFE08A; border-color:#1B3361; }
        .nav-inferior a:hover { background:#EAF6FF; }
        .nav-inferior .icono { font-size:1.6rem; display:block; margin-bottom:0; }

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

    {{-- Adornos de fondo (solo pantallas anchas) --}}
    <div class="decoracion-lateral" aria-hidden="true">
        <span class="l1">☁️</span><span class="l2">🍎</span><span class="l3">🌈</span>
        <span class="l4">⭐</span><span class="l5">🍌</span><span class="l6">🦋</span>
    </div>

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
                @php $rachaHoy = app(\App\Repositories\EstudianteRepository::class)->rachaActualMaximaEnDias($est); @endphp
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

        {{-- Franja de frutas (solo adorno) --}}
        <div class="franja-frutas" aria-hidden="true">🍎 🍌 🍊 🍉 🍇 🍓 🍍</div>
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
                <a href="{{ route('estudiante.ayuda') }}"
                   class="{{ request()->routeIs('estudiante.ayuda') ? 'activo' : '' }}">
                    <span class="icono text-center d-block">❓</span>
                    Ayuda
                </a>
            </div>
        </nav>
    @endauth

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
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
     * declaran @@section('musica_propia') y definen window.MusicaPropia.
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
