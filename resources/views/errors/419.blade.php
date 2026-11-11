<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head')
    <title>Tu sesión se cerró — MatiGuarã</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background:#EAF2FF; color:#1B3361; padding:16px; box-sizing:border-box; }
        .caja { background:#fff; border-radius:18px; padding:28px 24px; max-width:440px; width:100%;
                text-align:center; box-shadow:0 6px 24px rgba(27,51,97,.15); }
        .caja h1 { font-size:1.4rem; margin:8px 0 10px; }
        .caja p  { font-size:1.05rem; line-height:1.5; margin:0 0 20px; }
        .caja a  { display:inline-block; background:#1B3361; color:#fff; text-decoration:none;
                   font-weight:700; padding:14px 26px; border-radius:12px; font-size:1.1rem; }
        .caja a:focus { outline:3px solid #F4B400; outline-offset:2px; }
    </style>
</head>
<body>
<main class="caja">
    <div style="font-size:3rem" aria-hidden="true">⏳</div>
    <h1>Tu sesión se cerró</h1>
    <p>Pasó un rato sin usar la pantalla y, para cuidar tu cuenta, el sistema la cerró.
       Lo que ya se había guardado sigue ahí. Volvé a entrar para seguir.</p>
    <a href="{{ url('/') }}" autofocus>Volver a entrar</a>
</main>
</body>
</html>
