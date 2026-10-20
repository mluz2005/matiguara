@extends('layouts.app')
@section('titulo', $modo === 'crear' ? 'Nueva Institución' : ($modo === 'editar' ? 'Editar Institución' : 'Instituciones'))
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-building me-2"></i>
      @if($modo === 'crear') Nueva institución
      @elseif($modo === 'editar') Editar institución
      @else Instituciones
      @endif
    </h2>
    @if($modo === 'lista')
      <a href="{{ route('admin.instituciones.create') }}"
         class="btn rounded-3 fw-bold" style="background:#1B3361;color:#fff">
        <i class="bi bi-plus-lg me-1"></i>Nueva institución
      </a>
    @endif
  </div>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-3 py-2 mb-3">{{ $errors->first() }}</div>
  @endif

  @if($modo === 'lista')
    {{-- LISTA --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <table class="table table-hover align-middle mb-0">
        <thead style="background:#1B3361;color:#fff">
          <tr>
            <th class="py-3 ps-3">Institución</th>
            <th>Código</th>
            <th>Ciudad</th>
            <th class="text-center">Docentes</th>
            <th class="text-center">Estado</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($instituciones as $inst)
          <tr>
            <td class="ps-3 fw-bold">{{ $inst->nombre }}</td>
            <td><code class="small">{{ $inst->codigo }}</code></td>
            <td class="small text-muted">{{ $inst->ciudad }}</td>
            <td class="text-center">
              <span class="badge rounded-pill" style="background:#DAE8FC;color:#1B3361">
                {{ $inst->asignaciones->where('activo',true)->count() }}
              </span>
            </td>
            <td class="text-center">
              @if($inst->activo)
                <span class="badge bg-success rounded-pill">Activa</span>
              @else
                <span class="badge bg-secondary rounded-pill">Inactiva</span>
              @endif
            </td>
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1">
                <a href="{{ route('admin.instituciones.edit', $inst->id) }}"
                   class="btn btn-sm rounded-3" style="background:#DAE8FC;color:#1B3361;font-size:.875rem">
                  <i class="bi bi-pencil me-1"></i>Editar
                </a>
                <a href="{{ route('admin.instituciones.config', $inst->id) }}"
                   class="btn btn-sm rounded-3" style="background:#EDE7F6;color:#7B1FA2;font-size:.875rem">
                  <i class="bi bi-gear me-1"></i>Config
                </a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              No hay instituciones registradas.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $instituciones->links() }}</div>

  @elseif($modo === 'crear')
    {{-- FORMULARIO CREAR --}}
    <div class="card border-0 shadow-sm rounded-4" style="max-width:520px">
      <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.instituciones.store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold small">Nombre completo</label>
            <input type="text" name="nombre" class="form-control rounded-3"
                   value="{{ old('nombre') }}" required
                   placeholder="Escuela Básica N° 1234 San Roque González">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Código único</label>
            <input type="text" name="codigo" class="form-control rounded-3"
                   value="{{ old('codigo') }}" required
                   placeholder="EEB-ENC-003" style="text-transform:uppercase">
            <div class="form-text">Formato sugerido: EEB-[CIUDAD]-[N°]</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Ciudad</label>
            <input type="text" name="ciudad" class="form-control rounded-3"
                   value="{{ old('ciudad') }}" required placeholder="Encarnación">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold small">Tipo</label>
            <select name="tipo" class="form-select rounded-3" required>
              <option value="EEB" selected>EEB — Educación Escolar Básica</option>
              <option value="Colegio">Colegio</option>
              <option value="Instituto">Instituto</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn fw-bold rounded-3" style="background:#1B3361;color:#fff">
              <i class="bi bi-building-add me-1"></i>Registrar institución
            </button>
          </div>
        </form>
      </div>
    </div>
    <a href="{{ route('admin.instituciones.index') }}" class="btn btn-sm mt-3 rounded-3"
       style="background:#f8f9fa;border:1px solid #dee2e6">
      <i class="bi bi-arrow-left me-1"></i>Volver a la lista
    </a>

  @elseif($modo === 'editar')
    {{-- FORMULARIO EDITAR --}}
    @php $inst = $instituciones->first() @endphp
    <div class="card border-0 shadow-sm rounded-4" style="max-width:520px">
      <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.instituciones.update', $inst->id) }}">
          @csrf @method('PUT')
          <div class="mb-3">
            <label class="form-label fw-semibold small">Nombre</label>
            <input type="text" name="nombre" class="form-control rounded-3"
                   value="{{ old('nombre', $inst->nombre) }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Código (solo lectura)</label>
            <input type="text" class="form-control rounded-3 bg-light"
                   value="{{ $inst->codigo }}" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Ciudad</label>
            <input type="text" name="ciudad" class="form-control rounded-3"
                   value="{{ old('ciudad', $inst->ciudad) }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Tipo</label>
            <select name="tipo" class="form-select rounded-3">
              @foreach(['EEB','Colegio','Instituto','Otro'] as $tipo)
                <option value="{{ $tipo }}" {{ $inst->tipo === $tipo ? 'selected' : '' }}>
                  {{ $tipo }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-4 form-check">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" name="activo" value="1"
                   id="activo" {{ $inst->activo ? 'checked' : '' }}>
            <label class="form-check-label small" for="activo">Institución activa</label>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn fw-bold rounded-3" style="background:#1B3361;color:#fff">
              <i class="bi bi-check2-circle me-1"></i>Guardar cambios
            </button>
          </div>
        </form>
      </div>
    </div>
    <a href="{{ route('admin.instituciones.index') }}" class="btn btn-sm mt-3 rounded-3"
       style="background:#f8f9fa;border:1px solid #dee2e6">
      <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
  @endif

</div>
@endsection
