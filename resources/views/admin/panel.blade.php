@extends('layouts.app')
@section('titulo','Administración — MatiGuará')
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-gear-wide-connected me-2"></i>Panel de Administración
    </h2>
    <span class="badge rounded-pill px-3 py-2" style="background:#7B1FA2;font-size:.875rem">
      Administradora
    </span>
  </div>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif

  {{-- 5 métricas --}}
  <div class="row g-3 mb-4">
    @foreach([
      ['🏫','Instituciones',    $stats['instituciones'],    '#1B3361','#DAE8FC'],
      ['👩‍🏫','Docentes activos',   $stats['docentes'],         '#0E6B7D','#D9F0F5'],
      ['🎓','Estudiantes',       $stats['estudiantes'],      '#177040','#D5E8D4'],
      ['🎮','Sesiones totales',  $stats['sesiones'],         '#8A5A00','#FFF2CC'],
      ['🤖','Diagnósticos IA',   $stats['diagnosticos'],     '#7B1FA2','#EDE7F6'],
    ] as [$em,$lbl,$val,$color,$bg])
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm rounded-4 p-3" style="background:{{ $bg }}">
        <div style="font-size:1.8rem">{{ $em }}</div>
        <div style="font-size:1.6rem;font-weight:500;color:{{ $color }}">{{ $val }}</div>
        <div class="small" style="color:{{ $color }}">{{ $lbl }}</div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Accesos rápidos --}}
  <div class="row g-3">
    @foreach([
      ['admin.instituciones.index','building-fill','Instituciones',
       'Registrar, editar y configurar instituciones educativas','#1B3361','#DAE8FC'],
      ['admin.docentes','person-badge-fill','Docentes',
       'Crear cuentas, asignar a instituciones y gestionar bajas','#0E6B7D','#D9F0F5'],
      ['admin.mensajes','chat-quote-fill','Mensajes bilingüe',
       'Catálogo de retroalimentación en castellano y guaraní','#177040','#D5E8D4'],
      ['admin.juegos','controller','Juegos',
       'Catálogo de juegos y niveles del sistema','#8A5A00','#FFF2CC'],
    ] as [$ruta,$icon,$titulo,$desc,$color,$bg])
    <div class="col-md-3">
      <a href="{{ route($ruta) }}"
         class="card border-0 shadow-sm rounded-4 text-decoration-none p-4 d-block h-100"
         style="background:{{ $bg }};transition:transform .2s"
         onmouseover="this.style.transform='translateY(-3px)'"
         onmouseout="this.style.transform='translateY(0)'">
        <i class="bi bi-{{ $icon }}" style="font-size:2.2rem;color:{{ $color }}"></i>
        <h6 class="fw-bold mt-2 mb-1" style="color:{{ $color }}">{{ $titulo }}</h6>
        <small class="text-muted">{{ $desc }}</small>
      </a>
    </div>
    @endforeach
  </div>
</div>
@endsection
