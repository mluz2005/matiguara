@extends('layouts.app')
@section('titulo', 'Diagnóstico IA — ' . $estudiante->nombre_completo)

@section('contenido')
<div class="container py-3">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('docente.panel') }}">Panel</a></li>
      <li class="breadcrumb-item"><a href="{{ route('docente.estudiantes') }}">Estudiantes</a></li>
      <li class="breadcrumb-item">
        <a href="{{ route('docente.estudiante.progreso', $estudiante->id) }}">
          {{ $estudiante->nombre_completo }}
        </a>
      </li>
      <li class="breadcrumb-item active">Diagnóstico IA</li>
    </ol>
  </nav>

  {{-- Mensajes --}}
  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2 mb-3">{{ session('mensaje') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-warning rounded-3 py-2 mb-3">{{ $errors->first() }}</div>
  @endif

  <div class="row g-3">

    {{-- ══ PANEL IZQUIERDO: Solicitar diagnóstico ══ --}}
    <div class="col-md-5">

      {{-- Header del estudiante --}}
      <div class="card border-0 shadow-sm rounded-4 mb-3"
           style="background:linear-gradient(135deg,#D9F0F5,#DAE8FC)">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-3">
            <div style="width:46px;height:46px;border-radius:50%;
                        background:#0E6B7D;color:#fff;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.2rem;font-weight:500">
              {{ strtoupper(substr($estudiante->nombre,0,1)) }}
            </div>
            <div>
              <div class="fw-bold" style="color:#1B3361">{{ $estudiante->nombre_completo }}</div>
              <small class="text-muted">
                Código: <code>{{ $estudiante->codigo_acceso }}</code>
                · {{ $totalSesiones }} sesiones
              </small>
            </div>
          </div>
        </div>
      </div>

      {{-- Aviso RN-02 --}}
      <div class="card border-0 rounded-4 mb-3"
           style="background:#EDE7F6;border:1px solid #7B1FA2 !important">
        <div class="card-body p-3">
          <div class="d-flex gap-2">
            <span style="font-size:1.2rem">⚠️</span>
            <div>
              <div class="fw-bold small" style="color:#3C3489">Aviso importante (RN-02)</div>
              <div class="small" style="color:#534AB7;line-height:1.5">
                El diagnóstico describe <strong>patrones de desempeño matemático</strong>.
                No es un diagnóstico médico, clínico ni psicopedagógico.
                Para evaluaciones especializadas, consultar a un profesional.
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Aviso privacidad RN-01 --}}
      <div class="card border-0 rounded-4 mb-3"
           style="background:#D5E8D4;border:1px solid #177040 !important">
        <div class="card-body p-3">
          <div class="d-flex gap-2">
            <span style="font-size:1.2rem">🔒</span>
            <div class="small" style="color:#27500A;line-height:1.5">
              <strong>Privacidad garantizada (RN-01):</strong>
              La IA recibe solo datos numéricos anonimizados.
              Nunca se envía el nombre ni el código del estudiante.
            </div>
          </div>
        </div>
      </div>

      {{-- Formulario de solicitud --}}
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#0E6B7D">
            🤖 Solicitar diagnóstico IA
          </h6>
          <small class="text-muted">
            Solo bajo demanda explícita del docente (RN-05)
          </small>
        </div>
        <div class="card-body p-3">

          @if($totalSesiones < 1)
            <div class="alert alert-warning rounded-3 py-2 small">
              El estudiante necesita al menos 1 sesión completada para generar un diagnóstico.
            </div>
          @else
            <form method="POST"
                  action="{{ route('docente.estudiante.generar-diagnostico', $estudiante->id) }}">
              @csrf

              {{-- Filtro por juego (opcional) --}}
              <div class="mb-3">
                <label class="form-label fw-semibold small">
                  Analizar juego específico (opcional)
                </label>
                <select name="juego_id" class="form-select form-select-sm rounded-3">
                  <option value="">Todos los juegos</option>
                  @foreach($juegos as $juego)
                    <option value="{{ $juego->id }}">{{ $juego->nombre_es }}</option>
                  @endforeach
                </select>
                <div class="form-text">
                  Dejá vacío para analizar el desempeño global del estudiante.
                </div>
              </div>

              <div class="d-grid">
                <button type="submit"
                        class="btn fw-bold rounded-3"
                        style="background:#0E6B7D;color:#fff"
                        onclick="return confirm('¿Generar diagnóstico IA para {{ $estudiante->nombre }}?\n\nEsto consume tokens de la API. Continuá solo si es necesario.')">
                  <i class="bi bi-robot me-1"></i>
                  Generar diagnóstico
                </button>
              </div>
            </form>
          @endif

        </div>
      </div>

    </div>

    {{-- ══ PANEL DERECHO: Historial de diagnósticos ══ --}}
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3 d-flex justify-content-between">
          <h6 class="fw-bold mb-0" style="color:#1B3361">
            📋 Historial de diagnósticos
          </h6>
          <span class="badge rounded-pill" style="background:#1B3361">
            {{ $diagnosticos->count() }}
          </span>
        </div>
        <div class="card-body p-3">

          @forelse($diagnosticos as $diag)
            <div class="card border-0 rounded-4 mb-3"
                 style="background:{{ $diag->estado === 'exitoso' ? '#f8fff8' : '#fffdf0' }};
                        border-left:4px solid {{ $diag->estado === 'exitoso' ? '#177040' : '#8A5A00' }} !important">
              <div class="card-body p-3">

                {{-- Header del diagnóstico --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="fw-bold small" style="color:#1B3361">
                      📅 {{ $diag->fecha_generacion->format('d/m/Y \a \l\a\s H:i') }}
                    </div>
                    <small class="text-muted">
                      Solicitado por: Lic. {{ Auth::user()->apellido }}
                    </small>
                  </div>
                  <div class="text-end">
                    @if($diag->estado === 'exitoso')
                      <span class="badge bg-success rounded-pill">✅ IA</span>
                    @else
                      <span class="badge rounded-pill" style="background:#8A5A00">⚠️ Local</span>
                    @endif
                  </div>
                </div>

                {{-- Snapshot numérico --}}
                <div class="d-flex gap-2 mb-2 flex-wrap">
                  @if($diag->porcentaje_aciertos !== null)
                    <span class="badge rounded-pill"
                          style="background:#DAE8FC;color:#1B3361;font-size:.875rem">
                      📊 {{ $diag->porcentaje_aciertos }}% aciertos
                    </span>
                  @endif
                  @if($diag->puntaje_promedio !== null)
                    <span class="badge rounded-pill"
                          style="background:#FFF2CC;color:#8A5A00;font-size:.875rem">
                      ⭐ {{ $diag->puntaje_promedio }} pts/sesión
                    </span>
                  @endif
                  @if($diag->nivel_max_alcanzado !== null)
                    <span class="badge rounded-pill"
                          style="background:#D5E8D4;color:#177040;font-size:.875rem">
                      🎯 Nivel {{ $diag->nivel_max_alcanzado }}
                    </span>
                  @endif
                </div>

                {{-- Preview de fortalezas --}}
                @if($diag->fortalezas)
                  <div class="small text-muted mb-2"
                       style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                    <strong style="color:#177040">Fortalezas:</strong>
                    {{ $diag->fortalezas }}
                  </div>
                @endif

                {{-- Acciones --}}
                <a href="{{ route('docente.estudiante.ver-diagnostico', [$estudiante->id, $diag->id]) }}"
                   class="btn btn-sm rounded-3 fw-bold"
                   style="background:#DAE8FC;color:#1B3361;font-size:.875rem">
                  <i class="bi bi-eye me-1"></i>Ver completo
                </a>

              </div>
            </div>
          @empty
            <div class="text-center py-4">
              <div style="font-size:3rem">🤖</div>
              <p class="text-muted mt-2 mb-0">
                No hay diagnósticos generados aún.<br>
                <small>Usá el formulario de la izquierda para solicitar el primero.</small>
              </p>
            </div>
          @endforelse

        </div>
      </div>
    </div>

  </div>
</div>
@endsection
