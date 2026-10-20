@extends('layouts.estudiante')
@section('titulo', 'Mis Logros — MatiGuará')

@section('contenido')

<h4 class="titulo-sticker mb-3">
    🏅 Mis Logros y Medallas
</h4>

{{-- Logros obtenidos --}}
@if($logrosObtenidos->isEmpty())
    <div class="card border-0 rounded-4 p-4 text-center"
         style="background:#FFF2CC">
        <div style="font-size:3rem">🌟</div>
        <h5 class="fw-bold mt-2" style="color:#8A5A00">
            ¡Todavía no tenés medallas!
        </h5>
        <p class="text-muted small">
            Jugá y cumplí desafíos para ganar tus primeras medallas.
        </p>
        <a href="{{ route('estudiante.juegos') }}"
           class="btn fw-bold rounded-3 mt-2"
           style="background:#8A5A00;color:#fff">
            🎮 ¡A ganar medallas!
        </a>
    </div>
@else
    {{-- Contador --}}
    <div class="text-center mb-3">
        <span class="badge rounded-pill px-3 py-2"
              style="background:#8A5A00;font-size:.95rem">
            🏅 {{ $logrosObtenidos->count() }} de {{ $totalLogros }} medallas
        </span>
    </div>

    {{-- Grid de logros obtenidos --}}
    <div class="row g-2 mb-4">
        @foreach($logrosObtenidos as $l)
            @php
                $colores = [
                    'comun' => ['#FFF2CC','#8A5A00'],
                    'raro'  => ['#DAE8FC','#1B3361'],
                    'epico' => ['#D5E8D4','#177040'],
                ];
                $c = $colores[$l->logro->rareza ?? 'comun'];
            @endphp
            <div class="col-4">
                <div class="card border-0 rounded-4 p-3 text-center h-100"
                     style="background:{{ $c[0] }};border:3px solid {{ $c[1] }} !important;box-shadow:0 4px 0 {{ $c[1] }}">
                    <div style="font-size:2.2rem">{{ $l->logro->emoji ?? '🏅' }}</div>
                    <div class="fw-bold mt-1" style="color:{{ $c[1] }};font-size:.95rem">
                        {{ $l->logro->nombre_es }}
                    </div>
                    <div class="fw-bold" style="color:{{ $c[1] }};font-size:.95rem">
                        {{ $l->logro->nombre_gn }}
                    </div>
                    <div class="mt-1" style="font-size:.875rem;color:#666">
                        {{ $l->fecha_obtenido->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Logros disponibles (todavía sin obtener) --}}
@if($logrosPendientes->isNotEmpty())
    <h6 class="fw-bold mb-2 titulo-sticker" style="font-size:1rem">
        🔒 Próximas medallas a desbloquear
    </h6>
    <div class="row g-2">
        @foreach($logrosPendientes as $logro)
            <div class="col-4">
                <div class="card border-0 rounded-4 p-3 text-center h-100"
                     style="background:#f8f9fa;border:2px solid #adb5bd !important">
                    <div style="font-size:2.2rem;filter:grayscale(1)">
                        {{ $logro->emoji ?? '🏅' }}
                    </div>
                    <div class="fw-bold mt-1" style="font-size:.875rem;color:#595959">
                        {{ $logro->nombre_es }}
                    </div>
                    <div style="font-size:.875rem;color:#595959">
                        {{ $logro->descripcion_es }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
