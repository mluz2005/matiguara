@extends('layouts.app')
@section('titulo','Gestionar Cuentas')
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-person-plus me-2"></i>Gestionar Cuentas
    </h2>
  </div>

  <div class="row g-3">

    {{-- Formulario nueva cuenta --}}
    <div class="col-md-5">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#177040">
            <i class="bi bi-person-plus-fill me-1"></i>Crear nueva cuenta
          </h6>
        </div>
        <div class="card-body p-3">
          @if(session('mensaje'))
            <div class="alert alert-success rounded-3 py-2 small">
              {{ session('mensaje') }}
            </div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger rounded-3 py-2 small">
              {{ $errors->first() }}
            </div>
          @endif

          <form method="POST" action="{{ route('docente.cuentas.crear') }}">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold small">Nombre</label>
              <input type="text" name="nombre" class="form-control rounded-3"
                     value="{{ old('nombre') }}" required placeholder="Ej: Ana">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Apellido</label>
              <input type="text" name="apellido" class="form-control rounded-3"
                     value="{{ old('apellido') }}" required placeholder="Ej: López">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">
                PIN (4 dígitos — elegir junto al estudiante)
              </label>
              <input type="text" name="pin" class="form-control rounded-3 fw-bold"
                     maxlength="4" inputmode="numeric" pattern="\d{4}"
                     placeholder="0000" required style="letter-spacing:8px;font-size:1.2rem">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Confirmar PIN</label>
              <input type="text" name="pin_confirmation" class="form-control rounded-3 fw-bold"
                     maxlength="4" inputmode="numeric" pattern="\d{4}"
                     placeholder="0000" required style="letter-spacing:8px;font-size:1.2rem">
            </div>
            <div class="d-grid">
              <button type="submit" class="btn fw-bold rounded-3"
                      style="background:#177040;color:white">
                <i class="bi bi-person-check me-1"></i>Crear cuenta y matrícula
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Lista de cuentas existentes --}}
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#1B3361">
            <i class="bi bi-people-fill me-1"></i>
            Cuentas registradas ({{ $matriculas->count() }})
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead style="background:#f8f9fa">
                <tr>
                  <th class="ps-3 py-2 small">Nombre</th>
                  <th class="py-2 small">Código</th>
                  <th class="text-center py-2 small">Estado</th>
                  <th class="text-center py-2 small">Acción</th>
                </tr>
              </thead>
              <tbody>
                @forelse($matriculas as $mat)
                  <tr>
                    <td class="ps-3 fw-bold small">
                      {{ $mat->estudiante->apellido }}, {{ $mat->estudiante->nombre }}
                    </td>
                    <td>
                      <code class="small">{{ $mat->estudiante->codigo_acceso }}</code>
                    </td>
                    <td class="text-center">
                      @if($mat->estudiante->activo)
                        <span class="badge rounded-pill bg-success">Activo</span>
                      @else
                        <span class="badge rounded-pill bg-secondary">Inactivo</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm rounded-3"
                              style="background:#FFF2CC;color:#8A5A00;font-size:.875rem"
                              data-bs-toggle="modal"
                              data-bs-target="#modalEditar{{ $mat->estudiante_id }}">
                        <i class="bi bi-pencil me-1"></i>Editar
                      </button>
                    </td>
                  </tr>

                  {{-- Modal editar --}}
                  <div class="modal fade" id="modalEditar{{ $mat->estudiante_id }}" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                      <div class="modal-content rounded-4 border-0">
                        <div class="modal-header border-0">
                          <h6 class="modal-title fw-bold">
                            Editar: {{ $mat->estudiante->nombre }}
                          </h6>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pt-0">
                          <form method="POST"
                                action="{{ route('docente.cuentas.editar', $mat->estudiante_id) }}">
                            @csrf @method('PUT')
                            <div class="mb-2">
                              <label class="form-label small fw-bold">Acción</label>
                              <select name="accion" class="form-select form-select-sm rounded-3"
                                      onchange="this.nextElementSibling.style.display=this.value==='reset_pin'?'block':'none'">
                                <option value="reset_pin">Cambiar PIN</option>
                                @if($mat->estudiante->activo)
                                  <option value="desactivar">Desactivar cuenta</option>
                                @else
                                  <option value="reactivar">Reactivar cuenta</option>
                                @endif
                              </select>
                              <div class="mt-2">
                                <input type="text" name="nuevo_pin"
                                       class="form-control form-control-sm rounded-3"
                                       maxlength="4" inputmode="numeric"
                                       placeholder="Nuevo PIN (4 dígitos)"
                                       style="letter-spacing:6px">
                              </div>
                            </div>
                            <div class="d-grid">
                              <button type="submit" class="btn btn-sm rounded-3"
                                      style="background:#1B3361;color:white">
                                Guardar cambio
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                      Sin cuentas registradas aún.
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
