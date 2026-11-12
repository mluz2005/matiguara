<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head')
    <title>MatiGuarã</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Pantalla de inicio (rediseño 2026-10-11): la ven los niños, por eso
           lleva cielo, frutas y colores alegres. Los textos son siempre azul
           marino sobre fondos claros (contraste > 7:1). */
        body {
            background: linear-gradient(180deg, #8FD3FF 0%, #CDEEFF 55%, #FFF3C4 100%);
            min-height: 100vh;
            min-height: 100dvh;
            position: relative;
            overflow-x: hidden;
        }
        /* Todo el contenido va en un solo bloque centrado en vertical.
           (Antes el body era flex en fila y el enlace 'Acerca de' quedaba
           al costado del contenedor.) */
        .pantalla {
            min-height: 100vh; min-height: 100dvh;
            display: flex; flex-direction: column; justify-content: center;
        }
        .container { position: relative; z-index: 1; padding-top: 38px; padding-bottom: 28px; }
        .decoracion { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .decoracion span { position: absolute; font-size: 2.6rem; animation: flota 6s ease-in-out infinite; }
        .decoracion .d1 { top: 4%;  left: 5%;  font-size: 3.6rem; }
        .decoracion .d2 { top: 8%;  right: 7%; animation-delay: 1s; }
        .decoracion .d3 { top: 44%; left: 2%;  animation-delay: 2s; }
        .decoracion .d4 { top: 58%; right: 3%; animation-delay: 3s; }
        .decoracion .d5 { bottom: 5%; left: 7%; animation-delay: 1.5s; }
        .decoracion .d6 { bottom: 4%; right: 9%; font-size: 3.2rem; animation-delay: .5s; }
        @keyframes flota { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @media (prefers-reduced-motion: reduce) { .decoracion span { animation: none; } }

        /* Título = botón de la mascota. Al tocarlo, MatiGuarã festeja y
           aparece la burbuja con los datos institucionales. */
        .titulo-inicio {
            display: inline-flex; flex-direction: column; align-items: center;
            background: #fff; color: #1B3361; font: inherit;
            border: 4px solid #1B3361; border-radius: 28px;
            box-shadow: 0 7px 0 #1B3361;
            padding: 10px 28px 8px;
            cursor: pointer; position: relative;
            transition: transform .12s, box-shadow .12s;
        }
        .titulo-inicio:hover  { transform: translateY(-2px); box-shadow: 0 9px 0 #1B3361; }
        .titulo-inicio:active { transform: translateY(5px);  box-shadow: 0 2px 0 #1B3361; }
        .titulo-inicio:focus-visible { outline: 4px solid #1B3361; outline-offset: 5px; }
        .titulo-inicio .mascota { display: block; height: 92px; width: auto; }
        .titulo-inicio .nombre  { font-weight: 800; font-size: 2rem; line-height: 1.1; }
        .titulo-inicio.salta .mascota { animation: salta .9s ease-in-out 1; }
        @keyframes salta {
            0%   { transform: translateY(0) rotate(0); }
            25%  { transform: translateY(-16px) rotate(-6deg); }
            50%  { transform: translateY(0) rotate(5deg); }
            70%  { transform: translateY(-9px) rotate(-3deg); }
            100% { transform: translateY(0) rotate(0); }
        }
        /* Cartelito '¡Tocame para conocerme!': queda hasta que se presiona un botón de rol */
        .pista-toca {
            position: absolute; top: -17px; left: 50%;
            transform: translateX(-50%) rotate(-2deg);
            white-space: nowrap;
            background: #FFD966; color: #1B3361; font-weight: 800; font-size: .95rem;
            border: 3px solid #1B3361; border-radius: 16px; padding: 1px 12px;
            pointer-events: none;
            animation: late 1.3s ease-in-out infinite;
            transition: opacity .35s;
        }
        .pista-toca.oculta { opacity: 0; animation: none; visibility: hidden; transition: opacity .35s, visibility 0s .35s; }
        @keyframes late {
            0%,100% { transform: translateX(-50%) rotate(-2deg) scale(1); }
            50%     { transform: translateX(-50%) rotate(-2deg) scale(1.07); }
        }

        .subtitulo-inicio { color: #1B3361; font-weight: 700; }

        /* Burbuja con los datos institucionales */
        .burbuja {
            position: relative; display: inline-block; max-width: 420px; width: calc(100% - 8px);
            background: #fff; color: #1B3361;
            border: 3px solid #1B3361; border-radius: 22px;
            box-shadow: 0 5px 0 #1B3361;
            padding: 12px 18px; margin-top: 18px; font-weight: 600;
            animation: aparece .25s ease-out;
        }
        .burbuja[hidden] { display: none; }
        .burbuja::before {   /* piquito hacia la mascota */
            content: ""; position: absolute; top: -12px; left: 50%; margin-left: -10px;
            width: 18px; height: 18px; background: #fff;
            border-left: 3px solid #1B3361; border-top: 3px solid #1B3361;
            transform: rotate(45deg);
        }
        .burbuja p { margin: 0 0 4px; }
        .burbuja a { color: #1B3361; font-weight: 800; }
        @keyframes aparece { from { opacity: 0; transform: translateY(-8px) scale(.96); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            .titulo-inicio.salta .mascota, .pista-toca, .burbuja { animation: none; }
        }

        /* Celulares: tarjetas más compactas para que entren mejor en pantalla */
        @media (max-width: 575.98px) {
            .container { padding-top: 30px; padding-bottom: 14px; }
            .titulo-inicio .mascota { height: 76px; }
            .titulo-inicio .nombre  { font-size: 1.7rem; }
            .col-perfil .btn-perfil { padding: 9px 12px 10px; box-shadow: 0 6px 0 #1B3361; }
            .col-perfil .btn-perfil .emoji { font-size: 2.2rem; margin-bottom: 0; }
            .col-perfil .btn-perfil .fs-4 { font-size: 1.15rem !important; }
            .col-perfil .btn-perfil small { font-size: .9rem; }
            .col-perfil { margin-bottom: 1rem !important; }
        }

        .btn-perfil {
            border-radius: 26px;
            padding: 26px 18px;
            border: 4px solid #1B3361;
            transition: transform .12s, box-shadow .12s;
            box-shadow: 0 8px 0 #1B3361;
            font-weight: 800;
            color: #1B3361;
        }
        .btn-perfil:hover  { transform: translateY(-4px); box-shadow: 0 12px 0 #1B3361; color: #1B3361; }
        .btn-perfil:active { transform: translateY(6px);  box-shadow: 0 2px 0 #1B3361; color: #1B3361; }
        .btn-perfil:focus-visible { outline: 4px solid #1B3361; outline-offset: 4px; }
        .btn-perfil .emoji { font-size: 3.6rem; display: block; margin-bottom: 8px; line-height: 1.1; }
        .btn-perfil small { color: #1B3361; font-weight: 600; font-size: 1rem; }
        .perfil-estudiante { background: linear-gradient(135deg, #FFF1C2, #FFD966); }
        .perfil-docente    { background: linear-gradient(135deg, #D6F5DD, #A9E8BA); }
        .perfil-padre      { background: linear-gradient(135deg, #E8DEFF, #CDB8FF); }

        .aviso-sesion {
            background: #fff; color: #1B3361;
            border: 3px solid #1B3361; border-radius: 20px;
            box-shadow: 0 4px 0 #1B3361;
            padding: 10px 16px; font-weight: 600;
        }
        .btn-panel {
            --bs-btn-color:#fff; --bs-btn-bg:#177040; --bs-btn-border-color:#0F5130;
            --bs-btn-hover-color:#fff; --bs-btn-hover-bg:#1C8A4F; --bs-btn-hover-border-color:#0F5130;
            --bs-btn-active-color:#fff; --bs-btn-active-bg:#0F5130; --bs-btn-active-border-color:#0F5130;
            border-width: 3px; border-radius: 14px; font-weight: 700;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/fondo-selva.css') }}?v=1">
</head>
<body class="fondo-sin-mascota">

{{-- Adornos de fondo (solo adorno) --}}
<div class="decoracion" aria-hidden="true">
    <span class="d1">☁️</span><span class="d2">⭐</span><span class="d3">🍎</span>
    <span class="d4">🍌</span><span class="d5">🍊</span><span class="d6">🌈</span>
</div>

<main class="pantalla">
<div class="container">
    <div class="row justify-content-center text-center">
        <div class="col-12 mb-3">
            <h1 class="m-0">
                <button type="button" id="mascotaBtn" class="titulo-inicio"
                        aria-expanded="false" aria-controls="burbujaInfo"
                        aria-label="MatiGuarã, la mascota. Tocá para ver información de la aplicación"
                        data-img-quieta="{{ asset('img/guara/guara-guino.webp') }}"
                        data-img-feliz="{{ asset('img/guara/guara-festeja.webp') }}">
                    <img id="mascotaImg" class="mascota" src="{{ asset('img/guara/guara-guino.webp') }}"
                         alt="" width="86" height="92" decoding="async">
                    <span class="nombre">MatiGuarã</span>
                    <span id="pistaToca" class="pista-toca" aria-hidden="true">¡Tocame para conocerme!</span>
                </button>
            </h1>

            <div>
                <div id="burbujaInfo" class="burbuja" hidden>
                    <p>Primer Ciclo EEB</p>
                    <p>FACAT — Universidad Autónoma de Encarnación 2026</p>
                    <a href="{{ route('acerca') }}">ℹ️ Acerca de MatiGuarã</a>
                </div>
            </div>

            <p class="subtitulo-inicio fs-5 mt-3 mb-1">Matemáticas en castellano y guaraní</p>

            {{-- Si ya hay una sesión abierta, se ofrece ir directo al panel (evita volver a ingresar) --}}
            @auth
                @php
                    $u = Auth::user();
                    $panel = $u->esDocente() ? route('docente.panel')
                           : ($u->esPadre()  ? route('padre.panel')
                           : ($u->esAdmin()  ? route('admin.panel') : null));
                @endphp
                @if($panel)
                    <div class="aviso-sesion d-inline-flex align-items-center gap-3 flex-wrap justify-content-center mt-3">
                        <span>👋 Ya iniciaste sesión como <strong>{{ $u->nombre }}</strong></span>
                        <a href="{{ $panel }}" class="btn btn-panel btn-sm">Ir a mi panel</a>
                    </div>
                @endif
            @endauth
            @auth('estudiante')
                <div class="aviso-sesion d-inline-flex align-items-center gap-3 flex-wrap justify-content-center mt-3">
                    <span>👋 ¡Hola, <strong>{{ Auth::guard('estudiante')->user()->nombre }}</strong>!</span>
                    <a href="{{ route('estudiante.juegos') }}" class="btn btn-panel btn-sm">🎮 Seguir jugando</a>
                </div>
            @endauth
        </div>

        {{-- Tarjeta Estudiante --}}
        <div class="col-11 col-sm-6 col-md-4 col-lg-3 mb-4 col-perfil">
            <a href="{{ route('estudiante.login') }}"
               class="btn btn-perfil perfil-estudiante w-100 text-decoration-none d-block" data-perfil>
                <span class="emoji" aria-hidden="true">🎮</span>
                <span class="fs-4 d-block">¡Quiero jugar!</span>
                <small class="d-block mt-1">Soy estudiante</small>
            </a>
        </div>

        {{-- Tarjeta Docente/Admin --}}
        <div class="col-11 col-sm-6 col-md-4 col-lg-3 mb-4 col-perfil">
            <a href="{{ route('login') }}"
               class="btn btn-perfil perfil-docente w-100 text-decoration-none d-block" data-perfil>
                <span class="emoji" aria-hidden="true">👩‍🏫</span>
                <span class="fs-4 d-block">Soy docente</span>
                <small class="d-block mt-1">Panel de seguimiento</small>
            </a>
        </div>

        {{-- Tarjeta Padre/Tutor --}}
        <div class="col-11 col-sm-6 col-md-4 col-lg-3 mb-4 col-perfil">
            <a href="{{ route('familia.login') }}"
               class="btn btn-perfil perfil-padre w-100 text-decoration-none d-block" data-perfil>
                <span class="emoji" aria-hidden="true">👨‍👩‍👧</span>
                <span class="fs-4 d-block">Soy padre/madre</span>
                <small class="d-block mt-1">Ver progreso de mi hijo/a</small>
            </a>
        </div>
    </div>
</div>
</main>

<script>
/* La mascota es un botón: al tocarla festeja y muestra la burbuja con los datos
   institucionales. La burbuja se cierra sola a los pocos segundos. El cartelito
   '¡Tocame para conocerme!' se apaga cuando se presiona un botón de rol. */
(function () {
    var btn = document.getElementById('mascotaBtn'),
        img = document.getElementById('mascotaImg'),
        bur = document.getElementById('burbujaInfo'),
        pista = document.getElementById('pistaToca');
    if (!btn || !img || !bur) return;

    var AUTOCIERRE_MS = 8000,        // tiempo que queda abierta la burbuja
        REPOSO_MS = 4000;            // tiempo extra si se la estaba usando
    var quieta = btn.dataset.imgQuieta, feliz = btn.dataset.imgFeliz, t = null;
    (new Image()).src = feliz;       // precarga: sin parpadeo al tocar

    function armar(ms) { clearTimeout(t); t = setTimeout(cerrar, ms); }
    function abrir() {
        bur.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        img.src = feliz;
        btn.classList.remove('salta'); void btn.offsetWidth; btn.classList.add('salta');
        armar(AUTOCIERRE_MS);
    }
    function cerrar() {
        clearTimeout(t);
        bur.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        img.src = quieta;
        btn.classList.remove('salta');
    }

    btn.addEventListener('click', function () { bur.hidden ? abrir() : cerrar(); });

    /* Mientras alguien usa la burbuja (mouse encima, foco o toque), no se cierra */
    ['mouseenter', 'focusin', 'pointerdown'].forEach(function (ev) {
        bur.addEventListener(ev, function () { clearTimeout(t); });
    });
    ['mouseleave', 'focusout'].forEach(function (ev) {
        bur.addEventListener(ev, function () { if (!bur.hidden) armar(REPOSO_MS); });
    });
    bur.addEventListener('pointerup', function () { if (!bur.hidden) armar(AUTOCIERRE_MS); });

    document.addEventListener('click', function (e) {          // tocar afuera cierra
        if (!bur.hidden && !bur.contains(e.target) && !btn.contains(e.target)) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !bur.hidden) { cerrar(); btn.focus(); }
    });

    /* El cartelito se apaga al presionar cualquiera de los botones de rol */
    function apagarPista() { if (pista) pista.classList.add('oculta'); }
    document.querySelectorAll('[data-perfil]').forEach(function (a) {
        a.addEventListener('pointerdown', apagarPista);
        a.addEventListener('click', apagarPista);
        a.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') apagarPista(); });
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>
</html>
