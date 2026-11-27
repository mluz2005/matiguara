<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head')
    <title>{{ \App\Support\TextosComunes::unido(\App\Support\TextosComunes::INGRESO['pestana']) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Fuentes Baloo 2 y Nunito (ya incluidas en el proyecto) y estilos del login --}}
    <link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/login-v2.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/fondo-selva.css') }}?v=1">
</head>
<body class="lg2">
@php $tx = \App\Support\TextosComunes::INGRESO; $tk = \App\Support\TextosComunes::TECLADO; @endphp

{{-- Decoración de fondo: solo adorno (no la leen los lectores de pantalla) --}}
<div class="lg2-deco" aria-hidden="true">
    <span class="d1">☁️</span><span class="d2">⭐</span><span class="d3">🍎</span>
    <span class="d4">🍌</span><span class="d5">🍊</span><span class="d6">🌈</span>
</div>

<main class="lg2-tablero">

    {{-- ═ Columna 1: MatiGuarã da la bienvenida ═══════════════════════════ --}}
    <section class="lg2-izq" aria-label="Bienvenida">
        <div class="lg2-bienvenida">
            <div class="lg2-mascota">
                <img id="guaraImg" src="{{ asset('img/guara/guara-guino.webp') }}" alt="MatiGuarã, el guacamayo mascota, te saluda" width="230" height="260">
            </div>
            <div class="lg2-saludo">
                <h1>{{ $tx['titulo']['es'] }}<span class="lg2-gn" lang="gn">{{ $tx['titulo']['gn'] }}</span></h1>
                <p>{{ $tx['subtitulo']['es'] }}</p>
                <p class="gn" lang="gn">{{ $tx['subtitulo']['gn'] }}</p>
                <div class="lg2-burbuja" id="burbuja" role="status" aria-live="polite"><span>{{ $tx['burbujaInicio']['es'] }}</span><span class="lg2-gn" lang="gn">{{ $tx['burbujaInicio']['gn'] }}</span></div>
            </div>
        </div>
    </section>

    {{-- ═ Columna 2: código y PIN ═══════════════════════════════════════════ --}}
    <section class="lg2-centro" aria-label="Entrar">

        {{-- Error de autenticación --}}
        @if($errors->any())
            @php
                $errEs = $errors->first('codigo_acceso') ?: $errors->first();
                $errGn = \App\Support\TextosComunes::errorIngresoGn($errEs);
            @endphp
            <div class="lg2-error" id="errorServidor" role="alert">
                😅 {{ $errEs }}@if($errGn)<span class="lg2-gn" lang="gn">{{ $errGn }}</span>@endif
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
                <label for="codigo_acceso" class="titulo">{{ $tx['etCodigo']['es'] }}<span class="lg2-gn" lang="gn">{{ $tx['etCodigo']['gn'] }}</span></label>
                <input type="text"
                       name="codigo_acceso"
                       id="codigo_acceso"
                       class="lg2-codigo @error('codigo_acceso') is-invalid @enderror"
                       value="{{ old('codigo_acceso') }}"
                       placeholder="SAN-1A-007"
                       autocomplete="off"
                       autocapitalize="characters"
                       autocorrect="off"
                       spellcheck="false"
                       enterkeyhint="next"
                       maxlength="20"
                       required>
                <p class="lg2-ayuda">{{ $tx['ayudaCodigo']['es'] }}<span class="lg2-gn" lang="gn">{{ $tx['ayudaCodigo']['gn'] }}</span></p>
            </div>

            <div>
                <label class="titulo d-block text-center" id="rotuloPin">{{ $tx['etPin']['es'] }}<span class="lg2-gn" lang="gn">{{ $tx['etPin']['gn'] }}</span></label>

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
                            title="{{ \App\Support\TextosComunes::unido($tk['borrarUltimo']) }}" aria-label="{{ \App\Support\TextosComunes::unido($tk['borrarUltimo']) }}">⌫</button>
                    <button type="button" class="btn-num" onclick="agregarDigito(0)">0</button>
                    <button type="button" class="btn-num btn-borrar" onclick="borrarTodo()"
                            title="{{ \App\Support\TextosComunes::unido($tk['borrarTodo']) }}" aria-label="{{ \App\Support\TextosComunes::unido($tk['borrarTodo']) }}">🗑</button>
                </div>
            </div>

            <button type="submit" class="btn-jugar" id="btnJugar" disabled>{{ $tx['botonEntrar']['es'] }}<span class="lg2-gn" lang="gn">{{ $tx['botonEntrar']['gn'] }}</span></button>
            <p class="lg2-aviso" id="aviso" role="status" aria-live="polite"></p>
        </form>
    </section>

    {{-- ═ Columna 3: ¿quién sos? ═══════════════════════════════════════════ --}}
    @include('auth._lg2-roles', ['actual' => 'estudiante'])
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
/* Cada vez que un niño llega a ingresar, los sonidos arrancan ACTIVADOS: así un
   dispositivo compartido no queda mudo porque el niño anterior lo silenció.
   (Si el niño lo silencia durante su sesión, se respeta hasta que salga.)
   También se olvida el juego que dejó abierto el niño anterior. */
try {
    localStorage.setItem('matiguara_musica', '1');
    localStorage.setItem('matiguara_audio', '1');
    sessionStorage.removeItem('matiguara_reabrir');
} catch (e) {}
</script>
<script>
/**
 * Lógica del teclado de PIN (la misma de siempre) + reacciones de MatiGuarã.
 *
 * Mantiene un array de hasta 4 dígitos y lo muestra visualmente.
 * Cuando tiene 4 dígitos, copia el valor al input oculto y habilita el botón.
 *
 * RF-01: el PIN nunca se muestra como texto — se muestran estrellas.
 * RN-01: el PIN se envía por POST (HTTPS en producción), no en la URL.
 */
let digitos = [];  // Array con los dígitos ingresados (máx 4)

// Textos (castellano + guaraní) que manda el servidor: App\Support\TextosComunes::INGRESO.
const TX = @json(\App\Support\TextosComunes::ingresoParaJs());
function llenar(t, n) { return { es: t.es.replace('{n}', n), gn: t.gn.replace('{n}', n) }; }
/** Castellano y, debajo, guaraní (chico, en cursiva). */
function mostrarBi(nodo, t) {
    nodo.textContent = '';
    const es = document.createElement('span'); es.textContent = t.es; nodo.appendChild(es);
    const gn = document.createElement('span'); gn.className = 'lg2-gn'; gn.lang = 'gn'; gn.textContent = t.gn; nodo.appendChild(gn);
}

// ── MatiGuarã: cambia de pose según lo que hace el niño ──────────────────
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
    if (texto) mostrarBi(burbuja, texto);
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
    if (pin.length === 4 && hayCodigo) pose('festeja', TX.burbujaListo, 'salta');
    else if (pin.length === 4)         pose('mira', TX.burbujaFaltaCodigo);
    else if (pin.length > 0)           pose('mira', llenar(TX.burbujaSigue, 4 - pin.length));
    else if (hayCodigo)                pose('guino', TX.burbujaPin);
    else                               pose('guino', TX.burbujaInicio);
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
        mostrarBi(aviso, TX.avisoSinCodigo);
        pose('piensa', TX.burbujaSinCodigo, 'uy');
        document.getElementById('codigo_acceso').focus();
        return;
    }
    if (pin.length !== 4) {
        e.preventDefault();
        mostrarBi(aviso, TX.avisoPinCorto);
        pose('piensa', TX.burbujaPinCorto, 'uy');
    }
});

document.getElementById('codigo_acceso').addEventListener('input', actualizarDisplay);

// ── Teclado físico (computadora, o tablet con teclado) ─────────────────────
// Es un complemento del teclado en pantalla, que sigue funcionando igual:
//   · En el campo del código: Enter pasa al PIN (si el código no está vacío).
//   · Fuera de ese campo: los números 0-9 (fila superior o teclado numérico)
//     entran directo al PIN; Retroceso borra el último; Supr o Esc borran todo;
//     Enter equivale a tocar «Entrar a jugar» (si falta algo, muestra el aviso).
// No hay ningún campo de texto para el PIN: así en celulares y tabletas no se
// abre el teclado del sistema y el teclado de la pantalla no queda tapado.
const campoCodigo = document.getElementById('codigo_acceso');
// El cursor arranca en el código solo con mouse o touchpad. En pantallas táctiles no:
// abriría el teclado del sistema y taparía el teclado de números y la mascota.
if (window.matchMedia && window.matchMedia('(pointer: fine)').matches) campoCodigo.focus();
campoCodigo.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();                       // no enviar el formulario con el PIN vacío
    if (campoCodigo.value.trim() === '') return;
    campoCodigo.blur();                       // el foco sale del campo: los números van al PIN
    pose('guino', TX.burbujaPin);
});
document.addEventListener('keydown', function (e) {
    if (e.target === campoCodigo) return;     // escribiendo el código: no interferir
    if (e.ctrlKey || e.metaKey || e.altKey) return;   // respetar atajos del navegador
    if (/^[0-9]$/.test(e.key)) {
        e.preventDefault();
        if (!e.repeat) agregarDigito(parseInt(e.key, 10));   // mantener apretada no repite
    } else if (e.key === 'Backspace') {
        e.preventDefault();
        if (!e.repeat) borrarDigito();
    } else if (e.key === 'Escape' || e.key === 'Delete') {
        borrarTodo();
    } else if (e.key === 'Enter') {
        e.preventDefault();                   // evita que Enter «toque» el botón con foco
        const f = document.getElementById('formAcceso');
        if (f.requestSubmit) f.requestSubmit(); else if (!document.getElementById('btnJugar').disabled) f.submit();
    }
});

// Si el servidor devolvió un error, MatiGuarã se muestra pensativa
@if($errors->any())
pose('piensa', TX.burbujaError, 'uy');
@endif
</script>
</body>
</html>
