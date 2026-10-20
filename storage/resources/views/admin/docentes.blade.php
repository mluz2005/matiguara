@extends('layouts.app')
@section('titulo','Gestionar Docentes')
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-person-badge me-2"></i>Gestionar Docentes
    </h2>
  </div>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-3 py-2 mb-3">{{ $errors->first() }}</div>
  @endif

  <div class="row g-3">

    {{-- Formulario nuevo docente --}}
    <div class="col-md-5">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#0E6B7D">
            <i class="bi bi-person-plus-fill me-1"></i>Crear cuenta de docente
          </h6>
        </div>
        <div class="card-body p-3">
          <form method="POST" action="{{ route('admin.docentes.crear') }}">
            @csrf
            <div class="mb-2">
              <label class="form-label fw-semibold small">Nombre</label>
              <input type="text" name="nombre" class="form-control form-control-sm rounded-3"
                     value="{{ old('nombre') }}" required placeholder="Laura">
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Apellido</label>
              <input type="text" name="apellido" class="form-control form-control-sm rounded-3"
                     value="{{ old('apellido') }}" required placeholder="Vega">
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Correo electrónico</label>
              <input type="email" name="email" class="form-control form-control-sm rounded-3"
                     value="{{ old('email') }}" required placeholder="docente@escuela.edu.py">
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Contraseña (mín. 8 caracteres)</label>
              <input type="password" name="password" class="form-control form-control-sm rounded-3" required>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Confirmar contraseña</label>
              <input type="password" name="password_confirmation"
                     class="form-control form-control-sm rounded-3" required>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Institución</label>
              <select name="institucion_id" class="form-select form-select-sm rounded-3" required>
                <option value="">Seleccioná una institución</option>
                @foreach($instituciones as $inst)
                  <option value="{{ $inst->id }}">{{ $inst->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold small">Grado</label>
                <select name="grado" class="form-select form-select-sm rounded-3" required>
                  <option value="1">1° Grado</option>
                  <option value="2">2° Grado</option>
                  <option value="3">3° Grado</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold small">Turno</label>
                <select name="turno" class="form-select form-select-sm rounded-3" required>
                  <option value="mañana">Mañana</option>
                  <option value="tarde">Tarde</option>
                  <option value="noche">Noche</option>
                </select>
              </div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-sm fw-bold rounded-3"
                      style="background:#0E6B7D;color:#fff">
                <i class="bi bi-person-check me-1"></i>Crear docente
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Lista de docentes --}}
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#1B3361">
            <i class="bi bi-people-fill me-1"></i>Docentes activos ({{ $docentes->count() }})
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead style="background:#f8f9fa">
                <tr>
                  <th class="ps-3 py-2 small">Docente</th>
                  <th class="py-2 small">Email</th>
                  <th class="py-2 small">Asignación</th>
                  <th class="text-center py-2 small">Acción</th>
                </tr>
              </thead>
              <tbody>
                @forelse($docentes as $doc)
                <tr>
                  <td class="ps-3 fw-bold small">
                    {{ $doc->usuario?->apellido }}, {{ $doc->usuario?->nombre }}
                  </td>
                  <td class="small text-muted">{{ $doc->usuario?->email }}</td>
                  <td class="small">
                    @if($doc->asignacionActiva)
                      <span class="badge rounded-pill" style="background:#D5E8D4;color:#177040">
                        {{ $doc->asignacionActiva->grado }}° ·
                        {{ Str::limit($doc->asignacionActiva->institucion?->nombre ?? '', 20) }}
                      </span>
                    @else
                      <span class="text-muted">Sin asignación</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <form method="POST"
                          action="{{ route('admin.docentes.desactivar', $doc->id) }}"
                          onsubmit="return confirm('¿Desactivar este docente?')">
                      @csrf @method('PUT')
                      <button type="submit" class="btn btn-sm rounded-3"
                              style="background:#FDECEA;color:#C0392B;font-size:.875rem">
                        <i class="bi bi-person-x me-1"></i>Desactivar
                      </button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    No hay docentes registrados.
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
