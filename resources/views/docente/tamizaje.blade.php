@extends('layouts.app')
@section('titulo','Atención numérica')
@section('contenido')
@php use App\Support\Numeros\TextosTamizaje as TT; @endphp
<div class="container-fluid py-3">
<div class="row">

  <div class="col-lg-2 d-none d-lg-block">
    <div class="sidebar p-3">
      <nav class="nav flex-column gap-1">
        @foreach([
          ['docente.panel','grid-1x2','Mi Panel'],
          ['docente.estudiantes','people','Mis Estudiantes'],
          ['docente.tamizaje','bell','Atención numérica'],
          ['docente.juegos','controller','Configurar Juegos'],
          ['docente.cuentas','person-plus','Cuentas'],
        ] as [$ruta,$icon,$lbl])
          <a class="nav-link {{ request()->routeIs($ruta.'*') ? 'active' : '' }}" href="{{ route($ruta) }}">
            <i class="bi bi-{{ $icon }}"></i> {{ $lbl }}
          </a>
        @endforeach
      </nav>
    </div>
  </div>

  <div class="col-lg-10">
    <div class="mb-3">
      <h2 class="fw-bold mb-0" style="color:#1B3361">
        <i class="bi bi-bell me-2"></i>Atención numérica
        @if($asignacion)
          <span class="badge ms-2 rounded-pill" style="background:#1B3361;font-size:.875rem">Clase de {{ $asignacion->grado }}° Grado</span>
        @endif
      </h2>
      <div class="text-muted">Resultados de «Mati y los números»: una ayuda para saber a quién mirar con más atención.</div>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start" role="note">
      <i class="bi bi-info-circle-fill mt-1"></i>
      <div>{{ TT::AVISO_LEGAL }}</div>
    </div>

    {{-- Cuántos hay de cada color --}}
    <div class="row g-2 mb-4">
      @foreach([
        ['rojo', $cuentas['rojo']],
        ['amarillo', $cuentas['amarillo']],
        ['verde', $cuentas['verde']],
        [null, $cuentas['sin']],
      ] as [$color, $cantidad])
        @php $t = TT::semaforo($color); @endphp
        <div class="col-6 col-md-3">
          <div class="rounded-4 p-3 h-100" style="background:{{ $t['fondo'] }};color:{{ $t['color'] }}">
            <div class="fs-3 fw-bold lh-1">{{ $cantidad }}</div>
            <div class="small fw-semibold mt-1">{{ $t['icono'] }} {{ $t['etiqueta'] }}</div>
          </div>
        </div>
      @endforeach
    </div>

    @if($filas->isEmpty())
      <div class="card border-0 rounded-4 p-5 text-center shadow-sm">
        <i class="bi bi-people" style="font-size:3rem;color:#767676"></i>
        <p class="text-muted mt-3 mb-0">Todavía no hay estudiantes matriculados.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle shadow-sm rounded-4 overflow-hidden mb-2">
          <thead style="background:#1B3361;color:white">
            <tr>
              <th class="py-3 ps-3">Estudiante</th>
              <th class="text-center">Grado</th>
              <th>Semáforo</th>
              @foreach(TT::AREAS as $nombreArea)
                <th class="text-center small">{{ $nombreArea }}</th>
              @endforeach
              <th class="text-center">Ciclos</th>
              <th class="text-center">Ficha</th>
              <th class="text-center">Detalle</th>
            </tr>
          </thead>
          <tbody>
            @foreach($filas as $f)
              @php $t = TT::semaforo($f['semaforo']); @endphp
              <tr>
                <td class="ps-3 fw-bold">
                  {{ $f['estudiante']->apellido }}, {{ $f['estudiante']->nombre }}
                  @if(!empty($f['senales']))
                    <div class="small fw-normal text-muted">
                      @foreach($f['senales'] as $s)<span class="d-block">• {{ TT::SENALES[$s] ?? $s }}</span>@endforeach
                    </div>
                  @endif
                </td>
                <td class="text-center">{{ $f['grado'] }}.°</td>
                <td>
                  <span class="badge rounded-pill px-3 py-2" style="background:{{ $t['fondo'] }};color:{{ $t['color'] }};font-size:.85rem;white-space:normal">
                    {{ $t['icono'] }} {{ $t['etiqueta'] }}
                  </span>
                </td>
                @foreach(array_keys(TT::AREAS) as $clave)
                  @php $a = $f['areas'][$clave] ?? null; $ta = TT::semaforo($a['color'] ?? null); @endphp
                  <td class="text-center">
                    @if(($a['pct'] ?? null) !== null)
                      <span class="fw-bold" style="color:{{ $ta['color'] }}">{{ $a['pct'] }}%</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                @endforeach
                <td class="text-center"><span class="badge bg-light text-dark">{{ $f['ciclos'] }}</span></td>
                <td class="text-center">
                  @if($f['ficha'])<i class="bi bi-journal-check text-success" title="Tiene ficha de observación"></i>@else<span class="text-muted">—</span>@endif
                </td>
                <td class="text-center">
                  <a href="{{ route('docente.tamizaje.alumno', $f['estudiante']->id) }}" class="btn btn-sm rounded-3"
                     style="background:#DAE8FC;color:#1B3361">
                    <i class="bi bi-search me-1"></i>Ver
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="small text-muted">
        <strong>Cómo se calcula:</strong> hace falta que el niño complete al menos 2 ciclos (cada ciclo son 3 viajes).
        Se promedian los aciertos de los últimos ciclos en cada área y el color es el del área más baja:
        verde desde {{ $corteVerde }}%, amarillo desde {{ $corteAmarillo }}%, rojo por debajo.
        Los cortes son <strong>provisorios</strong> hasta tener datos del piloto, y la administración puede ajustarlos.
        No cuentan el calentamiento, los toques demasiado rápidos ni los viajes marcados como no válidos.
      </div>
    @endif
  </div>
</div>
</div>
@endsection
