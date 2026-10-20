@extends('layouts.app')
@section('titulo','Progreso: '.$estudiante->nombre)
@section('contenido')
<div class="container py-3">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('docente.panel') }}">Panel</a></li>
      <li class="breadcrumb-item"><a href="{{ route('docente.estudiantes') }}">Estudiantes</a></li>
      <li class="breadcrumb-item active">{{ $estudiante->nombre_completo }}</li>
    </ol>
  </nav>

  {{-- Header estudiante --}}
  <div class="card border-0 shadow-sm rounded-4 p-3 mb-4"
       style="background:linear-gradient(135deg,#DAE8FC,#D5E8D4)">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-0" style="color:#1B3361">
          👤 {{ $estudiante->nombre_completo }}
        </h4>
        <small class="text-muted">
          Código: <code>{{ $estudiante->codigo_acceso }}</code>
          · {{ $grado }}° Grado · Año {{ date('Y') }}
        </small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('docente.estudiante.diagnostico', $estudiante->id) }}"
           class="btn btn-sm rounded-3 fw-bold"
           style="background:#0E6B7D;color:white">
          <i class="bi bi-robot me-1"></i>Solicitar diagnóstico IA
        </a>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- Progreso por nivel --}}
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#1B3361">
            <i class="bi bi-bar-chart-fill me-1"></i>Progreso por nivel
          </h6>
        </div>
        <div class="card-body p-3">
          @forelse($progresos as $prog)
            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
              <div style="min-width:120px">
                <small class="fw-bold">{{ $prog->juego->nombre_es }}</small><br>
                <small class="text-muted">Nivel {{ $prog->nivel->numero_nivel }}</small>
              </div>
              <div class="flex-grow-1">
                @php
                  $total=$prog->total_partidas;
                  $pct=$total>0?round(($prog->total_aciertos/($total*5))*100):0;
                  $pct=min(100,$pct);
                  $color=$pct>=70?'#177040':($pct>=40?'#8A5A00':'#C0392B');
                @endphp
                <div class="progress rounded-3" style="height:10px">
                  <div class="progress-bar rounded-3"
                       style="width:{{ $pct }}%;background:{{ $color }}"></div>
                </div>
                <small class="text-muted">{{ $prog->total_partidas }} partidas
                  · Mejor: {{ $prog->mejor_puntaje }} pts</small>
              </div>
              <div class="text-end" style="min-width:60px">
                @if($prog->nivel_desbloqueado)
                  <span class="badge rounded-pill bg-success">✓</span>
                @endif
              </div>
            </div>
          @empty
            <p class="text-muted text-center py-3 mb-0">
              <i class="bi bi-clock me-1"></i>Sin progreso registrado aún.
            </p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Panel lateral: sesiones recientes + logros --}}
    <div class="col-md-4">

      {{-- Sesiones recientes --}}
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#0E6B7D">
            <i class="bi bi-clock-history me-1"></i>Últimas sesiones
          </h6>
        </div>
        <div class="card-body p-3">
          @forelse($sesiones->take(5) as $ses)
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
              <div>
                <small class="fw-bold">{{ $ses->juego->nombre_es }}</small><br>
                <small class="text-muted">{{ $ses->fecha_inicio->format('d/m H:i') }}</small>
              </div>
              <span class="badge rounded-pill {{ $ses->nivel_superado ? 'bg-success' : 'bg-light text-dark' }}">
                {{ $ses->puntaje_obtenido }} pts
              </span>
            </div>
          @empty
            <p class="text-muted small text-center mb-0">Sin sesiones</p>
          @endforelse
        </div>
      </div>

      {{-- Logros --}}
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#8A5A00">
            <i class="bi bi-trophy me-1"></i>Logros obtenidos
          </h6>
        </div>
        <div class="card-body p-3">
          @forelse($logros as $l)
            <div class="d-flex align-items-center gap-2 py-1">
              <span style="font-size:1.4rem">🏅</span>
              <div>
                <small class="fw-bold">{{ $l->logro->nombre_es }}</small><br>
                <small class="text-muted">{{ $l->fecha_obtenido->format('d/m/Y') }}</small>
              </div>
            </div>
          @empty
            <p class="text-muted small text-center mb-0">Sin logros aún</p>
          @endforelse
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
