@extends('layouts.app')
@section('titulo','Mensajes de Retroalimentación')
@section('contenido')
<div class="container py-3">

  <h2 class="fw-bold mb-4" style="color:#1B3361">
    <i class="bi bi-chat-quote me-2"></i>Mensajes de Retroalimentación Bilingüe
  </h2>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif

  <div class="row g-3">
    {{-- Formulario nuevo mensaje --}}
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#177040">Nuevo mensaje</h6>
        </div>
        <div class="card-body p-3">
          <form method="POST" action="{{ route('admin.mensajes.guardar') }}">
            @csrf
            <input type="hidden" name="id" id="msg_id">
            <div class="mb-2">
              <label class="form-label fw-semibold small">Tipo</label>
              <select name="tipo" class="form-select form-select-sm rounded-3">
                @foreach($tipos as $t)
                  <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">Contexto</label>
              <select name="contexto" class="form-select form-select-sm rounded-3">
                @foreach($contextos as $c)
                  <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold small">
                Castellano <small class="text-muted">(máx. 160 chars)</small>
              </label>
              <textarea name="texto_es" class="form-control form-control-sm rounded-3"
                        rows="2" maxlength="160" required
                        placeholder="¡Muy bien! Lo lograste. Seguí así."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">
                Guaraní <small class="text-muted">(máx. 160 chars)</small>
              </label>
              <textarea name="texto_gn" class="form-control form-control-sm rounded-3"
                        rows="2" maxlength="160" required
                        placeholder="¡Iporãve! Ejapo porã. Ejapyhy ag̃ague."></textarea>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-sm fw-bold rounded-3"
                      style="background:#177040;color:#fff">
                Guardar mensaje
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Lista de mensajes --}}
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
          @foreach($mensajes->groupBy('tipo') as $tipo => $grupo)
            <div class="mb-3">
              <div class="fw-bold small text-uppercase mb-2"
                   style="color:#1B3361;letter-spacing:.06em">{{ $tipo }}</div>
              @foreach($grupo as $msg)
                <div class="rounded-3 p-2 mb-2"
                     style="background:{{ $msg->activo ? '#f8f9fa' : '#fef' }};
                            border:1px solid {{ $msg->activo ? '#dee2e6' : '#fcc' }}">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="small"><strong>ES:</strong> {{ $msg->texto_es }}</div>
                      <div class="small"><strong>GN:</strong> {{ $msg->texto_gn }}</div>
                      <div class="small text-muted mt-1">
                        <span class="badge rounded-pill bg-light text-dark">{{ $msg->contexto }}</span>
                        @if(!$msg->activo)
                          <span class="badge rounded-pill bg-danger ms-1">Inactivo</span>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
