@extends('layouts.estudiante')

@section('titulo', 'Encuentra el par — MatiGuará')

{{-- Tiene su propia música (MP3): el layout no arranca la generada por código --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/memoria-tablero-v2.blade.php  (REDISEÑO VISUAL)
    =========================================================================
    Recibe EXACTAMENTE las mismas variables que memoria-tablero.blade.php
    ($sesion, $nivel, $juego, $mazo, $totalPares, $tiempoLimiteSeg,
    $bonusTiempoSeg), así que el controlador no necesita ningún cambio de
    datos. La lógica del juego vive en public/js/memoria-v2.js y los estilos
    en public/css/memoria-v2.css (ambos nuevos y aislados).

    Mundos por nivel (cada 2 niveles): 1-2 Ka'aguy · 3-4 Ysyry · 5-6 Ñu ·
    7-8 Yvága · 9-10 Ñandutí. Los nombres en guaraní son borradores a validar.
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
@endpush

@section('contenido')

@php
    $mundo = min(5, max(1, (int) ceil($nivel->numero_nivel / 2)));
    $mundos = [
        1 => ['gn' => "Ka'aguy", 'es' => 'Selva',     'ico' => '🌴', 'esc' => ['🌴','🦋','🌺','🌿','🦜','🍃'], 'fruta' => '🍌'],
        2 => ['gn' => 'Ysyry',   'es' => 'Río',       'ico' => '🌊', 'esc' => ['🌊','🐟','🐢','🐠','☁️','🐊'], 'fruta' => '🍇'],
        3 => ['gn' => 'Ñu',      'es' => 'Campo',     'ico' => '🌻', 'esc' => ['🌻','🐄','🌾','☀️','🐴','🦋'], 'fruta' => '🍊'],
        4 => ['gn' => 'Yvága',   'es' => 'Cielo',     'ico' => '🌈', 'esc' => ['☁️','🌈','⭐','🪁','☁️','✨'], 'fruta' => '🍓'],
        5 => ['gn' => 'Ñandutí', 'es' => 'Encaje',    'ico' => '🕸️', 'esc' => ['🌸','✨','🦋','💜','🌺','⭐'], 'fruta' => '🍎'],
    ];
    $m = $mundos[$mundo];

    // Columnas/filas según la cantidad de cartas: [celular, escritorio]
    $n = count($mazo);
    [$colsM, $filasM, $colsD, $filasD] = match (true) {
        $n <= 4  => [2, 2, 4, 1],
        $n <= 6  => [3, 2, 3, 2],
        $n <= 8  => [4, 2, 4, 2],
        $n <= 10 => [4, 3, 5, 2],
        $n <= 12 => [4, 3, 6, 2],
        default  => [4, 4, 7, 2],
    };
    $cwMax = $n <= 4 ? 215 : ($n <= 6 ? 190 : 170);
@endphp

<div class="mv2 mv2-w{{ $mundo }}" id="mv2">

    {{-- Escena del mundo (fondo) --}}
    <div class="mv2-escena" aria-hidden="true">
        @foreach($m['esc'] as $i => $adorno)<span class="e{{ $i + 1 }}">{{ $adorno }}</span>@endforeach
    </div>

    {{-- ── HUD: mascota + globo · mundo/progreso/puntos · tiempo ───────── --}}
    <header class="mv2-hud">
        <div class="mv2-guara-wrap">
            <div class="mv2-guara" id="guara" data-estado="idle" title="¡Guará te alienta!">
                @include('estudiante._mv2-guara')
            </div>
            <div class="mv2-burbuja" id="burbuja" role="status" aria-live="polite">
                <b id="burbujaEs">¡Hola, {{ Auth::guard('estudiante')->user()->nombre ?? 'amigo' }}!</b>
                <i id="burbujaGn">Mba'éichapa!</i>
            </div>
        </div>

        <div class="mv2-info">
            <div class="mv2-mundo mv2-pegatina">
                <span class="ico" aria-hidden="true">{{ $m['ico'] }}</span>
                <span>{{ $m['gn'] }} <small>({{ $m['es'] }})</small> · Nivel {{ $nivel->numero_nivel }}</span>
                <span class="dif" aria-label="Dificultad {{ $nivel->dificultad }} de 5">{{ str_repeat('★', $nivel->dificultad) }}{{ str_repeat('☆', 5 - $nivel->dificultad) }}</span>
            </div>
            <div class="mv2-marcadores">
                <div class="mv2-progreso mv2-pegatina{{ $totalPares >= 5 ? ' muchos' : '' }}" id="mv2Progreso" role="img" aria-label="Pares encontrados">
                    @for($i = 0; $i < $totalPares; $i++)<span class="mv2-hueco"></span>@endfor
                    <span style="font-weight:800;margin-left:4px"><span id="paresEncontrados">0</span>/{{ $totalPares }}</span>
                </div>
                <div class="mv2-puntos mv2-pegatina" title="Puntos">⭐ <span id="puntajeActual">0</span><span class="mv2-combo" id="mv2Combo" hidden></span></div>
            </div>
        </div>

        <div class="mv2-tiempo mv2-pegatina" id="timerRing">
            <div class="mv2-tiempo-fila"><span id="timerEtiqueta">⏱ Tiempo</span><b id="timerNumero">{{ gmdate('i:s', $tiempoLimiteSeg) }}</b></div>
            <div class="mv2-barra"><div id="timerBarra"></div></div>
        </div>
    </header>

    {{-- ── Mesa ─────────────────────────────────────────────────────────── --}}
    <section class="mv2-mesa" id="mv2Mesa">
        <div class="mv2-tablero" id="tableroMemoria"
             style="--cw-max:{{ $cwMax }}px;--cols-m:{{ $colsM }};--filas-m:{{ $filasM }};--cols-d:{{ $colsD }};--filas-d:{{ $filasD }}">
            @foreach($mazo as $carta)
                @php $modo = $carta['modo_render']; $v = (int) $carta['valor']; @endphp
                <div class="carta-memoria mv2-carta" role="button" tabindex="0" aria-label="Carta boca abajo"
                     data-tarjeta-id="{{ $carta['tarjeta_id'] }}"
                     data-posicion="{{ $carta['posicion'] }}"
                     data-modo="{{ $modo }}"
                     data-valor="{{ $carta['valor'] }}"
                     data-emoji="{{ $carta['emoji'] }}"
                     data-es="{{ $carta['nombre_es'] }}"
                     data-gn="{{ $carta['nombre_gn'] }}">
                    <div class="carta-memoria-inner">
                        <div class="carta-cara carta-dorso">
                            <span class="mv2-dorso-logo">@include('estudiante._mv2-guara-cara')</span>
                        </div>
                        <div class="carta-cara carta-frente t-{{ $modo }}">
                            @if($modo === 'numeral')
                                <span class="mv2-numeral">{{ $carta['valor'] }}</span>
                            @elseif($modo === 'cantidad')
                                @if($v > 0)
                                    <span class="mv2-cantidad" style="--q:{{ $v <= 1 ? 1 : ($v <= 2 || $v === 4 ? 2 : 3) }}">
                                        @for($i = 0; $i < $v; $i++)<span>{{ $m['fruta'] }}</span>@endfor
                                    </span>
                                @else
                                    <span class="mv2-emoji">⭕</span>
                                @endif
                            @else
                                <span class="mv2-emoji">{{ $carta['emoji'] ?? '❓' }}</span>
                            @endif
                            <span class="mv2-cinta">{{ $carta['nombre_es'] }}@if($carta['nombre_gn'] && $carta['nombre_gn'] !== $carta['nombre_es'])<small>{{ $carta['nombre_gn'] }}</small>@endif</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ayuda corta (se oculta tras la primera jugada). El texto completo sigue en "¿Cómo se juega?" --}}
        <div class="mv2-ayuda mv2-pegatina" id="mv2Ayuda">
            👆 Volteá dos cartas y encontrá los pares iguales.
            <small>Embojere mokõi kuatia ha ejuhu umi ojoguáva.</small>
        </div>
    </section>

    {{-- Audio (los mismos archivos de siempre) --}}
    <audio id="musicaFondoJuego" loop preload="none">
        <source src="{{ asset('audio/musica/encuentra_el_par.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="sonidoAcierto" preload="auto">
        <source src="{{ asset('audio/efectos/memoria_acierto.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="sonidoError" preload="auto">
        <source src="{{ asset('audio/efectos/memoria_error.mp3') }}" type="audio/mpeg">
    </audio>
</div>

@endsection

@push('scripts')
<script>
window.MV2_CFG = {
    sesionId:        @json($sesion->id),
    totalPares:      @json($totalPares),
    tiempoLimiteSeg: @json($tiempoLimiteSeg),
    bonusTiempoSeg:  @json($bonusTiempoSeg),
    nivelNumero:     @json($nivel->numero_nivel),
    urlListaJuegos:  @json(route('estudiante.juegos')),
    urlMovimiento:   "/estudiante/memoria/sesion/{{ $sesion->id }}/movimiento",
    urlCerrar:       "/estudiante/memoria/sesion/{{ $sesion->id }}/cerrar",
    csrf:            document.querySelector('meta[name="csrf-token"]').content
};
</script>
<script src="{{ asset('js/memoria-v2.js') }}?v=1"></script>
@endpush
