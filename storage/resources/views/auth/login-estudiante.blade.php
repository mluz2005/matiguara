<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡A Jugar! — App Educativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /*
         * DISEÑO UX INFANTIL — login-estudiante.blade.php
         * Pensado para niños de 6-9 años que aún están aprendiendo a leer.
         * Principios aplicados:
         *   - Tipografía grande (mínimo 1.2rem en labels, 1.5rem en inputs)
         *   - Botones grandes y fáciles de tocar en tablet/celular
         *   - Colores vivos y amigables, sin gris corporativo
         *   - Teclado numérico personalizado para el PIN (evita teclado complejo)
         *   - Emojis como apoyo visual para niños lectores emergentes
         */
        body {
            background: linear-gradient(135deg, #1B3361 0%, #7B1FA2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card-est {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
        }
        .header-card {
            background: linear-gradient(135deg, #0E6B7D, #177040);
            padding: 28px;
            text-align: center;
            color: white;
        }
        .header-card .emoji-grande { font-size: 4rem; }
        .header-card h3 { font-size: 1.6rem; font-weight: 800; }

        /* Código de acceso: input grande y claro */
        .input-codigo {
            font-size: 1.5rem !important;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
            border: 3px solid #dee2e6;
            border-radius: 14px;
            padding: 14px 16px;
        }
        .input-codigo:focus {
            border-color: #0E6B7D;
            box-shadow: 0 0 0 4px rgba(14,107,125, 0.2);
        }

        /* PIN: campos individuales para cada dígito */
        .pin-container {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .pin-digit {
            width: 68px;
            height: 72px;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            border: 3px solid #dee2e6;
            border-radius: 14px;
            background: #f8f9fa;
            caret-color: transparent; /* ocultar cursor parpadeante */
            transition: all .15s;
        }
        .pin-digit:focus {
            border-color: #1B3361;
            background: #DAE8FC;
            box-shadow: 0 0 0 4px rgba(27, 51, 97, 0.2);
            outline: none;
        }
        .pin-digit.lleno { background: #D5E8D4; border-color: #177040; }

        /* Teclado numérico personalizado */
        .teclado-pin {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 240px;
            margin: 0 auto;
        }
        .btn-num {
            height: 60px;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 14px;
            border: 2px solid #dee2e6;
            background: white;
            transition: all .1s;
            cursor: pointer;
        }
        .btn-num:hover, .btn-num:active {
            background: #1B3361;
            color: white;
            border-color: #1B3361;
            transform: scale(1.05);
        }
        .btn-borrar {
            background: #FFF2CC;
            border-color: #8A5A00;
            color: #8A5A00;
        }
        .btn-borrar:hover, .btn-borrar:active {
            background: #8A5A00;
            color: white;
        }

        /* Botón de ingresar */
        .btn-jugar {
            background: linear-gradient(135deg, #177040, #0E6B7D);
            color: white;
            font-size: 1.3rem;
            font-weight: bold;
            padding: 14px;
            border-radius: 16px;
            border: none;
            transition: all .2s;
        }
        .btn-jugar:hover { transform: translateY(-2px); filter: brightness(1.1); }
        .btn-jugar:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">

            <div class="card-est mx-auto">

                {{-- Header amigable para niños --}}
                <div class="header-card">
                    <div class="emoji-grande">🎮</div>
                    <h3 class="mt-2 mb-1">¡A jugar!</h3>
                    <p class="mb-0 opacity-75" style="font-size:1rem">
                        Ingresá tu código y tu PIN secreto
                    </p>
                </div>

                <div class="card-body p-4">

                    {{-- Error de autenticación --}}
                    @if($errors->any())
                        <div class="alert rounded-3 text-center py-3 mb-4"
                             style="background:#FFF2CC; border:2px solid #8A5A00; color:#7D5A00;">
                            <span style="font-size:1.8rem">😅</span>
                            <p class="mb-0 fw-bold mt-1">{{ $errors->first('codigo_acceso') }}</p>
                        </div>
                    @endif

                    {{--
                        FORMULARIO DE ACCESO ESTUDIANTIL
                        POST /acceso → LoginEstudianteController::autenticar()
                        Guard 'estudiante' — codigo_acceso + pin (4 dígitos)
                        RF-01: autenticación por perfil estudiante
                        RN-01: privacidad — el servidor nunca expone id_anonimizado
                    --}}
                    <form method="POST"
                          action="{{ route('estudiante.login.post') }}"
                          id="formAcceso"
                          novalidate>
                        @csrf

                        {{-- Input oculto que acumula los 4 dígitos del PIN --}}
                        <input type="hidden" name="pin" id="pinCompleto">

                        {{-- Código de acceso --}}
                        <div class="mb-4">
                            <label for="codigo_acceso"
                                   class="form-label fw-bold"
                                   style="font-size:1.15rem">
                                🪪 Tu código de acceso
                            </label>
                            <input type="text"
                                   name="codigo_acceso"
                                   id="codigo_acceso"
                                   class="form-control input-codigo @error('codigo_acceso') is-invalid @enderror"
                                   value="{{ old('codigo_acceso') }}"
                                   placeholder="SAN-1A-007"
                                   autocomplete="off"
                                   autocapitalize="characters"
                                   maxlength="20"
                                   autofocus
                                   required>
                            <div class="form-text text-muted mt-1">
                                📋 Tu maestra o maestro te dio este código
                            </div>
                        </div>

                        {{-- PIN: 4 campos individuales --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold d-block text-center"
                                   style="font-size:1.15rem">
                                🔐 Tu PIN secreto (4 números)
                            </label>

                            {{-- 4 cuadros de dígito --}}
                            <div class="pin-container mb-3" id="pinDisplay">
                                <div class="pin-digit" id="d0">—</div>
                                <div class="pin-digit" id="d1">—</div>
                                <div class="pin-digit" id="d2">—</div>
                                <div class="pin-digit" id="d3">—</div>
                            </div>

                            {{-- Teclado numérico personalizado --}}
                            <div class="teclado-pin">
                                @foreach([1,2,3,4,5,6,7,8,9] as $n)
                                    <button type="button"
                                            class="btn-num"
                                            onclick="agregarDigito({{ $n }})">{{ $n }}</button>
                                @endforeach
                                <button type="button"
                                        class="btn-num btn-borrar"
                                        onclick="borrarDigito()"
                                        title="Borrar">
                                    ⌫
                                </button>
                                <button type="button"
                                        class="btn-num"
                                        onclick="agregarDigito(0)">0</button>
                                <button type="button"
                                        class="btn-num btn-borrar"
                                        onclick="borrarTodo()"
                                        title="Borrar todo">
                                    🗑
                                </button>
                            </div>
                        </div>

                        {{-- Botón de acceder (se habilita cuando el PIN tiene 4 dígitos) --}}
                        <div class="d-grid mt-4">
                            <button type="submit"
                                    class="btn-jugar"
                                    id="btnJugar"
                                    disabled>
                                🚀 ¡Entrar a jugar!
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    {{-- Enlace para adultos --}}
                    <div class="text-center">
                        <a href="{{ route('login') }}"
                           class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>
                            Soy docente o administrador/a
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmh4qXU1WXZ7g9KGHGhxK4hRFwOt"
        crossorigin="anonymous"></script>
<script>
/**
 * Lógica del teclado de PIN personalizado.
 *
 * Mantiene un array de hasta 4 dígitos y lo muestra visualmente.
 * Cuando tiene 4 dígitos, copia el valor al input oculto y habilita el botón.
 *
 * RF-01: el PIN nunca se muestra como texto — se muestran asteriscos.
 * RN-01: el PIN se envía por POST (HTTPS en producción), no en la URL.
 */

let digitos = [];  // Array con los dígitos ingresados (máx 4)

function actualizarDisplay() {
    // Actualizar los 4 cuadros visuales
    for (let i = 0; i < 4; i++) {
        const div = document.getElementById('d' + i);
        if (digitos[i] !== undefined) {
            div.textContent = '●';   // Asterisco visual (no muestra el número)
            div.classList.add('lleno');
        } else {
            div.textContent = '—';
            div.classList.remove('lleno');
        }
    }

    // Actualizar el input oculto con el PIN completo
    const pin = digitos.join('');
    document.getElementById('pinCompleto').value = pin;

    // Habilitar el botón de acceso solo cuando hay 4 dígitos
    const btnJugar = document.getElementById('btnJugar');
    btnJugar.disabled = (pin.length !== 4);

    // También verificar que haya código de acceso
    if (document.getElementById('codigo_acceso').value.trim() === '') {
        btnJugar.disabled = true;
    }
}

function agregarDigito(n) {
    if (digitos.length < 4) {
        digitos.push(n);
        actualizarDisplay();
    }
}

function borrarDigito() {
    digitos.pop();
    actualizarDisplay();
}

function borrarTodo() {
    digitos = [];
    actualizarDisplay();
}

// Validación antes de enviar el formulario
document.getElementById('formAcceso').addEventListener('submit', function(e) {
    const codigo = document.getElementById('codigo_acceso').value.trim();
    const pin    = document.getElementById('pinCompleto').value;

    if (!codigo) {
        e.preventDefault();
        alert('Ingresá tu código de acceso primero. 🪪');
        document.getElementById('codigo_acceso').focus();
        return;
    }

    if (pin.length !== 4) {
        e.preventDefault();
        alert('Ingresá tu PIN de 4 números. 🔐');
        return;
    }
});

// Habilitar el botón cuando el código de acceso cambia
document.getElementById('codigo_acceso').addEventListener('input', actualizarDisplay);

// Soporte para teclado físico (tablets con teclado externo)
document.addEventListener('keydown', function(e) {
    if (document.activeElement.id === 'codigo_acceso') return; // No interferir
    if (e.key >= '0' && e.key <= '9') agregarDigito(parseInt(e.key));
    if (e.key === 'Backspace') borrarDigito();
    if (e.key === 'Escape') borrarTodo();
});
</script>
</body>
</html>
