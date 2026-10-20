@extends('layouts.estudiante')

@section('titulo', '¡Terminaste!')

@section('contenido')

{{--
    VISTA: estudiante/resumen.blade.php
    =========================================================================
    Resumen al finalizar una sesión de juego.
    Muestra resultados con animaciones amigables para niños.

    Variables disponibles:
      $sesion       → SesionJuego completada
      $estudiante   → Estudiante autenticado
      $pctOk        → Porcentaje de aciertos (0-100)
      $racha        → Racha actual del juego (puede ser null)
      $logrosNuevos → Collection de LogroObtenido nuevos (Semana 3)
      $duracion     → Duración en segundos (puede ser null)

    CORRECCIÓN (Semana 8 — botón "Jugar de nuevo"):
      El botón "¡Jugar de nuevo!" posteaba siempre a la ruta genérica
      estudiante.sesion.iniciar (pensada para Suma de Frutas y Flash
      Matemático, que usan contenido_nivel). El juego de memoria
      ("Encuentra el par") no tiene registros en contenido_nivel — sus
      tarjetas viven en tarjetas_nivel — así que esa ruta genérica creaba
      una sesión nueva, no encontraba ejercicios, la cerraba al instante
      con 0 aciertos y devolvía a este mismo resumen vacío, sin dejar
      jugar de nuevo. Ahora el botón detecta si la sesión es del juego de
      memoria y, en ese caso, postea a su propia ruta
      (estudiante.memoria.iniciar), igual que ya se hace en
      estudiante/juegos.blade.php para decidir a qué pantalla enviar.
    =========================================================================
--}}

@php
    $esMemoria = $sesion->juego->tipoMecanica?->tipo === 'memoria';
@endphp

<div class="text-center py-2">

    {{-- Emoji y título según rendimiento --}}
    @if($pctOk >= 80)
        <div style="font-size:5rem; animation:aparecer .5s ease">🏆</div>
        <h2 class="fw-bold" style="color:#177040">¡Fantástico!</h2>
        <p class="text-muted fs-5">Sos un crack de las matemáticas.</p>
    @elseif($pctOk >= 50)
        <div style="font-size:5rem; animation:aparecer .5s ease">⭐</div>
        <h2 class="fw-bold" style="color:#8A5A00">¡Muy bien!</h2>
        <p class="text-muted fs-5">Cada vez lo hacés mejor.</p>
    @else
        <div style="font-size:5rem; animation:aparecer .5s ease">💪</div>
        <h2 class="fw-bold" style="color:#0E6B7D">¡Buen intento!</h2>
        <p class="text-muted fs-5">La práctica te hace más fuerte.</p>
    @endif

    {{-- Badge de nivel superado --}}
    @if($sesion->nivel_superado)
        <div class="mb-3">
            <span class="badge rounded-pill px-4 py-2 fs-5"
                  style="background:linear-gradient(135deg,#177040,#0E6B7D)">
                🎉 ¡Nivel {{ $sesion->nivel->numero_nivel }} superado!
            </span>
        </div>
    @endif

    {{-- Estadísticas de la sesión --}}
    <div class="row g-3 justify-content-center my-3">

        <div class="col-4">
            <div class="card border-0 rounded-4 p-3"
                 style="background:#D5E8D4">
                <div style="font-size:2rem">✅</div>
                <div class="fw-bold fs-3 mt-1" style="color:#177040">
                    {{ $sesion->aciertos }}
                </div>
                <small class="text-muted">Aciertos</small>
            </div>
        </div>

        <div class="col-4">
            <div class="card border-0 rounded-4 p-3"
                 style="background:#FFF2CC">
                <div style="font-size:2rem">⭐</div>
                <div class="fw-bold fs-3 mt-1" style="color:#8A5A00">
                    {{ $sesion->puntaje_obtenido }}
                </div>
                <small class="text-muted">Puntos</small>
            </div>
        </div>

        <div class="col-4">
            <div class="card border-0 rounded-4 p-3"
                 style="background:#DAE8FC">
                <div style="font-size:2rem">📊</div>
                <div class="fw-bold fs-3 mt-1" style="color:#1B3361">
                    {{ $pctOk }}%
                </div>
                <small class="text-muted">Aciertos</small>
            </div>
        </div>
    </div>

    {{-- Racha de días --}}
    @if($racha && $racha->racha_actual_dias > 0)
        <div class="mb-3">
            <span class="badge rounded-pill px-3 py-2 fs-6"
                  style="background:#8A5A00">
                🔥 {{ $racha->racha_actual_dias }} día{{ $racha->racha_actual_dias > 1 ? 's' : '' }}
                seguido{{ $racha->racha_actual_dias > 1 ? 's' : '' }} jugando
            </span>
        </div>
    @endif

    {{-- Logros nuevos (Semana 3 — placeholder) --}}
    @if($logrosNuevos->isNotEmpty())
        <div class="card border-0 rounded-4 p-3 mb-3"
             style="background:linear-gradient(135deg,#FFF2CC,#D5E8D4)">
            <h5 class="fw-bold" style="color:#8A5A00">🎖️ ¡Logros nuevos!</h5>
            @foreach($logrosNuevos as $logro)
                <div class="badge bg-warning text-dark m-1 p-2">
                    {{ $logro->logro->nombre_es }}
                </div>
            @endforeach
        </div>
    @endif

    {{-- Juego y nivel --}}
    <p class="text-muted small mt-2">
        <i class="bi bi-controller me-1"></i>
        {{ $sesion->juego->nombre_es }}
        · Nivel {{ $sesion->nivel->numero_nivel }}
        @if($duracion)
            · {{ gmdate('i:s', $duracion) }} minutos
        @endif
    </p>

    {{-- Botones de acción --}}
    <div class="row g-3 justify-content-center mt-1">

        {{-- Volver a jugar el mismo nivel --}}
        <div class="col-10 col-sm-5">
            <form method="POST"
                  action="{{ $esMemoria
                        ? route('estudiante.memoria.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $sesion->nivel_id])
                        : route('estudiante.sesion.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $sesion->nivel_id]) }}">
                @csrf
                <button type="submit"
                        class="btn w-100 fw-bold rounded-4 py-3"
                        style="background:#0E6B7D; color:white; font-size:1.1rem">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    ¡Jugar de nuevo!
                </button>
            </form>
        </div>

        {{-- Elegir otro juego --}}
        <div class="col-10 col-sm-5">
            <a href="{{ route('estudiante.juegos') }}"
               class="btn w-100 fw-bold rounded-4 py-3"
               style="background:#DAE8FC; color:#1B3361; border:2px solid #1B3361; font-size:1.1rem">
                <i class="bi bi-grid me-1"></i>
                Elegir otro juego
            </a>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
@keyframes aparecer {
    from { opacity:0; transform: scale(.5); }
    to   { opacity:1; transform: scale(1); }
}
</style>
@endpush
