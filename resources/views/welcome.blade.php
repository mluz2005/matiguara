<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Educativa Bilingüe — UNAE Encarnación</title>
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
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }
        .container { position: relative; z-index: 1; padding-top: 20px; padding-bottom: 28px; }
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

        .titulo-inicio {
            display: inline-block;
            background: #fff; color: #1B3361;
            border: 4px solid #1B3361; border-radius: 28px;
            box-shadow: 0 7px 0 #1B3361;
            padding: 12px 26px;
        }
        .titulo-inicio .mascota { font-size: 3.2rem; line-height: 1; }
        .subtitulo-inicio { color: #1B3361; font-weight: 700; }

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
</head>
<body>

{{-- Adornos de fondo (solo adorno) --}}
<div class="decoracion" aria-hidden="true">
    <span class="d1">☁️</span><span class="d2">⭐</span><span class="d3">🍎</span>
    <span class="d4">🍌</span><span class="d5">🍊</span><span class="d6">🌈</span>
</div>

<div class="container">
    <div class="row justify-content-center text-center">
        <div class="col-12 mb-4">
            <h1 class="titulo-inicio fw-bold display-6 mb-3">
                <span class="mascota" aria-hidden="true">🦜</span> MatiGuará
            </h1>
            <p class="subtitulo-inicio fs-5 mb-1">Matemáticas en castellano y guaraní — Primer Ciclo EEB</p>
            <p class="subtitulo-inicio mb-0" style="font-size:.95rem">FACAT — Universidad Autónoma de Encarnación 2026</p>

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
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-4">
            <a href="{{ route('estudiante.login') }}"
               class="btn btn-perfil perfil-estudiante w-100 text-decoration-none d-block">
                <span class="emoji" aria-hidden="true">🎮</span>
                <span class="fs-4 d-block">¡Quiero jugar!</span>
                <small class="d-block mt-1">Soy estudiante</small>
            </a>
        </div>

        {{-- Tarjeta Docente/Admin --}}
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-4">
            <a href="{{ route('login') }}"
               class="btn btn-perfil perfil-docente w-100 text-decoration-none d-block">
                <span class="emoji" aria-hidden="true">👩‍🏫</span>
                <span class="fs-4 d-block">Soy docente</span>
                <small class="d-block mt-1">Panel de seguimiento</small>
            </a>
        </div>

        {{-- Tarjeta Padre/Tutor --}}
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-4">
            <a href="{{ route('familia.login') }}"
               class="btn btn-perfil perfil-padre w-100 text-decoration-none d-block">
                <span class="emoji" aria-hidden="true">👨‍👩‍👧</span>
                <span class="fs-4 d-block">Soy padre/madre</span>
                <small class="d-block mt-1">Ver progreso de mi hijo/a</small>
            </a>
        </div>
    </div>
</div>
<p class="text-center mb-4">
    <a href="{{ route('acerca') }}" style="color:#1B3361;font-weight:700;font-size:1.05rem">ℹ️ Acerca de MatiGuará</a>
</p>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>
</html>
