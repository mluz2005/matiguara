<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'App Educativa Bilingüe') — UNAE</title>

    {{--
        LAYOUT PRINCIPAL — layouts/app.blade.php
        =========================================================================
        Layout base para usuarios ADULTOS (docente, padre, admin).
        Bootstrap 5 vía CDN para evitar build step durante el desarrollo.
        En producción (I5), compilar con Vite: npm run build
        =========================================================================
    --}}

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Estilos propios de la app --}}
    <style>
        /* ── Variables de color del sistema ──────────────────────────────── */
        :root {
            --color-primary:   #1B3361;  /* Azul UNAE */
            --color-secondary: #0E6B7D;  /* Teal diagnóstico */
            --color-success:   #177040;  /* Verde logros */
            --color-warning:   #8A5A00;  /* Ámbar configuración */
            --color-danger:    #C0392B;  /* Rojo errores */
            --color-purple:    #7B1FA2;  /* Violeta nuevo v1.2 */
        }

        /* ── Navbar ──────────────────────────────────────────────────────── */
        .navbar-brand img { height: 32px; }
        .navbar { background-color: var(--color-primary) !important; }

        /* ── Sidebar (panel docente) ──────────────────────────────────────  */
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
            padding: .5rem 1rem;
            border-radius: .375rem;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--color-primary);
            color: white;
        }
        .sidebar .nav-link i { margin-right: 8px; }

        /* ── Cards ──────────────────────────────────────────────────────── */
        .card-stat {
            border-left: 4px solid var(--color-primary);
            transition: transform .15s ease;
        }
        .card-stat:hover { transform: translateY(-2px); }

        /* ── Alertas flash ──────────────────────────────────────────────── */
        .alert-flash { animation: fadeInDown .4s ease; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>

    {{-- Estilos adicionales por vista --}}
    @stack('styles')
</head>
<body class="bg-light">

    {{-- ── NAVBAR ─────────────────────────────────────────────────────── --}}
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">

            {{-- Marca / Logo --}}
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('inicio') }}">
                <i class="bi bi-mortarboard-fill fs-4"></i>
                <span class="fw-bold">App Educativa Bilingüe</span>
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto">

                    {{-- Links según perfil del usuario --}}
                    @auth
                        @if(Auth::user()->esDocente())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('docente.panel') }}">
                                    <i class="bi bi-grid-1x2"></i> Mi Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('docente.estudiantes') }}">
                                    <i class="bi bi-people"></i> Estudiantes
                                </a>
                            </li>
                        @elseif(Auth::user()->esPadre())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('padre.panel') }}">
                                    <i class="bi bi-house"></i> Panel
                                </a>
                            </li>
                        @elseif(Auth::user()->esAdmin())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.panel') }}">
                                    <i class="bi bi-gear"></i> Administración
                                </a>
                            </li>
                        @endif

                        {{-- Ayuda (cada perfil ve la guía que le corresponde) --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('ayuda') ? 'active' : '' }}" href="{{ route('ayuda') }}">
                                <i class="bi bi-question-circle"></i> Ayuda
                            </a>
                        </li>
                    @endauth
                </ul>

                {{-- Usuario autenticado --}}
                @auth
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            {{ Auth::user()->nombre }} {{ Auth::user()->apellido }}
                            <span class="badge bg-secondary ms-1">{{ Auth::user()->perfil }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
                    {{-- Botón cerrar sesión siempre visible --}}
@auth
<form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
    @csrf
    <button type="submit"
            class="btn btn-sm btn-outline-light rounded-pill">
        <i class="bi bi-box-arrow-right me-1"></i> Salir
    </button>
</form>
                @endauth
            </div>
        </div>
    </nav>

    {{-- ── ALERTAS FLASH ───────────────────────────────────────────────── --}}
    @if(session('mensaje'))
        <div class="container-fluid mt-2">
            <div class="alert alert-success alert-dismissible alert-flash mb-0" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('mensaje') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container-fluid mt-2">
            <div class="alert alert-danger alert-dismissible alert-flash mb-0" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- ── CONTENIDO PRINCIPAL ─────────────────────────────────────────── --}}
    <main>
        @yield('contenido')
    </main>

    {{-- ── FOOTER ──────────────────────────────────────────────────────── --}}
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container-fluid">
            <small class="text-muted">
                App Educativa Bilingüe — FACAT-UNAE Encarnación 2026
                &nbsp;|&nbsp; Pesallaccia Cardozo, M. L.
                &nbsp;|&nbsp; <a href="{{ route('acerca') }}" class="text-muted">Acerca de</a>
                &nbsp;·&nbsp; v{{ \App\Support\AcercaDe::VERSION }}
            </small>
        </div>
    </footer>

    {{-- Bootstrap 5 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>

    {{-- Scripts adicionales por vista --}}
    @stack('scripts')
</body>
</html>
