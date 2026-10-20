<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Docente / Admin — MatiGuará</title>
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
                <h1>MatiGuará</h1>
                <p>App Educativa Bilingüe</p>
                <small>FACAT — Universidad Autónoma de Encarnación</small>
            </div>
        </div>
    </section>

    {{-- ═ Columna 2: formulario ═════════════════════════════════════════════ --}}
    <section class="lg2-centro" aria-label="Entrar">
        <h2 style="font:800 1.3rem var(--f-titulo);margin:0 0 12px;text-align:center">🪪 Ingreso Docente / Admin</h2>

        {{-- Mensajes de error --}}
        @if($errors->any())
            <div class="lg2-error" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- Mensaje de éxito (ej: cierre de sesión) --}}
        @if(session('mensaje'))
            <div class="lg2-exito" role="status">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('mensaje') }}
            </div>
        @endif

        {{--
            FORMULARIO DE LOGIN
            POST /login → LoginController::autenticar()
            Guard 'web' — email + password_hash (bcrypt)
            RF-01: autenticación por perfil (docente | padre | admin)
        --}}
        <form method="POST" action="{{ route('login.post') }}" novalidate>
            @csrf

            <div class="lg2-campo">
                <label for="email"><i class="bi bi-envelope me-1"></i> Correo electrónico</label>
                <input type="email" name="email" id="email"
                       class="lg2-input @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="docente@escuela.edu.py"
                       autocomplete="email" autofocus required>
            </div>

            <div class="lg2-campo">
                <label for="password"><i class="bi bi-lock me-1"></i> Contraseña</label>
                <div class="lg2-pass">
                    <input type="password" name="password" id="password"
                           class="lg2-input @error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                    {{-- Toggle mostrar/ocultar contraseña --}}
                    <button type="button" class="lg2-ojo" onclick="togglePassword()"
                            title="Mostrar/ocultar contraseña" aria-label="Mostrar u ocultar la contraseña">
                        <i class="bi bi-eye" id="iconoPass"></i>
                    </button>
                </div>
            </div>

            <label class="lg2-check" for="recuerdar">
                <input type="checkbox" name="recuerdar" id="recuerdar" value="1">
                Recordar mi sesión
            </label>

            <button type="submit" class="btn-ingresar">
                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
            </button>
        </form>

        <p class="lg2-chico">Proyecto de Grado 2026 — Licenciatura en Análisis de Sistemas Informáticos</p>
    </section>

    {{-- ═ Columna 3: ¿quién sos? ═══════════════════════════════════════════ --}}
    @include('auth._lg2-roles', ['actual' => 'docente'])
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
    /** Toggle mostrar/ocultar contraseña */
    function togglePassword() {
        const input = document.getElementById('password');
        const icono = document.getElementById('iconoPass');
        if (input.type === 'password') {
            input.type = 'text';
            icono.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icono.className = 'bi bi-eye';
        }
    }
</script>
</body>
</html>
