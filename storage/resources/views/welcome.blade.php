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
        body {
            background: linear-gradient(135deg, #1B3361 0%, #0E6B7D 50%, #177040 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .btn-perfil {
            border-radius: 20px;
            padding: 30px 20px;
            border: 3px solid transparent;
            transition: all .2s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-weight: bold;
        }
        .btn-perfil:hover { transform: translateY(-6px); box-shadow: 0 16px 32px rgba(0,0,0,0.25); }
        .emoji { font-size: 3.5rem; display: block; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center text-center">
        <div class="col-12 mb-5">
            <h1 class="text-white fw-bold display-5">🎓 App Educativa Bilingüe</h1>
            <p class="text-white fs-5">Matemáticas en castellano y guaraní — Primer Ciclo EEB</p>
            <p class="text-white">FACAT — Universidad Autónoma de Encarnación 2026</p>
        </div>

        {{-- Tarjeta Estudiante --}}
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
            <a href="{{ route('estudiante.login') }}"
               class="btn btn-perfil w-100 text-decoration-none d-block"
               style="background:#DAE8FC; color:#1B3361;">
                <span class="emoji">🎮</span>
                <span class="fs-5 d-block">¡Quiero jugar!</span>
                <small class="text-muted d-block mt-1">Soy estudiante</small>
            </a>
        </div>

        {{-- Tarjeta Docente/Admin --}}
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
            <a href="{{ route('login') }}"
               class="btn btn-perfil w-100 text-decoration-none d-block"
               style="background:#D5E8D4; color:#177040;">
                <span class="emoji">👩‍🏫</span>
                <span class="fs-5 d-block">Soy docente</span>
                <small class="text-muted d-block mt-1">Panel de seguimiento</small>
            </a>
        </div>

        {{-- Tarjeta Padre/Tutor --}}
        <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
            <a href="{{ route('login') }}"
               class="btn btn-perfil w-100 text-decoration-none d-block"
               style="background:#FFF2CC; color:#8A5A00;">
                <span class="emoji">👨‍👩‍👧</span>
                <span class="fs-5 d-block">Soy padre/madre</span>
                <small class="text-muted d-block mt-1">Ver progreso de mi hijo/a</small>
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>
</html>
