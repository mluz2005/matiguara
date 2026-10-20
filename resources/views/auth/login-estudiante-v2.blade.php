<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡A Jugar! — MatiGuará</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Solo para las fuentes Baloo 2 y Nunito (ya incluidas en el proyecto) --}}
    <link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
    <style>
        /*
         * LOGIN DEL ESTUDIANTE · v2 (rediseño visual en 3 columnas)
         * ─────────────────────────────────────────────────────────────────
         * Pantallas anchas:  [ mascota y saludo ] [ código + PIN ] [ ¿quién sos? ]
         * Pantallas angostas: una sola columna, compacta (sin imagen enorme).
         * Mismo formulario, mismos ids, misma lógica y mismas rutas que la
         * versión anterior (POST /acceso, guard 'estudiante', PIN de 4 números).
         */
        .lg2 {
            --azul:#1B3361; --teal:#0E6B7D; --verde:#177040; --verde-o:#0F5130; --sol:#FFC94D; --gris-t:#44546F;
            --f-titulo:'Baloo 2','Nunito','Trebuchet MS',system-ui,sans-serif;
            --f-texto:'Nunito','Trebuchet MS',system-ui,sans-serif;
            font-family: var(--f-texto); color: var(--azul);
        }
        body.lg2 {
            margin: 0; min-height: 100vh; min-height: 100dvh;
            display: grid; place-items: center; padding: 16px; overflow-x: hidden;
            background: linear-gradient(180deg, #8FD3FF 0%, #CDEEFF 55%, #FFF3C4 100%);
            font-size: 1rem;
        }
        .lg2 *, .lg2 *::before, .lg2 *::after { box-sizing: border-box; }

        /* Decoración (solo adorno) */
        .lg2-deco { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .lg2-deco span { position: absolute; font-size: 2.4rem; animation: lg2-flota 6s ease-in-out infinite; }
        .lg2-deco .d1 { top: 4%; left: 4%; font-size: 3.4rem; } .lg2-deco .d2 { top: 8%; right: 5%; animation-delay: 1s; }
        .lg2-deco .d3 { top: 46%; left: 1.5%; animation-delay: 2s; } .lg2-deco .d4 { top: 52%; right: 2%; animation-delay: 3s; }
        .lg2-deco .d5 { bottom: 5%; left: 6%; animation-delay: 1.5s; } .lg2-deco .d6 { bottom: 4%; right: 6%; font-size: 3rem; animation-delay: .5s; }
        @keyframes lg2-flota { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }

        /* Tablero principal */
        .lg2-tablero {
            position: relative; z-index: 1; width: min(1040px, 100%);
            display: grid; grid-template-columns: 1fr; gap: 0;
            background: #fff; border: 5px solid var(--azul); border-radius: 32px;
            box-shadow: 0 10px 0 var(--azul), 0 22px 40px rgba(27,51,97,.25); overflow: hidden;
        }
        @media (min-width: 900px) {
            .lg2-tablero { grid-template-columns: minmax(0, 1fr) minmax(340px, 1.05fr) minmax(0, .9fr); }
        }

        /* ── Columna izquierda: mascota ───────────────────────────────── */
        .lg2-bienvenida {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            background: linear-gradient(135deg, #FFE08A, var(--sol)); border-bottom: 5px dashed var(--azul);
        }
        .lg2-mascota { flex: none; width: 84px; height: 96px; display: flex; align-items: flex-end; justify-content: center; }
        .lg2-mascota img { display: block; height: 100%; width: auto; max-width: 100%; object-fit: contain; object-position: bottom center; transform-origin: 50% 100%; animation: lg2-respira 3s ease-in-out infinite; }
        .lg2-mascota img.salta { animation: lg2-salta .7s cubic-bezier(.3,1.6,.5,1); }
        .lg2-mascota img.uy { animation: lg2-uy .5s ease; }
        @keyframes lg2-respira { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-3px) } }
        @keyframes lg2-salta { 0% { transform: translateY(0) } 40% { transform: translateY(-16px) rotate(-3deg) } 100% { transform: translateY(0) } }
        @keyframes lg2-uy { 0%,100% { transform: translateX(0) } 25% { transform: translateX(-6px) } 75% { transform: translateX(6px) } }
        .lg2-saludo h1 { margin: 0; font: 800 clamp(1.7rem, 5vw, 2.3rem)/1 var(--f-titulo); }
        .lg2-saludo p { margin: 4px 0 0; font-weight: 700; font-size: 1.02rem; line-height: 1.25; }
        .lg2-saludo .gn { color: var(--teal); }
        .lg2-burbuja {
            position: relative; margin-top: 10px; padding: 8px 12px; background: #fff; border: 3px solid var(--azul);
            border-radius: 16px; box-shadow: 0 4px 0 var(--azul); font-weight: 800; font-size: 1.02rem; line-height: 1.2; display: none;
        }
        .lg2-burbuja::before {
            content: ""; position: absolute; left: 50%; top: -11px; width: 16px; height: 16px; background: #fff;
            border-left: 3px solid var(--azul); border-top: 3px solid var(--azul); transform: translateX(-50%) rotate(45deg);
        }
        @media (min-width: 900px) {
            .lg2-izq { display: flex; flex-direction: column; background: linear-gradient(180deg, #FFE9A8 0%, #FFD86B 100%); border-right: 5px dashed var(--azul); }
            .lg2-bienvenida { flex-direction: column; text-align: center; border: 0; background: none; padding: 22px 18px 6px; gap: 6px; flex: 1; justify-content: center; }
            .lg2-mascota { width: min(100%, 230px); height: clamp(190px, 38vh, 330px); }
            .lg2-burbuja { display: block; }
        }

        /* ── Columna central: código + PIN ─────────────────────────────── */
        .lg2-centro { padding: 18px 20px 18px; }
        .lg2-centro label.titulo { font: 800 1.15rem var(--f-titulo); display: block; margin-bottom: 4px; }
        .lg2-codigo {
            width: 100%; font: 800 1.5rem var(--f-texto); letter-spacing: 4px; text-transform: uppercase; color: var(--azul);
            background: #FFFDF2; border: 3px solid var(--azul); border-radius: 18px; padding: 10px 14px;
        }
        .lg2-codigo::placeholder { color: #5A6785; letter-spacing: 2px; }
        .lg2-codigo:focus { outline: 0; border-color: var(--verde); background: #fff; box-shadow: 0 0 0 5px rgba(23,112,64,.25); }
        .lg2-ayuda { margin: 4px 0 0; color: var(--gris-t); font-size: .95rem; font-weight: 600; }
        .lg2-pin { display: flex; gap: 10px; justify-content: center; margin: 4px 0 12px; }
        .lg2-pin .d {
            width: 58px; height: 62px; font-size: 1.8rem; line-height: 1; display: grid; place-items: center; color: var(--gris-t);
            border: 3px solid var(--azul); border-radius: 18px; background: #FFF8E1; transition: all .15s;
        }
        .lg2-pin .d.lleno { background: #D5F5DC; border-color: var(--verde); transform: scale(1.06); }
        .lg2-teclado { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; max-width: 270px; margin: 0 auto; }
        .btn-num {
            height: 58px; font: 800 1.6rem var(--f-titulo); color: var(--azul); border-radius: 18px; border: 3px solid var(--c, #3B8FD6);
            background: var(--bg, #D5ECFF); box-shadow: 0 5px 0 var(--c, #3B8FD6); cursor: pointer; transition: transform .08s, box-shadow .08s;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-num:active, .btn-num.activo { transform: translateY(4px); box-shadow: 0 1px 0 var(--c); }
        .btn-num:focus-visible { outline: 4px solid var(--azul); outline-offset: 3px; }
        .lg2-teclado .btn-num:nth-child(5n+1) { --c:#D9534F; --bg:#FFE0E0; }
        .lg2-teclado .btn-num:nth-child(5n+2) { --c:#B38600; --bg:#FFF1C2; }
        .lg2-teclado .btn-num:nth-child(5n+3) { --c:#2E9B5A; --bg:#D6F5DD; }
        .lg2-teclado .btn-num:nth-child(5n+4) { --c:#3B8FD6; --bg:#D5ECFF; }
        .lg2-teclado .btn-num:nth-child(5n+5) { --c:#8E6BD1; --bg:#E8DEFF; }
        .lg2-teclado .btn-borrar { --c:#44546F; --bg:#EEF1F6; font-size: 1.4rem; }
        .btn-jugar {
            width: 100%; margin-top: 16px; background: var(--verde); color: #fff; font: 800 1.45rem var(--f-titulo); padding: 8px 14px;
            border-radius: 22px; border: 4px solid var(--verde-o); box-shadow: 0 7px 0 var(--verde-o); transition: transform .1s, box-shadow .1s;
        }
        .btn-jugar:hover:not(:disabled) { filter: brightness(1.08); }
        .btn-jugar:active:not(:disabled) { transform: translateY(5px); box-shadow: 0 2px 0 var(--verde-o); }
        .btn-jugar:focus-visible { outline: 4px solid var(--azul); outline-offset: 3px; }
        .btn-jugar:disabled { background: #E3E9F4; color: #44546F; border: 4px dashed #8896B3; box-shadow: none; cursor: not-allowed; }
        .lg2-error { margin-bottom: 12px; padding: 8px 12px; text-align: center; background: #FFF2CC; border: 3px solid #8A5A00; color: #5C3B00; border-radius: 18px; font-weight: 800; }
        .lg2-aviso { min-height: 1.3em; margin: 8px 0 0; text-align: center; font-weight: 800; font-size: .98rem; color: #8A5A00; }

        /* ── Columna derecha: ¿quién sos? ──────────────────────────────── */
        .lg2-roles { padding: 14px 18px 18px; background: #EAF4FF; border-top: 5px dashed var(--azul); }
        @media (min-width: 900px) { .lg2-roles { border-top: 0; border-left: 5px dashed var(--azul); display: flex; flex-direction: column; justify-content: center; padding: 22px 20px; } }
        .lg2-roles h2 { margin: 0 0 8px; font: 800 1.25rem var(--f-titulo); text-align: center; }
        .lg2-roles ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; grid-template-columns: repeat(3, 1fr); }
        @media (min-width: 900px) { .lg2-roles ul { grid-template-columns: 1fr; gap: 12px; } }
        .lg2-rol {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; text-align: center; text-decoration: none;
            min-height: 78px; padding: 8px 6px; background: #fff; color: var(--azul); border: 3px solid var(--azul); border-radius: 20px;
            box-shadow: 0 5px 0 var(--azul); font: 800 .95rem/1.15 var(--f-texto); transition: transform .1s, box-shadow .1s;
        }
        .lg2-rol .ico { font-size: 1.9rem; line-height: 1; }
        .lg2-rol small { font-weight: 700; font-size: .82rem; color: var(--gris-t); }
        a.lg2-rol:hover { color: var(--azul); background: #FFF8E1; }
        a.lg2-rol:active { transform: translateY(4px); box-shadow: 0 1px 0 var(--azul); }
        a.lg2-rol:focus-visible { outline: 4px solid var(--sol); outline-offset: 3px; }
        .lg2-rol.actual { background: #D5F5DC; border-color: var(--verde); box-shadow: 0 5px 0 var(--verde); }
        .lg2-rol.actual small { color: var(--verde-o); }
        @media (min-width: 900px) { .lg2-rol { flex-direction: row; justify-content: flex-start; gap: 12px; text-align: left; min-height: 74px; padding: 8px 14px; font-size: 1.05rem; } }
        @media (max-width: 420px) { .lg2-rol small { display: none; } .lg2-rol { min-height: 70px; font-size: .88rem; } }
        .lg2-pie { margin: 12px 0 0; text-align: center; }
        .lg2-pie a { display: inline-block; padding: 6px 12px; border-radius: 12px; color: var(--azul); font-weight: 700; text-decoration: underline; }
        .lg2-pie a:hover { background: #fff; }

        /* Pantallas bajas (laptops, celular horizontal): se compacta todo */
        @media (max-height: 720px) and (min-width: 900px) {
            .lg2-tablero { border-radius: 26px; }
            .lg2-mascota { height: clamp(150px, 30vh, 240px); }
            .btn-num { height: 50px; font-size: 1.4rem; } .lg2-pin .d { width: 52px; height: 54px; }
            .lg2-centro { padding-block: 12px; } .btn-jugar { margin-top: 12px; }
        }
        @media (max-width: 899px) and (orientation: landscape) and (max-height: 520px) { body.lg2 { place-items: start center; } }
        @media (prefers-reduced-motion: reduce) { .lg2-deco span, .lg2-mascota img { animation: none !important; } }
    </style>
</head>
<body class="lg2">

{{-- Decoración de fondo: solo adorno (no la leen los lectores de pantalla) --}}
<div class="lg2-deco" aria-hidden="true">
    <span class="d1">☁️</span><span class="d2">⭐</span><span class="d3">🍎</span>
    <span class="d4">🍌</span><span class="d5">🍊</span><span class="d6">🌈</span>
</div>

<main class="lg2-tablero">

    {{-- ═ Columna 1: MatiGuará da la bienvenida ═══════════════════════════ --}}
    <section class="lg2-izq" aria-label="Bienvenida">
        <div class="lg2-bienvenida">
            <div class="lg2-mascota">
                <img id="guaraImg" src="{{ asset('img/guara/guara-guino.webp') }}" alt="MatiGuará, el guacamayo mascota, te saluda" width="230" height="260">
            </div>
            <div class="lg2-saludo">
                <h1>¡A jugar!</h1>
                <p>Ingresá tu código y tu PIN secreto</p>
                <p class="gn">Mba'éichapa!</p>
                <div class="lg2-burbuja" id="burbuja" role="status" aria-live="polite">¡Hola! Escribí tu código.</div>
            </div>
        </div>
    </section>

    {{-- ═ Columna 2: código y PIN ═══════════════════════════════════════════ --}}
    <section class="lg2-centro" aria-label="Entrar">

        {{-- Error de autenticación --}}
        @if($errors->any())
            <div class="lg2-error" id="errorServidor" role="alert">
                😅 {{ $errors->first('codigo_acceso') }}
            </div>
        @endif

        {{--
            FORMULARIO DE ACCESO ESTUDIANTIL
            POST /acceso → LoginEstudianteController::autenticar()
            Guard 'estudiante' — codigo_acceso + pin (4 dígitos)
            RF-01: autenticación por perfil estudiante
            RN-01: privacidad — el servidor nunca expone id_anonimizado
        --}}
        <form method="POST" action="{{ route('estudiante.login.post') }}" id="formAcceso" novalidate>
            @csrf

            {{-- Input oculto que acumula los 4 dígitos del PIN --}}
            <input type="hidden" name="pin" id="pinCompleto">

            <div class="mb-3">
                <label for="codigo_acceso" class="titulo">🪪 Tu código de acceso</label>
                <input type="text"
                       name="codigo_acceso"
                       id="codigo_acceso"
                       class="lg2-codigo @error('codigo_acceso') is-invalid @enderror"
                       value="{{ old('codigo_acceso') }}"
                       placeholder="SAN-1A-007"
                       autocomplete="off"
                       autocapitalize="characters"
                       maxlength="20"
                       autofocus
                       required>
                <p class="lg2-ayuda">📋 Tu maestra o maestro te dio este código</p>
            </div>

            <div>
                <label class="titulo d-block text-center" id="rotuloPin">🔐 Tu PIN secreto (4 números)</label>

                {{-- 4 cuadros: se llenan con una estrella por cada número --}}
                <div class="lg2-pin" id="pinDisplay" aria-live="polite" aria-labelledby="rotuloPin">
                    <div class="d" id="d0">☆</div><div class="d" id="d1">☆</div>
                    <div class="d" id="d2">☆</div><div class="d" id="d3">☆</div>
                </div>

                {{-- Teclado numérico de colores --}}
                <div class="lg2-teclado">
                    @foreach([1,2,3,4,5,6,7,8,9] as $n)
                        <button type="button" class="btn-num" onclick="agregarDigito({{ $n }})">{{ $n }}</button>
                    @endforeach
                    <button type="button" class="btn-num btn-borrar" onclick="borrarDigito()"
                            title="Borrar el último número" aria-label="Borrar el último número">⌫</button>
                    <button type="button" class="btn-num" onclick="agregarDigito(0)">0</button>
                    <button type="button" class="btn-num btn-borrar" onclick="borrarTodo()"
                            title="Borrar todo" aria-label="Borrar todo">🗑</button>
                </div>
            </div>

            <button type="submit" class="btn-jugar" id="btnJugar" disabled>🚀 ¡Entrar a jugar!</button>
            <p class="lg2-aviso" id="aviso" role="status" aria-live="polite"></p>
        </form>
    </section>

    {{-- ═ Columna 3: ¿quién sos? ════════════════════════════════════════════ --}}
    <nav class="lg2-roles" aria-label="Elegí quién sos">
        <h2>¿Quién sos?</h2>
        <ul>
            <li>
                <span class="lg2-rol actual" aria-current="page">
                    <span class="ico" aria-hidden="true">🧒</span>
                    <span>Estudiante<br><small>Estás acá</small></span>
                </span>
            </li>
            <li>
                <a class="lg2-rol" href="{{ route('login') }}">
                    <span class="ico" aria-hidden="true">🧑‍🏫</span>
                    <span>Docente o administrador/a<br><small>Con correo y contraseña</small></span>
                </a>
            </li>
            <li>
                <a class="lg2-rol" href="{{ route('familia.login') }}">
                    <span class="ico" aria-hidden="true">👨‍👩‍👧</span>
                    <span>Familia<br><small>Con correo y código</small></span>
                </a>
            </li>
        </ul>
        <p class="lg2-pie"><a href="{{ route('acerca') }}">ℹ️ Acerca de MatiGuará</a></p>
    </nav>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
/**
 * Lógica del teclado de PIN (la misma de siempre) + reacciones de MatiGuará.
 *
 * Mantiene un array de hasta 4 dígitos y lo muestra visualmente.
 * Cuando tiene 4 dígitos, copia el valor al input oculto y habilita el botón.
 *
 * RF-01: el PIN nunca se muestra como texto — se muestran estrellas.
 * RN-01: el PIN se envía por POST (HTTPS en producción), no en la URL.
 */
let digitos = [];  // Array con los dígitos ingresados (máx 4)

// ── MatiGuará: cambia de pose según lo que hace el niño ──────────────────
const POSES = {
    guino:   @js(asset('img/guara/guara-guino.webp')),
    mira:    @js(asset('img/guara/guara-mira.webp')),
    festeja: @js(asset('img/guara/guara-festeja.webp')),
    piensa:  @js(asset('img/guara/guara-piensa.webp')),
};
Object.values(POSES).forEach(function (u) { new Image().src = u; });   // precarga
const guaraImg = document.getElementById('guaraImg');
const burbuja  = document.getElementById('burbuja');
const aviso    = document.getElementById('aviso');
let poseActual = 'guino';
function pose(nombre, texto, efecto) {
    if (nombre !== poseActual) { poseActual = nombre; guaraImg.src = POSES[nombre]; }
    if (texto) burbuja.textContent = texto;
    if (efecto) { guaraImg.classList.remove('salta', 'uy'); void guaraImg.offsetWidth; guaraImg.classList.add(efecto); }
}

function actualizarDisplay() {
    for (let i = 0; i < 4; i++) {
        const div = document.getElementById('d' + i);
        if (digitos[i] !== undefined) { div.textContent = '⭐'; div.classList.add('lleno'); }
        else                          { div.textContent = '☆';  div.classList.remove('lleno'); }
    }

    const pin = digitos.join('');
    document.getElementById('pinCompleto').value = pin;

    const hayCodigo = document.getElementById('codigo_acceso').value.trim() !== '';
    document.getElementById('btnJugar').disabled = (pin.length !== 4) || !hayCodigo;

    // Reacción de la mascota
    aviso.textContent = '';
    if (pin.length === 4 && hayCodigo) pose('festeja', '¡Listo! Tocá «Entrar a jugar».', 'salta');
    else if (pin.length === 4)         pose('mira', 'Falta tu código de acceso.');
    else if (pin.length > 0)           pose('mira', '¡Seguí! Te faltan ' + (4 - pin.length) + ' números.');
    else if (hayCodigo)                pose('guino', 'Ahora tu PIN secreto.');
    else                               pose('guino', '¡Hola! Escribí tu código.');
}

function agregarDigito(n) { if (digitos.length < 4) { digitos.push(n); actualizarDisplay(); } }
function borrarDigito()   { digitos.pop(); actualizarDisplay(); }
function borrarTodo()     { digitos = []; actualizarDisplay(); }

// Validación antes de enviar (sin ventanas emergentes: el aviso aparece en la pantalla)
document.getElementById('formAcceso').addEventListener('submit', function (e) {
    const codigo = document.getElementById('codigo_acceso').value.trim();
    const pin    = document.getElementById('pinCompleto').value;
    if (!codigo) {
        e.preventDefault();
        aviso.textContent = '🪪 Ingresá tu código de acceso primero.';
        pose('piensa', '¿Cuál es tu código?', 'uy');
        document.getElementById('codigo_acceso').focus();
        return;
    }
    if (pin.length !== 4) {
        e.preventDefault();
        aviso.textContent = '🔐 Ingresá tu PIN de 4 números.';
        pose('piensa', 'Faltan números del PIN.', 'uy');
    }
});

document.getElementById('codigo_acceso').addEventListener('input', actualizarDisplay);

// Soporte para teclado físico (tablets con teclado externo)
document.addEventListener('keydown', function (e) {
    if (document.activeElement.id === 'codigo_acceso') return; // No interferir
    if (e.key >= '0' && e.key <= '9') agregarDigito(parseInt(e.key));
    if (e.key === 'Backspace') borrarDigito();
    if (e.key === 'Escape') borrarTodo();
});

// Si el servidor devolvió un error, MatiGuará se muestra pensativa
@if($errors->any())
pose('piensa', '¡Uy! Probemos de nuevo.', 'uy');
@endif
</script>
</body>
</html>
