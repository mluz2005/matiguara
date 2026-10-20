@extends('layouts.estudiante')
@section('titulo', 'Mi Progreso — MatiGuará')

@section('contenido')

{{--
    VISTA: estudiante/progreso.blade.php — Semana 7
    Muestra el progreso real del estudiante por juego y nivel.
--}}

<h4 class="fw-bold mb-3" style="color:#1B3361">
    📊 Mi Progreso
</h4>

@if($progresos->isEmpty())
    <div class="card border-0 rounded-4 p-4 text-center"
         style="background:#DAE8FC">
        <div style="font-size:3rem">🌱</div>
        <h5 class="fw-bold mt-2" style="color:#1B3361">
            ¡Todavía no jugaste ninguna partida!
        </h5>
        <p class="text-muted small">
            Empezá jugando y acá vas a ver tu progreso.
        </p>
        <a href="{{ route('estudiante.juegos') }}"
           class="btn fw-bold rounded-3 mt-2"
           style="background:#1B3361;color:#fff">
            🎮 ¡A jugar!
        </a>
    </div>
@else

    {{-- Resumen general --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-0 rounded-4 p-2 text-center"
                 style="background:#D5E8D4">
                <div style="font-size:1.6rem;font-weight:500;color:#177040">
                    {{ $totalSesiones }}
                </div>
                <div class="small" style="color:#3B6D11">Partidas</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 rounded-4 p-2 text-center"
                 style="background:#DAE8FC">
                <div style="font-size:1.6rem;font-weight:500;color:#1B3361">
                    {{ $puntajeTotal }}
                </div>
                <div class="small" style="color:#185FA5">Puntos</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 rounded-4 p-2 text-center"
                 style="background:#FFF2CC">
                <div style="font-size:1.6rem;font-weight:500;color:#8A5A00">
                    {{ $rachaMax }}🔥
                </div>
                <div class="small" style="color:#854F0B">Racha</div>
            </div>
        </div>
    </div>

    {{-- Progreso por juego --}}
    @foreach($progresos->groupBy('juego_id') as $juegoId => $progresosJuego)
        @php $juego = $progresosJuego->first()->juego @endphp
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
                <h6 class="fw-bold mb-0" style="color:#1B3361;font-size:1.05rem">
                    🎮 {{ $juego->nombre_es }}
                </h6>
                <h6 class="fw-bold mb-0" style="color:#1B3361;font-size:1.05rem">
                    {{ $juego->nombre_gn }}
                </h6>
            </div>
            <div class="card-body p-3">
                @foreach($progresosJuego->sortBy('nivel.numero_nivel') as $prog)
                    <div class="d-flex align-items-center gap-3 py-2
                         {{ !$loop->last ? 'border-bottom' : '' }}">

                        {{-- Número de nivel --}}
                        <div class="rounded-3 fw-bold text-center"
                             style="width:36px;height:36px;line-height:36px;
                                    background:{{ $prog->nivel_desbloqueado ? '#1B3361' : '#dee2e6' }};
                                    color:{{ $prog->nivel_desbloqueado ? '#fff' : '#999' }};
                                    font-size:.9rem;flex-shrink:0">
                            {{ $prog->nivel->numero_nivel ?? '?' }}
                        </div>

                        {{-- Barra de progreso --}}
                        <div class="flex-grow-1">
                            @php
                                $pct = $prog->total_partidas > 0
                                    ? min(100, round(($prog->total_aciertos / ($prog->total_partidas * 5)) * 100))
                                    : 0;
                                $colorBarra = $pct >= 70 ? '#177040' : ($pct >= 40 ? '#8A5A00' : '#0E6B7D');
                            @endphp
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">
                                    Nivel {{ $prog->nivel->numero_nivel ?? '?' }}
                                    · {{ $prog->total_partidas }} partida{{ $prog->total_partidas != 1 ? 's' : '' }}
                                </small>
                                <small class="fw-bold" style="color:{{ $colorBarra }}">
                                    {{ $pct }}%
                                </small>
                            </div>
                            <div class="progress rounded-3" style="height:10px">
                                <div class="progress-bar rounded-3"
                                     style="width:{{ $pct }}%;background:{{ $colorBarra }}">
                                </div>
                            </div>
                            <small class="text-muted">
                                Mejor puntaje: ⭐ {{ $prog->mejor_puntaje }} pts
                            </small>
                        </div>

                        {{-- Estado --}}
                        <div style="flex-shrink:0">
                            @if($prog->nivel_desbloqueado)
                                <span style="font-size:1.3rem" title="Nivel desbloqueado">✅</span>
                            @else
                                <span style="font-size:1.3rem" title="Nivel bloqueado" aria-label="Nivel bloqueado">🔒</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

@endif

@endsection
