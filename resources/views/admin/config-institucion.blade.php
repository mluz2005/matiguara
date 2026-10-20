@extends('layouts.app')
@section('titulo','Configuración — '.$inst->nombre)
@section('contenido')
<div class="container py-3" style="max-width:600px">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('admin.panel') }}">Admin</a></li>
      <li class="breadcrumb-item"><a href="{{ route('admin.instituciones.index') }}">Instituciones</a></li>
      <li class="breadcrumb-item active">Configuración</li>
    </ol>
  </nav>

  <h2 class="fw-bold mb-1" style="color:#1B3361">
    <i class="bi bi-gear me-2"></i>Configuración institucional
  </h2>
  <p class="text-muted mb-4">{{ $inst->nombre }}</p>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('admin.instituciones.config.guardar', $inst->id) }}">
        @csrf @method('PUT')

        {{-- Parámetro 1: Días de racha (RN-08) --}}
        <div class="mb-4">
          <label class="form-label fw-bold" style="color:#1B3361">
            🔥 Días sin jugar para reiniciar racha (RN-08)
          </label>
          <div class="d-flex align-items-center gap-3">
            <input type="range" name="dias_reinicio_racha_default"
                   min="1" max="30" step="1"
                   value="{{ old('dias_reinicio_racha_default', $config->dias_reinicio_racha_default) }}"
                   class="form-range flex-grow-1"
                   oninput="document.getElementById('dias_val').textContent=this.value">
            <span class="fw-bold" style="color:#1B3361;min-width:40px;text-align:center">
              <span id="dias_val">{{ $config->dias_reinicio_racha_default }}</span>
            </span>
          </div>
          <div class="form-text">
            Días de inactividad que reinician la racha diaria del estudiante.
            <strong>Por defecto: 3 días</strong> (no penaliza el fin de semana).
          </div>
        </div>

        {{-- Parámetro 2: Acceso grados anteriores (RF-17) --}}
        <div class="mb-4">
          <label class="form-label fw-bold" style="color:#1B3361">
            📚 Permitir al estudiante ver progreso de grados anteriores (RF-17)
          </label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox"
                   name="permitir_acceso_grados_ant" value="1"
                   id="grados_ant"
                   {{ $config->permitir_acceso_grados_ant ? 'checked' : '' }}>
            <label class="form-check-label small" for="grados_ant">
              Activar acceso a grados anteriores
            </label>
          </div>
          <div class="form-text">
            Si está activado, el estudiante puede ver su progreso de grados previos.
            <strong>Por defecto: desactivado</strong>.
          </div>
        </div>

        {{-- Parámetro 3: Meses conservación egresados (RN-10) --}}
        <div class="mb-4">
          <label class="form-label fw-bold" style="color:#1B3361">
            🎓 Meses de conservación de cuentas de egresados (RN-10)
          </label>
          <div class="d-flex align-items-center gap-3">
            <input type="range" name="meses_conservacion_egresados"
                   min="1" max="12" step="1"
                   value="{{ old('meses_conservacion_egresados', $config->meses_conservacion_egresados) }}"
                   class="form-range flex-grow-1"
                   oninput="document.getElementById('meses_val').textContent=this.value">
            <span class="fw-bold" style="color:#1B3361;min-width:40px;text-align:center">
              <span id="meses_val">{{ $config->meses_conservacion_egresados }}</span>
            </span>
          </div>
          <div class="form-text">
            Meses que se conserva la cuenta de un egresado de 3° grado antes de desactivarla.
            <strong>Por defecto: 4 meses</strong>.
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn fw-bold rounded-3"
                  style="background:#7B1FA2;color:#fff">
            <i class="bi bi-check2-circle me-1"></i>Guardar configuración
          </button>
        </div>
      </form>
    </div>
  </div>

  <a href="{{ route('admin.instituciones.index') }}"
     class="btn btn-sm mt-3 rounded-3"
     style="background:#f8f9fa;border:1px solid #dee2e6">
    <i class="bi bi-arrow-left me-1"></i>Volver a instituciones
  </a>
</div>
@endsection
