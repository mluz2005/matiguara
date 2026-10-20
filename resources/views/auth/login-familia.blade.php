<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso para familias — MatiGuará</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Fuentes Baloo 2 y Nunito (ya incluidas en el proyecto) y estilos del login --}}
    <link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/login-v2.css') }}?v=1">
</head>
<body class="lg2 lg2-adulto">

<div class="lg2-deco" aria-hidden="true">
    <span class="d1">☁️</span><span class="d2">⭐</span><span class="d3">🍎</span>
    <span class="d4">🍌</span><span class="d5">🍊</span><span class="d6">🌈</span>
</div>

<main class="lg2-tablero">

    {{-- ═ Columna 1: MatiGuará ═══════════════════════════════════════════════ --}}
    <section class="lg2-izq" aria-label="Bienvenida">
        <div class="lg2-bienvenida">
            <div class="lg2-mascota">
                <img src="{{ asset('img/guara/guara-guino.webp') }}" alt="MatiGuará, el guacamayo mascota" width="230" height="260">
            </div>
            <div class="lg2-saludo">
                <h1>Acceso para familias</h1>
                <p>Ver cómo le va a mi hijo/a</p>
                <p class="gn">Eike</p>
                <small>MatiGuará — FACAT, Universidad Autónoma de Encarnación</small>
            </div>
        </div>
    </section>

    {{-- ═ Columna 2: formulario ═════════════════════════════════════════════ --}}
    <section class="lg2-centro" aria-label="Entrar">

        @if($errors->any())
            <div class="lg2-error" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <div class="lg2-nota" style="margin:0 0 14px">
            <strong>¿Cómo entro?</strong><br>
            Con <strong>dos datos</strong>: el <strong>correo</strong> que le dio a la docente
            y el <strong>código</strong> de su hijo/a (el mismo que usa para jugar, por ejemplo
            <span style="font-family:monospace">SAN-1A-007</span>).
            No hay contraseña.
        </div>

        {{-- POST /familias → LoginFamiliaController::autenticar() --}}
        <form method="POST" action="{{ route('familia.login.post') }}" novalidate>
            @csrf

            <div class="lg2-campo">
                <label for="email"><i class="bi bi-envelope me-1"></i> Su correo electrónico</label>
                <input type="email" name="email" id="email"
                       class="lg2-input @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="correo@ejemplo.com"
                       autocomplete="email" inputmode="email"
                       autocapitalize="none" autofocus required>
            </div>

            <div class="lg2-campo" style="margin-bottom:18px">
                <label for="codigo_acceso"><i class="bi bi-key me-1"></i> Código de su hijo/a</label>
                <input type="text" name="codigo_acceso" id="codigo_acceso"
                       class="lg2-input codigo @error('codigo_acceso') is-invalid @enderror"
                       value="{{ old('codigo_acceso') }}"
                       placeholder="SAN-1A-007"
                       autocomplete="off" autocapitalize="characters" maxlength="20" required>
            </div>

            <button type="submit" class="btn-ingresar">
                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
            </button>
        </form>

        <p class="lg2-chico">
            ¿No tiene su correo cargado, o no le funciona?<br>
            <strong>Consulte a la docente</strong>: ella lo puede cargar o corregir.
        </p>
        <p class="lg2-pie"><a href="{{ route('inicio') }}">← Volver al inicio</a></p>
    </section>

    {{-- ═ Columna 3: ¿quién sos? ═══════════════════════════════════════════ --}}
    @include('auth._lg2-roles', ['actual' => 'familia'])
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>
</html>
