@extends('layouts.app')
@section('titulo','Vincular cuenta al hijo/a')
@section('contenido')
<div class="container py-4" style="max-width:480px">

  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div style="background:linear-gradient(135deg,#1B3361,#0E6B7D);padding:24px;text-align:center">
      <div style="font-size:3rem">👪</div>
      <h4 class="fw-bold mt-2 mb-1" style="color:#fff">Vincular con tu hijo/a</h4>
      <p class="mb-0 small" style="color:rgba(255,255,255,.92)">
        Ingresá el código de acceso que le dio la docente
      </p>
    </div>

    <div class="card-body p-4">

      @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 mb-3">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('padre.vincular.post') }}">
        @csrf

        <div class="mb-3">
          <label class="form-label fw-bold">Código de acceso del niño/a</label>
          <input type="text"
                 name="codigo_acceso"
                 class="form-control form-control-lg rounded-3 @error('codigo_acceso') is-invalid @enderror"
                 value="{{ old('codigo_acceso') }}"
                 placeholder="SAN-1A-007"
                 autocapitalize="characters"
                 autocomplete="off"
                 style="font-family:monospace;font-size:1.3rem;letter-spacing:3px"
                 required>
          <div class="form-text">
            📋 La docente te entregó este código al matricular a tu hijo/a.
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold">Tu relación con el/la estudiante</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="relacion"
                     value="padre" id="rel_padre" checked>
              <label class="form-check-label" for="rel_padre">Padre</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="relacion"
                     value="madre" id="rel_madre">
              <label class="form-check-label" for="rel_madre">Madre</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="relacion"
                     value="tutor" id="rel_tutor">
              <label class="form-check-label" for="rel_tutor">Tutor/a</label>
            </div>
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-lg fw-bold rounded-3"
                  style="background:#1B3361;color:#fff">
            <i class="bi bi-link-45deg me-1"></i>Vincular mi cuenta
          </button>
        </div>
      </form>
    </div>
  </div>

  <p class="text-center text-muted small mt-3">
    Esta vinculación es permanente. Si necesitás cambiarla, contactá a la docente.
  </p>
  <p class="text-center mt-2">
    <a href="{{ route('ayuda') }}#ingresar" style="color:#1B3361;font-size:1.1rem;font-weight:600">
      <i class="bi bi-question-circle me-1"></i>¿Necesitás ayuda? · Pytyvõ
    </a>
  </p>
</div>
@endsection
