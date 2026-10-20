@extends('layouts.app')
@section('titulo','Seguimiento de '.$estudiante->nombre)
@section('contenido')
<div class="container py-3" style="max-width:700px">

  @if(session('bienvenida'))
    <div class="alert alert-success rounded-4 text-center fw-bold mb-3">
      {{ session('bienvenida') }}
    </div>
  @endif

  {{-- Header del hijo --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div style="background:linear-gradient(135deg,#1B3361,#0E6B7D);padding:18px 20px">
      <div class="d-flex align-items-center gap-3">
        <div style="width:56px;height:56px;border-radius:50%;background:#DAE8FC;
                    display:flex;align-items:center;justify-content:center;
                    font-size:1.6rem;flex-shrink:0">
          {{ $vinculo->relacion === 'madre' ? '👩' : '👨' }}
        </div>
        <div>
          <div style="font-size:1.1rem;font-weight:500;color:#fff">
            {{ $estudiante->nombre_completo }}
          </div>
          <div class="small" style="color:rgba(255,255,255,.92)">
            @if($matricula)
              {{ $matricula->grado }}° Grado
              · {{ $matricula->institucion->nombre }}
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Mensaje del docente (si hay diagnóstico reciente) --}}
  @if($ultimoDiagnostico?->recomendacion)
    <div class="card border-0 rounded-4 mb-3"
         style="background:#D5E8D4;border-left:4px solid #177040 !important">
      <div class="card-body p-3">
        <div class="small fw-bold mb-1" style="color:#177040">
          📢 Nota de la docente ({{ $ultimoDiagnostico->fecha_generacion->format('d/m/Y') }})
        </div>
        <div class="small" style="color:#27500A;line-height:1.6">
         {{ $ultimoDiagnostico->recomendacion }}
        </div>
      </div>
    </div>
  @endif

  {{-- 3 métricas grandes y claras --}}
  <div class="row g-3 mb-3">
    <div class="col-4">
      <div class="card border-0 rounded-4 p-3 text-center" style="background:#DAE8FC">
        <div style="font-size:2rem">🎮</div>
        <div style="font-size:1.8rem;font-weight:500;color:#1B3361">{{ $totalSesiones }}</div>
        <div class="small" style="color:#185FA5">Veces que jugó</div>
      </div>
    </div>
    <div class="col-4">
      <div class="card border-0 rounded-4 p-3 text-center" style="background:#D5E8D4">
        <div style="font-size:2rem">✅</div>
        <div style="font-size:1.8rem;font-weight:500;color:#177040">{{ $porcentajeAciertos }}%</div>
        <div class="small" style="color:#3B6D11">Respuestas bien</div>
      </div>
    </div>
    <div class="col-4">
      <div class="card border-0 rounded-4 p-3 text-center" style="background:#FFF2CC">
        <div style="font-size:2rem">🔥</div>
        <div style="font-size:1.8rem;font-weight:500;color:#8A5A00">{{ $rachaMax }}</div>
        <div class="small" style="color:#854F0B">Días seguidos</div>
      </div>
    </div>
  </div>

  {{-- Últimas partidas --}}
  @if($ultimasSesiones->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
        <h6 class="fw-bold mb-0" style="color:#1B3361">
          🕹 Últimas partidas
        </h6>
      </div>
      <div class="card-body p-3">
        @foreach($ultimasSesiones as $ses)
          <div class="d-flex justify-content-between align-items-center py-2
               {{ !$loop->last ? 'border-bottom' : '' }}">
            <div>
              <div class="small fw-bold">{{ $ses->juego->nombre_es }}</div>
              <div class="small text-muted">
                {{ $ses->fecha_inicio->format('d/m H:i') }}
                · Nivel {{ $ses->nivel->numero_nivel }}
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold" style="color:#8A5A00">⭐ {{ $ses->puntaje_obtenido }}</div>
              @if($ses->nivel_superado)
                <div class="small text-success">¡Nivel superado!</div>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Logros --}}
  @if($logros->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3 d-flex justify-content-between">
        <h6 class="fw-bold mb-0" style="color:#8A5A00">🏅 Logros obtenidos</h6>
        <a href="{{ route('padre.logros') }}" class="small" style="color:var(--bs-link-color)">
          Ver todos →
        </a>
      </div>
      <div class="card-body p-3">
        <div class="row g-2">
          @foreach($logros->take(6) as $l)
            <div class="col-4">
              <div class="card border-0 rounded-4 p-2 text-center"
                   style="background:#FFF2CC;border:1.5px solid #8A5A00 !important">
                <div style="font-size:1.5rem">🥇</div>
                <div class="small fw-bold" style="color:#8A5A00;font-size:.875rem">
                  {{ $l->logro->nombre_es }}
                </div>
                <div style="font-size:.875rem;color:#854F0B">
                  {{ $l->fecha_obtenido->format('d/m/Y') }}
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  {{-- Nota informativa --}}
  <div class="card border-0 rounded-4 p-3"
       style="background:#f8f9fa;border:1px solid #dee2e6 !important">
    <div class="small text-muted text-center">
      <i class="bi bi-info-circle me-1"></i>
      Los datos se actualizan en tiempo real.
      Si tenés preguntas sobre el progreso, contactá a la docente.
    </div>
  </div>
</div>
@endsection
