<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Docente / Admin — App Educativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            /* Fondo CLARO: antes era el mismo degradado del encabezado de la tarjeta y se confundían */
            background: #E6ECF5;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card-login {
            border-radius: 20px;
            border: 1px solid #B8C4D8;
            box-shadow: 0 12px 36px rgba(27,51,97,0.18);
            max-width: 420px;
            width: 100%;
        }
        .logo-header {
            background: linear-gradient(135deg, #1B3361, #0E6B7D);
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
            color: white;
        }
        .logo-header .icono { font-size: 3.5rem; }
        .btn-acceso-est {
            border: 2px dashed #1B3361;
            color: #1B3361;
            border-radius: 10px;
            transition: all .2s;
        }
        .btn-acceso-est:hover {
            background: #1B3361;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">

            <div class="card card-login">

                {{-- Header con logo --}}
                <div class="logo-header">
                    <div class="icono"><i class="bi bi-mortarboard-fill"></i></div>
                    <h4 class="fw-bold mt-2 mb-0">App Educativa Bilingüe</h4>
                    <small class="opacity-75">FACAT — Universidad Autónoma de Encarnación</small>
                </div>

                <div class="card-body p-4">

                    <h5 class="text-center text-muted mb-4">
                        <i class="bi bi-person-badge me-1"></i> Ingreso Docente / Admin
                    </h5>

                    {{-- Mensajes de error --}}
                    @if($errors->any())
                        <div class="alert alert-danger rounded-3 py-2">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- Mensaje de éxito (ej: cierre de sesión) --}}
                    @if(session('mensaje'))
                        <div class="alert alert-success rounded-3 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            {{ session('mensaje') }}
                        </div>
                    @endif

                    {{-- FORMULARIO DE LOGIN --}}
                    {{--
                        POST /login → LoginController::autenticar()
                        Guard 'web' — email + password_hash (bcrypt)
                        RF-01: autenticación por perfil (docente | padre | admin)
                    --}}
                    <form method="POST" action="{{ route('login.post') }}" novalidate>
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <i class="bi bi-envelope me-1"></i> Correo electrónico
                            </label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   class="form-control form-control-lg rounded-3 @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   placeholder="docente@escuela.edu.py"
                                   autocomplete="email"
                                   autofocus
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Contraseña --}}
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <i class="bi bi-lock me-1"></i> Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       name="password"
                                       id="password"
                                       class="form-control form-control-lg rounded-start-3 @error('password') is-invalid @enderror"
                                       placeholder="••••••••"
                                       autocomplete="current-password"
                                       required>
                                {{-- Toggle mostrar/ocultar contraseña --}}
                                <button type="button"
                                        class="btn btn-outline-secondary rounded-end-3"
                                        onclick="togglePassword()"
                                        tabindex="-1"
                                        title="Mostrar/ocultar contraseña">
                                    <i class="bi bi-eye" id="iconoPass"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Recordarme --}}
                        <div class="mb-4 form-check">
                            <input type="checkbox"
                                   name="recuerdar"
                                   id="recuerdar"
                                   class="form-check-input"
                                   value="1">
                            <label class="form-check-label text-muted" for="recuerdar">
                                Recordar mi sesión
                            </label>
                        </div>

                        {{-- Botón de ingreso --}}
                        <div class="d-grid">
                            <button type="submit"
                                    class="btn btn-lg fw-bold rounded-3"
                                    style="background-color:#1B3361; color:white;">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    {{-- Enlace para estudiantes --}}
                    <div class="text-center">
                        <p class="text-muted small mb-2">¿Sos estudiante?</p>
                        <a href="{{ route('estudiante.login') }}"
                           class="btn btn-acceso-est w-100">
                            <i class="bi bi-controller me-1"></i>
                            ¡Quiero jugar! — Acceso con mi código
                        </a>
                    </div>

                </div>
            </div>

            {{-- Footer de la página de login --}}
            <p class="text-center mt-3 small" style="color:#1B3361">
                Proyecto de Grado 2026 — Licenciatura en Análisis de Sistemas Informáticos
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmh4qXU1WXZ7g9KGHGhxK4hRFwOt"
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
