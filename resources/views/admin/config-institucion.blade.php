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

        {{-- Parámetro 4: Segundos extra en Flash Matemático --}}
        <div class="mb-4">
          <label class="form-label fw-bold" for="segundos_extra_flash" style="color:#1B3361">
            ⚡ Segundos extra en Flash Matemático
          </label>
          <div class="d-flex align-items-center gap-3">
            <input type="range" id="segundos_extra_flash_rango"
                   min="-5" max="20" step="1"
                   value="{{ old('segundos_extra_flash', $config->segundos_extra_flash ?? 0) }}"
                   class="form-range flex-grow-1"
                   oninput="document.getElementById('segundos_extra_flash').value=this.value">
            <input type="number" name="segundos_extra_flash" id="segundos_extra_flash"
                   min="-5" max="20" step="1" required
                   value="{{ old('segundos_extra_flash', $config->segundos_extra_flash ?? 0) }}"
                   class="form-control text-center fw-bold" style="max-width:90px"
                   oninput="document.getElementById('segundos_extra_flash_rango').value=this.value">
            <span class="text-muted">s</span>
          </div>
          @error('segundos_extra_flash')
            <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
          <div class="form-text">
            Se <strong>suma</strong> al tiempo que se ven los números en <strong>todos los niveles</strong>
            de Flash Matemático: cada unidad es 1 segundo. Con <strong>0</strong> quedan los tiempos
            actuales; un valor negativo los acorta (nunca baja de 2 segundos). Se aplica a los alumnos de
            esta institución en la próxima partida. <strong>Solo afecta a Flash Matemático</strong>.
            Por defecto: 0.
          </div>
        </div>

        {{-- Parámetro 5: Cortes del semáforo de «Mati y los números» --}}
        <div class="mb-4">
          <label class="form-label fw-bold" style="color:#1B3361">
            🦜 Cortes del semáforo de «Mati y los números»
          </label>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label small" for="tamizaje_corte_verde">Verde desde (% de aciertos)</label>
              <input type="number" name="tamizaje_corte_verde" id="tamizaje_corte_verde" min="2" max="100" step="1" required
                     value="{{ old('tamizaje_corte_verde', $config->tamizaje_corte_verde ?? 75) }}"
                     class="form-control fw-bold" style="max-width:120px">
              @error('tamizaje_corte_verde')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
              <label class="form-label small" for="tamizaje_corte_amarillo">Amarillo desde (% de aciertos)</label>
              <input type="number" name="tamizaje_corte_amarillo" id="tamizaje_corte_amarillo" min="1" max="99" step="1" required
                     value="{{ old('tamizaje_corte_amarillo', $config->tamizaje_corte_amarillo ?? 60) }}"
                     class="form-control fw-bold" style="max-width:120px">
              @error('tamizaje_corte_amarillo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="form-text">
            Con el promedio de aciertos del área más baja del niño: desde el corte del verde es <strong>verde</strong>,
            desde el del amarillo es <strong>amarillo</strong> y por debajo es <strong>rojo</strong>. El amarillo debe ser
            menor que el verde. <strong>Son cortes provisorios</strong>: conviene revisarlos con los datos del piloto.
            Se aplican a la próxima vez que se calcule el semáforo de cada niño. Por defecto: 75 y 60.
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
