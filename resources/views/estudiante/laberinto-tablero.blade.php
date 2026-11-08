@extends('layouts.estudiante')

@section('titulo', 'El camino a la escuela — MatiGuarã')

{{-- Música propia del juego (audio/musica/laberinto_matematico.mp3): se declara para que el layout no arranque la generada por código --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/laberinto-tablero.blade.php — «El camino a la escuela»
    =========================================================================
    Variables: $sesion, $nivel, $juego, $config (las arma LaberintoController::mostrarTablero).
    Toda la lógica del juego vive en public/js/laberinto-v2.js y los estilos en
    public/css/laberinto-v2.css (aislados bajo #lb-root).

    El tablero ocupa toda la pantalla: tapa el encabezado y la navegación del
    layout para que el niño tenga el mayor espacio posible para el laberinto.
    La barra de tiempo del layout (RN-L10) sigue visible arriba.
    Guaraní: BORRADOR a validar con una persona hablante.
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/laberinto-v2.css') }}?v=5">
<style>
    /* Solo en esta pantalla: el juego ocupa TODA la pantalla (celular, tablet y compu).
       El layout deja el contenido dentro de <main>, que crea su propia capa (z-index) y por eso
       el encabezado y la barra de abajo quedaban por encima del tablero. Se ocultan acá y se
       libera la capa de <main> para que #lb-root (position: fixed) cubra la ventana completa. */
    .decoracion-lateral,
    .header-estudiante,
    .franja-frutas,
    .nav-inferior { display: none !important; }
    body main.container-fluid {
        position: static !important; z-index: auto !important;
        max-width: none !important; padding: 0 !important; margin: 0 !important;
    }
    html, body { overflow: hidden; height: 100%; overscroll-behavior: none; }
</style>
@endpush

@section('contenido')
<div id="lb-root">
 <audio id="lb-music" loop preload="none">
  <source src="{{ asset('audio/musica/laberinto_matematico.mp3') }}" type="audio/mpeg">
 </audio>
 <section id="s-juego" class="screen game" aria-label="Juego">
  <header class="hud">
   <button class="ib" id="b-home" type="button" aria-label="Volver a los juegos">←</button>
   <div class="hud-t"><strong id="h-nivel"></strong><small id="h-grado"></small></div>
   <button class="ib" id="b-mus" type="button" aria-pressed="false" aria-label="Música de fondo">🎵</button>
   <button class="ib" id="b-snd" type="button" aria-pressed="true" aria-label="Sonido">🔊</button>
   <button class="ib" id="b-pad" type="button" aria-pressed="false" aria-label="Flechas en pantalla">⌨</button>
  </header>
  <div class="hud2">
   <span class="stars" id="h-stars" role="img" aria-label="Estrellas posibles"></span>
   <span class="pill"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#lb-chipa"/></svg><b id="h-chipas">0/0</b></span>
   <span class="pill">Puntos <b id="h-pts">0</b></span>
   <span class="route" id="h-route" aria-label="Obstáculos del camino"></span>
  </div>
  <div class="guara" aria-live="polite">
   <img id="g-img" alt="" width="60" height="60">
   <div class="bubble"><p class="es" id="g-es"></p><p class="gn" id="g-gn" lang="gn"></p></div>
  </div>
  <div class="stage" id="stage">
   <canvas id="cv" role="img" aria-label="Laberinto: llevá a tu personaje de la casa a la escuela"></canvas>
   <div class="modal" id="modal" hidden>
    <div class="card" role="alertdialog" aria-labelledby="m-t">
     <h3 id="m-t">La flecha ayuda, pero…</h3>
     <p>Si usás la flecha ya no podés llegar con 3 estrellas. Igual podés llegar con 2.</p>
     <p class="gn">Peteĩ flecha ndive ndaikatuvéima ereguahẽ mbohapy estrella ndive.</p>
     <div class="row"><button class="btn gold" id="m-yes" type="button">Sí, usar la flecha</button><button class="btn" id="m-no" type="button">No, sigo solo</button></div>
    </div>
   </div>
  </div>
  <div class="ctrl">
   <button class="btn arrowbtn" id="b-arrow" type="button">🧭 ¿Por dónde?</button>
   <div class="dpad" id="dpad">
    <button class="u" type="button" data-d="u" aria-label="Arriba">▲</button>
    <button class="l" type="button" data-d="l" aria-label="Izquierda">◀</button>
    <button class="d" type="button" data-d="d" aria-label="Abajo">▼</button>
    <button class="r" type="button" data-d="r" aria-label="Derecha">▶</button>
   </div>
  </div>

  {{-- Fin del tiempo de juego: se termina el ejercicio y se va a descansar --}}
  <div class="modal" id="rest" hidden style="z-index:9">
   <div class="card" role="alertdialog" aria-labelledby="rest-h" style="text-align:center;align-items:center">
    <img id="rest-img" alt="" width="110" height="110" style="object-fit:contain">
    <h3 id="rest-h">¡Terminó el tiempo de hoy!</h3>
    <p class="gn">Opa ko árape ñembosarái!</p>
    <p id="rest-t"></p>
    <p>Guardamos tu camino. Descansá un ratito y mirá lejos. <span class="gn">Eñemomýi sapy'ami ha ehecha mombyry.</span></p>
    <button class="btn gold big" id="rest-go" type="button">Ir a descansar</button>
   </div>
  </div>

  {{-- Llegada a la escuela: se guarda la partida y se pasa al resumen --}}
  <div class="modal arr" id="arr" hidden style="z-index:10">
   <div class="card" role="alertdialog" aria-labelledby="arr-msg">
    <span class="school" aria-hidden="true">🏫</span>
    <div class="stars-big" id="arr-stars" role="img"></div>
    <h3 id="arr-msg" aria-live="polite">Guardando tu camino…</h3>
    <p class="gn">Ereguahẽ mbo'ehaópe!</p>
    <div id="arr-medals"></div>
    <button class="btn gold" id="arr-go" type="button" hidden>Continuar ➜</button>
    <button class="btn" id="arr-retry" type="button" hidden>Reintentar</button>
   </div>
  </div>

  <div class="panel" id="panel" hidden role="dialog" aria-labelledby="p-title">
   <div class="p-head"><span class="p-emo" id="p-emo" aria-hidden="true"></span><strong id="p-title"></strong><span class="p-att" id="p-att"></span></div>
   <p class="p-es" id="p-es"></p>
   <p class="p-gn gn" id="p-gn" lang="gn"></p>
   <div class="p-eq" id="p-eq"></div>
   <div class="p-aid" id="p-aid" hidden></div>
   <div class="p-hint" id="p-hint" hidden></div>
   <div class="p-sol" id="p-sol" hidden></div>
   <div class="p-opts" id="p-opts"></div>
   <div class="p-fb" id="p-fb" aria-live="polite"></div>
   <div class="p-tools">
    <button class="tool" id="t-hint" type="button">💡 Pista</button>
    <button class="tool" id="t-aid" type="button">🔢 Dibujo</button>
    <button class="tool" id="t-say" type="button">🔊 Escuchar</button>
    <button class="tool go" id="t-next" type="button" hidden>Seguir ➜</button>
   </div>
  </div>
 </section>
</div>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
 <defs>
  <symbol id="lb-chipa" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7.2" fill="none" stroke="#E0A33A" stroke-width="5.2"/><circle cx="12" cy="12" r="7.2" fill="none" stroke="#FFD98A" stroke-width="1.6" stroke-dasharray="5 4.6"/><circle cx="12" cy="12" r="9.9" fill="none" stroke="#1B3361" stroke-width="1"/><circle cx="12" cy="12" r="4.5" fill="none" stroke="#1B3361" stroke-width="1"/></symbol>
  <symbol id="lb-star" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6.2 6.8.8-5 4.7 1.3 6.7L12 17.5 6 20.9l1.3-6.7-5-4.7 6.8-.8z" stroke-linejoin="round"/></symbol>
 </defs>
</svg>
@endsection

@push('scripts')
<script>window.LB_CFG = @json($config);</script>
<script src="{{ asset('js/laberinto-v2.js') }}?v=3"></script>
@endpush
