@extends('layouts.app')
@section('titulo', 'Diagnóstico — ' . $estudiante->nombre)

@section('contenido')
<div class="container py-3">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('docente.panel') }}">Panel</a></li>
      <li class="breadcrumb-item"><a href="{{ route('docente.estudiantes') }}">Estudiantes</a></li>
      <li class="breadcrumb-item">
        <a href="{{ route('docente.estudiante.diagnostico', $estudiante->id) }}">
          Diagnóstico IA
        </a>
      </li>
      <li class="breadcrumb-item active">
        {{ $diagnostico->fecha_generacion->format('d/m/Y') }}
      </li>
    </ol>
  </nav>

  {{-- ══ HEADER DEL DIAGNÓSTICO ══ --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div style="background:linear-gradient(135deg,#0E6B7D,#0F6E56);padding:18px 22px">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <div class="small mb-1" style="color:rgba(255,255,255,.92)">
            🤖 Diagnóstico Pedagógico IA — API Claude
          </div>
          <div style="font-size:1.15rem;font-weight:500;color:#fff">
            {{ $estudiante->nombre_completo }}
          </div>
          <div class="small" style="color:rgba(255,255,255,.92)">
            {{ $diagnostico->fecha_generacion->format('d \d\e F \d\e Y, H:i') }}
            · Solicitado por: Lic. {{ Auth::user()->apellido }}
          </div>
        </div>
        <div class="text-end">
          <div class="rounded-3 px-3 py-2" style="background:rgba(255,255,255,.15)">
            <div class="small" style="color:rgba(255,255,255,.92)">ID anónimo (RN-01)</div>
            <div class="small" style="color:#D3F5E6;font-family:monospace">
              {{ substr($diagnostico->id_anonimizado, 0, 13) }}...
            </div>
          </div>
          @if($diagnostico->tokens_usados)
            <div class="small mt-1" style="color:rgba(255,255,255,.92)">
              {{ $diagnostico->tokens_usados }} tokens
            </div>
          @endif
          @if($diagnostico->estado === 'fallido')
            <div class="badge rounded-pill mt-1" style="background:#8A5A00">
              ⚠️ Análisis local
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ══ SNAPSHOT NUMÉRICO (RF-15) ══ --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
      <div class="small fw-bold text-muted mb-2" style="letter-spacing:.05em">
        INSTANTÁNEA AL MOMENTO DEL DIAGNÓSTICO
      </div>
      <div class="row g-2">

        <div class="col-4">
          <div class="rounded-4 p-3 text-center" style="background:#DAE8FC">
            <div style="font-size:1.6rem;font-weight:500;color:#1B3361">
              {{ $diagnostico->puntaje_promedio ?? '—' }}
            </div>
            <div class="small" style="color:#185FA5">Puntaje promedio</div>
            <div class="small" style="color:#185FA5;font-size:.875rem">por sesión</div>
          </div>
        </div>

        <div class="col-4">
          <div class="rounded-4 p-3 text-center" style="background:#D5E8D4">
            <div style="font-size:1.6rem;font-weight:500;color:#177040">
              {{ $diagnostico->porcentaje_aciertos !== null ? $diagnostico->porcentaje_aciertos.'%' : '—' }}
            </div>
            <div class="small" style="color:#3B6D11">% Aciertos</div>
            <div class="small" style="color:#3B6D11;font-size:.875rem">sobre total de intentos</div>
          </div>
        </div>

        <div class="col-4">
          <div class="rounded-4 p-3 text-center" style="background:#FFF2CC">
            <div style="font-size:1.6rem;font-weight:500;color:#8A5A00">
              @if($diagnostico->nivel_max_alcanzado)
                Niv. {{ $diagnostico->nivel_max_alcanzado }}
              @else
                —
              @endif
            </div>
            <div class="small" style="color:#854F0B">Nivel máximo</div>
            <div class="small" style="color:#854F0B;font-size:.875rem">al momento del análisis</div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ══ AVISO RN-02 ══ --}}
  <div class="card border-0 rounded-4 mb-3"
       style="background:#EDE7F6;border:1px solid #7B1FA2 !important">
    <div class="card-body p-3">
      <div class="d-flex gap-2 align-items-start">
        <span>⚠️</span>
        <div class="small" style="color:#534AB7;line-height:1.5">
          <strong style="color:#3C3489">Aviso (RN-02):</strong>
          Este informe describe patrones de desempeño matemático.
          No constituye un diagnóstico médico, clínico ni psicopedagógico.
          Para evaluaciones especializadas, consultar a un profesional habilitado.
        </div>
      </div>
    </div>
  </div>

  {{-- ══ CONTENIDO GENERADO POR IA ══ --}}
  <div class="row g-3 mb-3">

    {{-- Fortalezas --}}
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:4px;height:20px;background:#177040;border-radius:2px"></div>
            <h6 class="fw-bold mb-0" style="color:#177040">
              Fortalezas identificadas
            </h6>
          </div>
          <div class="rounded-3 p-3" style="background:#D5E8D4">
            <p class="mb-0 small" style="color:#27500A;line-height:1.7">
              {{ $diagnostico->fortalezas ?? 'Sin datos disponibles.' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Dificultades --}}
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:4px;height:20px;background:#8A5A00;border-radius:2px"></div>
            <h6 class="fw-bold mb-0" style="color:#8A5A00">
              Dificultades detectadas
            </h6>
          </div>
          <div class="rounded-3 p-3" style="background:#FFF2CC">
            <p class="mb-0 small" style="color:#633806;line-height:1.7">
              {{ $diagnostico->dificultades ?? 'Sin datos disponibles.' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Recomendación --}}
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:4px;height:20px;background:#1B3361;border-radius:2px"></div>
            <h6 class="fw-bold mb-0" style="color:#1B3361">
              Recomendación pedagógica
            </h6>
          </div>
          <div class="rounded-3 p-3" style="background:#DAE8FC">
            <p class="mb-0 small" style="color:#042C53;line-height:1.7">
              {{ $diagnostico->recomendacion ?? 'Sin datos disponibles.' }}
            </p>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ══ HISTORIAL COMPARATIVO ══ --}}
  @if($historial->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
        <h6 class="fw-bold mb-0" style="color:#1B3361">
          📈 Diagnósticos anteriores — comparativa
        </h6>
      </div>
      <div class="card-body p-3">
        <div class="d-flex gap-2 flex-wrap">

          {{-- Diagnóstico actual --}}
          <div class="rounded-3 p-2 text-center flex-grow-1"
               style="background:#D5E8D4;border:2px solid #177040;min-width:100px">
            <div class="small fw-bold" style="color:#177040">Actual</div>
            <div class="small" style="color:#27500A">
              {{ $diagnostico->fecha_generacion->format('d/m/Y') }}
            </div>
            <div class="fw-bold" style="color:#177040">
              {{ $diagnostico->porcentaje_aciertos ?? '—' }}%
            </div>
          </div>

          {{-- Historial --}}
          @foreach($historial as $h)
            <a href="{{ route('docente.estudiante.ver-diagnostico', [$estudiante->id, $h->id]) }}"
               class="rounded-3 p-2 text-center text-decoration-none flex-grow-1"
               style="background:var(--bs-light);border:1px solid #dee2e6;min-width:100px">
              <div class="small fw-bold text-muted">Anterior</div>
              <div class="small text-muted">
                {{ $h->fecha_generacion->format('d/m/Y') }}
              </div>
              <div class="fw-bold" style="color:#1B3361">
                {{ $h->porcentaje_aciertos ?? '—' }}%
              </div>
            </a>
          @endforeach

        </div>
        <div class="small text-muted text-center mt-2">
          Evolución del % de aciertos entre diagnósticos
        </div>
      </div>
    </div>
  @endif

  {{-- ══ ACCIONES ══ --}}
  <div class="d-flex gap-2 justify-content-between flex-wrap">
    <a href="{{ route('docente.estudiante.diagnostico', $estudiante->id) }}"
       class="btn btn-sm rounded-3"
       style="background:#f8f9fa;border:1px solid #dee2e6;color:#333">
      <i class="bi bi-arrow-left me-1"></i>Volver al historial
    </a>
    <div class="d-flex gap-2">
      <button onclick="window.print()"
              class="btn btn-sm rounded-3"
              style="background:#f8f9fa;border:1px solid #dee2e6">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
      <a href="{{ route('docente.estudiante.generar-diagnostico', $estudiante->id) }}"
         class="btn btn-sm rounded-3 fw-bold"
         style="background:#0E6B7D;color:#fff">
        <i class="bi bi-robot me-1"></i>Nuevo diagnóstico
      </a>
    </div>
  </div>

</div>
@endsection
