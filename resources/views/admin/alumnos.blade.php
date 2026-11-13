@extends('layouts.app')
@section('titulo','Alumnos')
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-people-fill me-2"></i>Alumnos
    </h2>
    <form method="GET" action="{{ route('admin.alumnos') }}" class="d-flex gap-2">
      <input type="search" name="q" value="{{ $busqueda }}" class="form-control form-control-sm rounded-3"
             placeholder="Buscar por nombre, apellido o código" style="min-width:260px">
      <button class="btn btn-sm rounded-3 fw-bold" style="background:#1B3361;color:#fff">Buscar</button>
    </form>
  </div>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-3 py-2 mb-3">{{ $errors->first() }}</div>
  @endif

  <div class="alert alert-light border rounded-3 py-2 small mb-3">
    <b>¿Cuenta repetida?</b> Las que aparecen con la etiqueta <span class="badge text-bg-warning">¿Repetido?</span>
    tienen el mismo nombre y apellido que otra. El botón <b>Eliminar</b> solo aparece en cuentas que
    <b>nunca jugaron</b> y no tienen diagnósticos. Si ya tienen partidas, la docente solo puede desactivarlas
    (así no se pierde el historial).
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead style="background:#f8f9fa">
            <tr>
              <th class="ps-3 py-2 small">Alumno</th>
              <th class="py-2 small">Código</th>
              <th class="py-2 small">Grado · Institución</th>
              <th class="py-2 small">Estado</th>
              <th class="text-center py-2 small">Partidas</th>
              <th class="text-center py-2 small">Acción</th>
            </tr>
          </thead>
          <tbody>
            @forelse($alumnos as $al)
              @php
                $clave = \App\Support\NombrePropio::clave($al->nombre . ' ' . $al->apellido);
                $sinActividad = $al->sesiones_count === 0 && $al->diagnosticos_count === 0;
              @endphp
              <tr>
                <td class="ps-3 fw-bold small">
                  {{ $al->apellido }}, {{ $al->nombre }}
                  @if(in_array($clave, $repetidos, true))
                    <span class="badge text-bg-warning ms-1">¿Repetido?</span>
                  @endif
                </td>
                <td class="small text-muted">{{ $al->codigo_acceso }}</td>
                <td class="small">
                  @if($al->matriculaActiva)
                    {{ $al->matriculaActiva->grado }}° · {{ \Illuminate\Support\Str::limit($al->matriculaActiva->institucion?->nombre ?? '', 24) }}
                  @else
                    <span class="text-muted">Sin matrícula activa</span>
                  @endif
                </td>
                <td class="small">
                  @if($al->activo)
                    <span class="badge rounded-pill" style="background:#D5E8D4;color:#177040">Activo</span>
                  @else
                    <span class="badge rounded-pill" style="background:#E5E5E5;color:#555">Inactivo</span>
                  @endif
                </td>
                <td class="text-center small">{{ $al->sesiones_count }}</td>
                <td class="text-center">
                  @if($sinActividad)
                    <form method="POST" action="{{ route('admin.alumnos.eliminar', $al->id) }}"
                          onsubmit="return confirm('¿Eliminar definitivamente la cuenta de {{ e($al->nombre_completo) }} ({{ e($al->codigo_acceso) }})? Esta acción no se puede deshacer.')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm rounded-3"
                              style="background:#FDECEA;color:#C0392B;font-size:.875rem">
                        <i class="bi bi-trash me-1"></i>Eliminar
                      </button>
                    </form>
                  @else
                    <span class="small text-muted" title="Tiene partidas o diagnósticos: solo se puede desactivar">Con actividad</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">No hay alumnos que coincidan.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
