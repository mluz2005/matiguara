@extends('layouts.estudiante')

@php $d = \App\Support\TextosComunes::DESCANSO; @endphp
@section('titulo', \App\Support\TextosComunes::unido($d['pestana']))

{{--
    VISTA: estudiante/descanso.blade.php  (RN-L10, RN-L11)
    =========================================================================
    Se muestra cuando el niño llegó al límite de juego de la sesión. Es
    amable, no un castigo: sin puntos perdidos y con ideas para descansar.
    Mati duerme en su nido (public/img/numeros/mati-durmiendo.webp).
    Guaraní REVISADO 2026-11-19 (App\Support\TextosComunes::DESCANSO, planilla Guarani_PantallasComunes_revisado).
    =========================================================================
--}}

@section('contenido')
<div class="tarjeta-infantil bg-white p-4 text-center mt-3" id="descanso"
     data-vuelve-en="{{ (int) $vuelveEnSeg }}">
    <img src="{{ asset('img/numeros/mati-durmiendo.webp') }}" alt="{{ \App\Support\TextosComunes::unido($d['imagen']) }}"
         width="260" height="228" class="d-block mx-auto" style="max-width:70%;height:auto">

    @php $enl = \App\Support\TextosComunes::ENLACES_DESCANSO; $minIni = max(1, (int) ceil($vuelveEnSeg / 60)); @endphp
    <h2 class="fw-bold mt-2 mb-0" style="color:#1B3361">{{ $d['titulo']['es'] }}</h2>
    <p class="fw-bold mb-3" style="color:#0E6B7D" lang="gn">{{ $d['titulo']['gn'] }}</p>

    <p style="font-size:1.2rem;color:#1B3361" class="mb-1">
        {{ $d['texto']['es'] }}
    </p>
    <p style="font-size:1.1rem;color:#0E6B7D" class="mb-3" lang="gn">
        {{ $d['texto']['gn'] }}
    </p>

    <div class="text-start mx-auto mb-3" style="max-width:360px;font-size:1.15rem;color:#1B3361">
        @foreach(['consejo1', 'consejo2', 'consejo3'] as $c)
            <div class="mb-2">{{ $d[$c]['es'] }}
                <div style="font-size:.95rem;font-style:italic;font-weight:700;color:#0E6B7D" lang="gn">{{ $d[$c]['gn'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-4 py-3 px-2 mb-3" style="background:#FFF3C4;border:3px solid #FFC94D;color:#1B3361">
        <div style="font-size:1.1rem">{{ $d['volvemos']['es'] }}</div>
        <div style="font-size:.95rem;font-style:italic;font-weight:700;color:#0E6B7D" lang="gn">{{ $d['volvemos']['gn'] }}</div>
        <div id="descansoMinutos" class="fw-bold" style="font-size:2rem" aria-live="polite">
            <div id="descansoMinEs">{{ \App\Support\TextosComunes::rellenar($d['minutos']['es'], ['n' => $minIni]) }}</div>
            <div id="descansoMinGn" lang="gn" style="font-size:1rem;font-style:italic;color:#0E6B7D">{{ \App\Support\TextosComunes::rellenar($d['minutos']['gn'], ['n' => $minIni]) }}</div>
        </div>
    </div>

    <a href="{{ route('estudiante.juegos') }}" id="descansoVolver"
       class="btn btn-lg fw-bold rounded-pill px-4 d-none"
       style="background:#177040;color:#fff;border:3px solid #1B3361">
        {{ $d['volver']['es'] }}
        <span class="d-block" style="font-size:.85rem;font-style:italic;font-weight:700" lang="gn">{{ $d['volver']['gn'] }}</span>
    </a>

    <div class="mt-3">
        <a href="{{ route('estudiante.progreso') }}" class="me-3 fw-bold" style="color:#1B3361">{{ $enl['progreso']['es'] }}
            <small class="d-block fst-italic" style="color:#0E6B7D" lang="gn">{{ $enl['progreso']['gn'] }}</small></a>
        <a href="{{ route('estudiante.logros') }}" class="fw-bold" style="color:#1B3361">{{ $enl['logros']['es'] }}
            <small class="d-block fst-italic" style="color:#0E6B7D" lang="gn">{{ $enl['logros']['gn'] }}</small></a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var caja = document.getElementById('descanso');
    var resto = parseInt(caja.dataset.vuelveEn, 10) || 0;
    var fin = Date.now() + resto * 1000;
    var minEs = document.getElementById('descansoMinEs');
    var minGn = document.getElementById('descansoMinGn');
    // Textos (es + gn) que manda el servidor; {n} = minutos que faltan.
    var TX = @json(['min' => $d['minutos'], 'listo' => $d['listo']]);
    function poner(t, n) {
        minEs.textContent = t.es.replace('{n}', n);
        minGn.textContent = t.gn.replace('{n}', n);
    }
    var volver = document.getElementById('descansoVolver');

    function tic() {
        var seg = Math.max(0, Math.round((fin - Date.now()) / 1000));
        if (seg <= 0) {
            poner(TX.listo, '');
            volver.classList.remove('d-none');
            clearInterval(id);
            return;
        }
        poner(TX.min, Math.max(1, Math.ceil(seg / 60)));
    }
    var id = setInterval(tic, 1000);
    tic();
})();
</script>
@endpush
