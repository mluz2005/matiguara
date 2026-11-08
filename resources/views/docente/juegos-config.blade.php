@extends('layouts.app')
@section('titulo','Configurar Juegos')
@section('contenido')
<div class="container py-3">
  <h2 class="fw-bold mb-1" style="color:#1B3361">
    <i class="bi bi-controller me-2"></i>Configurar Juegos
  </h2>
  <p class="text-muted mb-4">
    Habilitá o deshabilitá juegos para tu {{ $grado }}° Grado.
    Los juegos desactivados no aparecen en la pantalla del estudiante.
  </p>

  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif

  <form method="POST" action="{{ route('docente.juegos.guardar') }}">
    @csrf
    <div class="row g-3 mb-4">
      @foreach($juegos as $juego)
        @php $habilitado = $configuraciones->get($juego->id, true) @endphp
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 h-100"
               style="border-left:4px solid {{ $habilitado ? '#177040' : '#dee2e6' }} !important">
            <div class="form-check form-switch d-flex align-items-start gap-3">
              <input class="form-check-input mt-1" type="checkbox"
                     name="juegos[]" value="{{ $juego->id }}"
                     id="jue{{ $juego->id }}"
                     style="width:3em;height:1.5em"
                     {{ $habilitado ? 'checked' : '' }}>
              <label class="form-check-label" for="jue{{ $juego->id }}">
                <span class="fw-bold d-block">{{ $juego->nombre_es }}</span>
                <span class="fw-bold d-block">{{ $juego->nombre_gn }}</span>
                <small class="badge rounded-pill mt-1"
                       style="background:#DAE8FC;color:#1B3361">
                  {{ $juego->tipoMecanica->nombre ?? '—' }}
                </small>
              </label>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <button type="submit" class="btn fw-bold rounded-3 px-4"
            style="background:#1B3361;color:white">
      <i class="bi bi-check2-circle me-1"></i>Guardar configuración
    </button>
  </form>

  {{-- ── Tiempo de juego (todos los juegos) — RN-L10, RN-L11 ───────────────── --}}
  <hr class="my-4">
  <h4 class="fw-bold mb-1" style="color:#1B3361">
    <i class="bi bi-hourglass-split me-2"></i>Tiempo de juego
  </h4>
  <p class="text-muted mb-3">
    Para cuidar la salud de los niños, <b>todos los juegos</b> cortan después de un tiempo
    y piden un descanso. El niño termina el ejercicio que tiene en pantalla y pasa a
    la pantalla de descanso. Si ponés <b>0</b> se desactiva.
  </p>

  @if($errors->any())
    <div class="alert alert-danger rounded-3 py-2 mb-3">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('docente.juegos.tiempo') }}">
    @csrf
    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
          <label for="limite_sesion_min" class="fw-bold mb-1">Tiempo de juego por sesión (minutos)</label>
          <input type="number" min="0" max="60" step="1" class="form-control form-control-lg"
                 id="limite_sesion_min" name="limite_sesion_min"
                 value="{{ old('limite_sesion_min', $limiteMin) }}" required>
          <small class="text-muted mt-1">Recomendado: 8 minutos. Cuenta solo el tiempo dentro de los juegos.</small>
        </div>
      </div>
      <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
          <label for="pausa_entre_sesiones_min" class="fw-bold mb-1">Pausa entre sesiones (minutos)</label>
          <input type="number" min="0" max="240" step="5" class="form-control form-control-lg"
                 id="pausa_entre_sesiones_min" name="pausa_entre_sesiones_min"
                 value="{{ old('pausa_entre_sesiones_min', $pausaMin) }}" required>
          <small class="text-muted mt-1">Recomendado: 30 minutos. Es lo que debe esperar el mismo niño para volver a jugar.</small>
        </div>
      </div>
    </div>
    <button type="submit" class="btn fw-bold rounded-3 px-4"
            style="background:#177040;color:white">
      <i class="bi bi-check2-circle me-1"></i>Guardar tiempo de juego
    </button>
  </form>
</div>
@endsection
