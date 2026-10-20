@extends('layouts.app')
@section('titulo','Panel Docente')
@section('contenido')
<div class="container-fluid py-3">
<div class="row">

  {{-- Sidebar --}}
  <div class="col-lg-2 d-none d-lg-block">
    <div class="sidebar p-3">
      <div class="text-muted small fw-bold px-2 mb-2">MENÚ DOCENTE</div>
      <nav class="nav flex-column gap-1">
        @foreach([
          ['docente.panel','grid-1x2','Mi Panel'],
          ['docente.estudiantes','people','Mis Estudiantes'],
          ['docente.juegos','controller','Configurar Juegos'],
          ['docente.cuentas','person-plus','Cuentas'],
        ] as [$ruta,$icon,$lbl])
          <a class="nav-link {{ request()->routeIs($ruta) ? 'active' : '' }}"
             href="{{ route($ruta) }}">
            <i class="bi bi-{{ $icon }}"></i> {{ $lbl }}
          </a>
        @endforeach
      </nav>
    </div>
  </div>

  {{-- Contenido --}}
  <div class="col-lg-10">

    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h2 class="fw-bold mb-0" style="color:#1B3361">
          <i class="bi bi-grid-1x2 me-2"></i>Mi Panel
        </h2>
        @if($asignacion)
          <small class="text-muted">
            <i class="bi bi-building me-1"></i>{{ $asignacion->institucion->nombre }}
            · {{ $asignacion->grado }}° Grado · Turno {{ ucfirst($asignacion->turno) }}
            · {{ $asignacion->anio_lectivo }}
          </small>
        @else
          <small style="color:#8A5A00">
            <i class="bi bi-exclamation-triangle me-1"></i>Sin asignación activa
          </small>
        @endif
      </div>
      <a href="{{ route('docente.cuentas') }}"
         class="btn btn-sm rounded-3 fw-bold"
         style="background:#1B3361;color:white">
        <i class="bi bi-person-plus me-1"></i>Nueva cuenta
      </a>
    </div>

    {{-- Métricas --}}
    @if($metricas)
    <div class="row g-3 mb-4">
      @foreach([
        ['students','bi-people-fill','Estudiantes',$metricas['total_estudiantes'],'#1B3361','#DAE8FC'],
        ['sessions','bi-controller','Sesiones totales',$metricas['total_sesiones'],'#0E6B7D','#D9F0F5'],
        ['pct','bi-bar-chart-fill','% Aciertos global',$metricas['pct_global'].'%','#177040','#D5E8D4'],
        ['today','bi-calendar-check','Activos hoy',$metricas['activos_hoy'],'#8A5A00','#FFF2CC'],
      ] as [$k,$icon,$lbl,$val,$color,$bg])
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100"
             style="border-left:4px solid {{ $color }} !important">
          <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="bi {{ $icon }}" style="color:{{ $color }};font-size:1.3rem"></i>
              <span class="text-muted small">{{ $lbl }}</span>
            </div>
            <h3 class="fw-bold mb-0" style="color:{{ $color }}">{{ $val }}</h3>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="row g-3">
      @foreach([
        ['docente.estudiantes','people-fill','Ver mis estudiantes','Ver progreso y métricas de cada alumno','#1B3361','#DAE8FC'],
        ['docente.juegos','controller','Configurar juegos','Habilitar o deshabilitar juegos por grado','#0E6B7D','#D9F0F5'],
        ['docente.cuentas','person-plus','Gestionar cuentas','Crear y editar cuentas de estudiantes','#177040','#D5E8D4'],
      ] as [$ruta,$icon,$titulo,$desc,$color,$bg])
      <div class="col-md-4">
        <a href="{{ route($ruta) }}"
           class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none p-3 d-block"
           style="background:{{ $bg }}; transition:transform .2s"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
          <i class="bi bi-{{ $icon }} mb-2 d-block" style="font-size:2rem;color:{{ $color }}"></i>
          <h6 class="fw-bold mb-1" style="color:{{ $color }}">{{ $titulo }}</h6>
          <small class="text-muted">{{ $desc }}</small>
        </a>
      </div>
      @endforeach
    </div>

  </div>
</div>
</div>
@endsection
