@extends('layouts.app')
@section('titulo','Mis Estudiantes')
@section('contenido')
<div class="container-fluid py-3">
<div class="row">
  <div class="col-lg-2 d-none d-lg-block">
    <div class="sidebar p-3">
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
  <div class="col-lg-10">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold mb-0" style="color:#1B3361">
        <i class="bi bi-people me-2"></i>Mis Estudiantes
        @if($asignacion)
          <span class="badge ms-2 rounded-pill"
                style="background:#1B3361;font-size:.875rem">
            {{ $grado }}° Grado
          </span>
        @endif
      </h2>
      <a href="{{ route('docente.cuentas') }}"
         class="btn btn-sm rounded-3 fw-bold"
         style="background:#177040;color:white">
        <i class="bi bi-person-plus me-1"></i>Nueva cuenta
      </a>
    </div>

    @if($estudiantes->isEmpty())
      <div class="card border-0 rounded-4 p-5 text-center shadow-sm">
        <i class="bi bi-people" style="font-size:3rem;color:#767676"></i>
        <p class="text-muted mt-3 mb-0">No hay estudiantes matriculados aún.</p>
        <a href="{{ route('docente.cuentas') }}" class="btn btn-sm mt-3 rounded-3"
           style="background:#1B3361;color:white">Crear la primera cuenta</a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle rounded-4 overflow-hidden shadow-sm">
          <thead style="background:#1B3361;color:white">
            <tr>
              <th class="py-3 ps-3">Estudiante</th>
              <th>Código</th>
              <th class="text-center">Sesiones</th>
              <th class="text-center">% Aciertos</th>
              <th class="text-center">Estado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($estudiantes as $est)
            <tr>
              <td class="ps-3 fw-bold">{{ $est->apellido }}, {{ $est->nombre }}</td>
              <td><code class="text-muted">{{ $est->codigo_acceso }}</code></td>
              <td class="text-center">
                <span class="badge bg-light text-dark">{{ $est->sesiones_total }}</span>
              </td>
              <td class="text-center">
                @php $pct=$est->porcentaje_ok; $color=$pct>=70?'#177040':($pct>=40?'#8A5A00':'#C0392B') @endphp
                <span class="fw-bold" style="color:{{ $color }}">{{ $pct }}%</span>
              </td>
              <td class="text-center">
                @if($est->activo)
                  <span class="badge rounded-pill bg-success">Activo</span>
                @else
                  <span class="badge rounded-pill bg-secondary">Inactivo</span>
                @endif
              </td>
              <td class="text-center">
                <a href="{{ route('docente.estudiante.progreso', $est->id) }}"
                   class="btn btn-sm rounded-3"
                   style="background:#DAE8FC;color:#1B3361">
                  <i class="bi bi-graph-up me-1"></i>Ver progreso
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
</div>
@endsection
