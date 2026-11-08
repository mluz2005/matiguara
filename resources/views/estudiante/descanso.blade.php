@extends('layouts.estudiante')

@section('titulo', 'A descansar')

{{--
    VISTA: estudiante/descanso.blade.php  (RN-L10, RN-L11)
    =========================================================================
    Se muestra cuando el niño llegó al límite de juego de la sesión. Es
    amable, no un castigo: sin puntos perdidos y con ideas para descansar.
    Guaraní: BORRADOR, validar con una persona hablante antes de usar con niños.
    =========================================================================
--}}

@section('contenido')
<div class="tarjeta-infantil bg-white p-4 text-center mt-3" id="descanso"
     data-vuelve-en="{{ (int) $vuelveEnSeg }}">
    <div style="font-size:4rem" aria-hidden="true">🦜😴</div>

    <h2 class="fw-bold mt-2" style="color:#1B3361">¡Hora de descansar!</h2>
    <p class="fw-bold mb-3" style="color:#0E6B7D">Ñapytu'ũ ko'ágã!</p>

    <p style="font-size:1.2rem;color:#1B3361" class="mb-1">
        Jugaste mucho y tus ojos necesitan un descanso.
    </p>
    <p style="font-size:1.1rem;color:#0E6B7D" class="mb-3">
        Eñembosarái hetáma ha nde resa oikotevẽ pytu'u.
    </p>

    <div class="text-start mx-auto mb-3" style="max-width:360px;font-size:1.15rem;color:#1B3361">
        <div class="mb-2">👀 Mirá algo lejos, por la ventana.</div>
        <div class="mb-2">🤸 Estirá los brazos y las piernas.</div>
        <div class="mb-2">💧 Tomá un poco de agua.</div>
    </div>

    <div class="rounded-4 py-3 px-2 mb-3" style="background:#FFF3C4;border:3px solid #FFC94D;color:#1B3361">
        <div style="font-size:1.1rem">Volvemos a jugar en</div>
        <div id="descansoMinutos" class="fw-bold" style="font-size:2rem" aria-live="polite">
            {{ max(1, (int) ceil($vuelveEnSeg / 60)) }} min
        </div>
    </div>

    <a href="{{ route('estudiante.juegos') }}" id="descansoVolver"
       class="btn btn-lg fw-bold rounded-pill px-4 d-none"
       style="background:#177040;color:#fff;border:3px solid #1B3361">
        🎮 ¡Volver a jugar!
    </a>

    <div class="mt-3">
        <a href="{{ route('estudiante.progreso') }}" class="me-3 fw-bold" style="color:#1B3361">📊 Mi progreso</a>
        <a href="{{ route('estudiante.logros') }}" class="fw-bold" style="color:#1B3361">🏅 Mis logros</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var caja = document.getElementById('descanso');
    var resto = parseInt(caja.dataset.vuelveEn, 10) || 0;
    var fin = Date.now() + resto * 1000;
    var minutos = document.getElementById('descansoMinutos');
    var volver = document.getElementById('descansoVolver');

    function tic() {
        var seg = Math.max(0, Math.round((fin - Date.now()) / 1000));
        if (seg <= 0) {
            minutos.textContent = '¡Listo!';
            volver.classList.remove('d-none');
            clearInterval(id);
            return;
        }
        minutos.textContent = Math.max(1, Math.ceil(seg / 60)) + ' min';
    }
    var id = setInterval(tic, 1000);
    tic();
})();
</script>
@endpush
