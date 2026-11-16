@extends('layouts.app')
@section('titulo','Atención numérica · ' . $estudiante->nombre)
@section('contenido')
@php
  use App\Support\Numeros\TextosTamizaje as TT;
  use App\Support\Numeros\TextosNumeros as TN;
  $t = TT::semaforo($resultado['semaforo']);
  $marcadas = old('observaciones', $ficha?->observaciones ?? []);
@endphp
<div class="container py-3" style="max-width:1000px">

  <a href="{{ route('docente.tamizaje') }}" class="btn btn-sm mb-3 rounded-3" style="background:#f8f9fa;border:1px solid #dee2e6">
    <i class="bi bi-arrow-left me-1"></i>Volver a Atención numérica
  </a>

  @if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  @endif

  <div class="mb-3">
    <h2 class="fw-bold mb-0" style="color:#1B3361">{{ $estudiante->apellido }}, {{ $estudiante->nombre }}</h2>
    <div class="text-muted">@if($grado){{ $grado }}.° grado · @endif«Mati y los números»</div>
  </div>

  <div class="alert alert-info d-flex gap-2 align-items-start" role="note">
    <i class="bi bi-info-circle-fill mt-1"></i><div>{{ TT::AVISO_LEGAL }}</div>
  </div>

  {{-- Semáforo --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <span class="badge rounded-pill px-4 py-3" style="background:{{ $t['fondo'] }};color:{{ $t['color'] }};font-size:1.05rem;white-space:normal">
          {{ $t['icono'] }} {{ $t['etiqueta'] }}
        </span>
        <div class="text-muted">
          {{ $t['texto'] }}
          @if(!empty($resultado['subio_por_senales']))
            <div class="small">Los aciertos están bien, pero hay señales extra (ver abajo).</div>
          @endif
        </div>
      </div>

      <div class="small text-muted mb-3">
        Ciclos completos: <strong>{{ $resultado['ciclos_completos'] }}</strong>
        @if($resultado['ciclos_considerados']) · se miraron los últimos <strong>{{ $resultado['ciclos_considerados'] }}</strong> @endif
        @if($resultado['motivo'] === 'sin_datos_confiables')
          <div class="mt-1" style="color:#7A4F00">Hay ciclos completos, pero sus viajes tuvieron muchos toques demasiado rápidos y no son confiables. Conviene que lo repita con calma.</div>
        @endif
      </div>

      <div class="row g-3">
        @foreach(TT::AREAS as $clave => $nombreArea)
          @php $a = $resultado['areas'][$clave]; $ta = TT::semaforo($a['color']); @endphp
          <div class="col-md-4">
            <div class="rounded-4 p-3 h-100" style="background:{{ $a['pct'] !== null ? $ta['fondo'] : '#F3F3F3' }}">
              <div class="fw-bold" style="color:#1B3361">{{ $nombreArea }}</div>
              <div class="fs-3 fw-bold lh-1 my-1" style="color:{{ $a['pct'] !== null ? $ta['color'] : '#555' }}">
                {{ $a['pct'] !== null ? $a['pct'] . '%' : '—' }}
              </div>
              <div class="small text-muted">
                @if($a['pct'] !== null)
                  {{ $ta['icono'] }} promedio de {{ $a['ciclos'] }} ciclos
                @else
                  Sin datos suficientes ({{ $a['ciclos'] }} de 2 ciclos)
                @endif
              </div>
              <div class="small text-muted mt-1">{{ TT::AREAS_AYUDA[$clave] }}</div>
            </div>
          </div>
        @endforeach
      </div>

      @if(!empty($resultado['senales']))
        <div class="mt-3">
          <div class="fw-semibold small">Señales extra</div>
          <ul class="mb-0 small">
            @foreach($resultado['senales'] as $s)<li>{{ TT::SENALES[$s] ?? $s }}</li>@endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>

  {{-- Ficha de observación --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
      <h5 class="fw-bold" style="color:#1B3361"><i class="bi bi-journal-text me-2"></i>Tus observaciones <span class="text-muted fw-normal fs-6">(opcional)</span></h5>
      <p class="small text-muted">Marcá lo que veas en el aula. Es solo para vos: no se muestra a la familia ni a otras personas, y no lleva el nombre del niño a ningún servicio externo.</p>
      <form method="POST" action="{{ route('docente.tamizaje.ficha', $estudiante->id) }}">
        @csrf
        @foreach(TT::FICHA as $clave => $etiqueta)
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="observaciones[]" value="{{ $clave }}" id="obs_{{ $clave }}" @checked(in_array($clave, (array) $marcadas, true))>
            <label class="form-check-label" for="obs_{{ $clave }}">{{ $etiqueta }}</label>
          </div>
        @endforeach
        <div class="mt-3">
          <label class="form-label small fw-semibold" for="nota">Nota corta</label>
          <textarea name="nota" id="nota" rows="2" maxlength="{{ TT::FICHA_NOTA_MAX }}" class="form-control rounded-3"
                    placeholder="Algo que quieras recordar (hasta {{ TT::FICHA_NOTA_MAX }} caracteres)">{{ old('nota', $ficha?->nota) }}</textarea>
        </div>
        <button class="btn mt-3 rounded-3 fw-bold" style="background:#1B3361;color:white">
          <i class="bi bi-check2-circle me-1"></i>Guardar observaciones
        </button>
      </form>
    </div>
  </div>

  {{-- Informe con ayuda de la IA --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3" id="informe">
    <div class="card-body p-4">
      <h5 class="fw-bold" style="color:#1B3361"><i class="bi bi-stars me-2"></i>Informe con ayuda de la IA</h5>
      <p class="small text-muted mb-2">
        Redacta un borrador con lo que se ve en los datos, ideas para el aula y un texto para conversar con la familia.
        A la IA se envían <strong>solo números anónimos</strong>: sin nombre, sin código, sin fecha de nacimiento y sin tus observaciones.
        Se genera únicamente cuando vos lo pedís.
      </p>

      @if($resultado['semaforo'] === null)
        <div class="alert alert-secondary small mb-0">El informe se puede pedir cuando el niño complete al menos 2 ciclos (6 viajes) con datos confiables.</div>
      @else
        <form method="POST" action="{{ route('docente.tamizaje.informe', $estudiante->id) }}">
          @csrf
          <button class="btn rounded-3 fw-bold" style="background:#1B3361;color:white" @disabled($espera > 0)
                  onclick="this.form.submit(); this.disabled = true; this.querySelector('span').textContent = 'Redactando…'; return false;">
            <i class="bi bi-magic me-1"></i><span>{{ $informe ? 'Pedir un informe nuevo' : 'Pedir informe' }}</span>
          </button>
          @if($espera > 0)<span class="small text-muted ms-2">Podés pedir otro en {{ $espera }} segundos.</span>@endif
        </form>
      @endif

      @if($informe)
        @php $nombrePila = $estudiante->nombre; @endphp
        <hr>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
          @if($informe->esDeLaIA())
            <span class="badge" style="background:#DAE8FC;color:#1B3361">Redactado con ayuda de la IA</span>
          @else
            <span class="badge" style="background:#FFF2CC;color:#7A4F00">Informe estándar (la IA no estuvo disponible)</span>
          @endif
          <span class="small text-muted">{{ $informe->generado_en?->format('d/m/Y H:i') }} · {{ $informe->ciclos }} ciclos completos</span>
        </div>

        <div class="alert alert-warning small" role="note">
          Es un <strong>borrador</strong>: leelo y adaptalo con tu criterio antes de usarlo o compartirlo. No es un diagnóstico.
        </div>

        <h6 class="fw-bold" style="color:#1B3361">Qué se ve</h6>
        <p style="white-space:pre-line">{{ $informe->lectura }}</p>

        <h6 class="fw-bold" style="color:#1B3361">Ideas para el aula</h6>
        <p style="white-space:pre-line">{{ $informe->actividades }}</p>

        <h6 class="fw-bold" style="color:#1B3361">Texto para conversar con la familia</h6>
        <p class="small text-muted mb-1">Podés editarlo acá mismo antes de copiarlo. No se guarda lo que cambies.</p>
        <textarea id="familiaTexto" class="form-control rounded-3" rows="7">{{ $informe->textoFamiliaPara($nombrePila) }}</textarea>
        <button type="button" id="btnCopiarFamilia" class="btn btn-sm mt-2 rounded-3" style="background:#DAE8FC;color:#1B3361">
          <i class="bi bi-clipboard-check me-1"></i><span>Copiar texto</span>
        </button>
      @endif
    </div>
  </div>

  {{-- Resumen para copiar --}}
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
      <h5 class="fw-bold" style="color:#1B3361"><i class="bi bi-clipboard me-2"></i>Resumen para copiar</h5>
      <p class="small text-muted">Para tenerlo a mano si conversás con la familia, con la dirección o con un profesional. Revisalo antes de compartirlo.</p>
      <textarea id="resumenTexto" class="form-control rounded-3" rows="8" readonly>{{ $resumen }}</textarea>
      <button type="button" id="btnCopiar" class="btn btn-sm mt-2 rounded-3" style="background:#DAE8FC;color:#1B3361">
        <i class="bi bi-clipboard-check me-1"></i><span>Copiar resumen</span>
      </button>
    </div>
  </div>

  {{-- Viajes y preguntas --}}
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
      <h5 class="fw-bold" style="color:#1B3361"><i class="bi bi-map me-2"></i>Viajes hechos</h5>
      @if($viajes->isEmpty())
        <p class="text-muted mb-0">Todavía no hizo ningún viaje.</p>
      @else
        <p class="small text-muted">Si un viaje se hizo en mal momento (se distrajo, hubo ruido, alguien lo ayudó), podés marcarlo como <strong>no válido</strong>: deja de contar para el semáforo y el niño lo vuelve a hacer.</p>
        @foreach($viajes as $v)
          @php
            $fecha = ($v->sesion?->fecha_fin ?? $v->sesion?->fecha_inicio ?? $v->created_at);
            $nv = TN::VIAJES[$v->viaje]['es'] ?? ('Viaje ' . $v->viaje);
          @endphp
          <details class="border rounded-3 mb-2 p-2" @if(!$v->valida) style="background:#FFF8F0" @endif>
            <summary class="d-flex flex-wrap gap-2 align-items-center" style="cursor:pointer">
              <strong>Ciclo {{ $v->ciclo }} · Viaje {{ $v->viaje }} · {{ $nv }}</strong>
              <span class="text-muted small">{{ $fecha?->format('d/m/Y H:i') }}</span>
              @if(!$v->completa)
                <span class="badge bg-secondary">Incompleto ({{ $v->respondidas }}/{{ $v->total_preguntas }})</span>
              @else
                <span class="badge bg-success">Completo</span>
                @if($v->pct_aciertos !== null)<span class="badge bg-light text-dark">{{ $v->pct_aciertos }}% de aciertos</span>@endif
              @endif
              @if(!$v->confiable)<span class="badge" style="background:#FFF2CC;color:#7A4F00">Muchos toques rápidos ({{ $v->pct_azar }}%)</span>@endif
              @if(!$v->valida)<span class="badge" style="background:#FADBD8;color:#9C2B1F">No válido</span>@endif
            </summary>

            <div class="mt-2">
              @if($v->completa)
                <form method="POST" action="{{ route('docente.tamizaje.validez', $v->id) }}" class="d-flex flex-wrap gap-2 align-items-center mb-2">
                  @csrf
                  @if($v->valida)
                    <input type="hidden" name="accion" value="invalidar">
                    <select name="motivo" class="form-select form-select-sm" style="max-width:260px" aria-label="Motivo">
                      @foreach(TT::MOTIVOS_NO_VALIDA as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-danger rounded-3" onclick="return confirm('¿Marcar este viaje como no válido? Deja de contar y el niño lo volverá a hacer.')">Marcar como no válido</button>
                  @else
                    <input type="hidden" name="accion" value="validar">
                    <span class="small text-muted">Motivo: {{ TT::MOTIVOS_NO_VALIDA[$v->motivo_no_valida ?? ''] ?? '—' }}</span>
                    <button class="btn btn-sm btn-outline-success rounded-3">Volver a contarlo</button>
                  @endif
                </form>
              @endif

              @if($v->filas->isEmpty())
                <div class="small text-muted">Sin respuestas guardadas.</div>
              @else
                <div class="table-responsive">
                  <table class="table table-sm align-middle small mb-0">
                    <thead><tr><th>Parte</th><th>Pregunta</th><th>Respondió</th><th>Correcta</th><th class="text-center">Resultado</th><th class="text-end">Tiempo</th><th>Notas</th></tr></thead>
                    <tbody>
                      @foreach($v->filas as $f)
                        <tr>
                          <td>{{ $f['parte'] }}</td>
                          <td>{{ $f['pregunta'] }} @if($f['fija'])<span class="badge bg-light text-dark">fija</span>@endif</td>
                          <td>{{ $f['respondio'] }}</td>
                          <td>{{ $f['correcta'] }}</td>
                          <td class="text-center">{!! $f['acierto'] ? '<span class="text-success fw-bold">✔ bien</span>' : '<span style="color:#9C2B1F" class="fw-bold">✘ error</span>' !!}</td>
                          <td class="text-end">{{ $f['segundos'] !== null ? $f['segundos'] . ' s' : '—' }}</td>
                          <td>@if($f['error']){{ $f['error'] }}@endif @if($f['azar'])<span class="text-muted">(muy rápido)</span>@endif</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </details>
        @endforeach
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  function conectar(idBoton, idTexto, rotulo) {
    var b = document.getElementById(idBoton), t = document.getElementById(idTexto);
    if (!b || !t) return;
    b.addEventListener('click', function () {
      var listo = function () { b.querySelector('span').textContent = '¡Copiado!'; setTimeout(function () { b.querySelector('span').textContent = rotulo; }, 2000); };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(t.value).then(listo, function () { t.select(); document.execCommand('copy'); listo(); });
      } else { t.select(); document.execCommand('copy'); listo(); }
    });
  }
  conectar('btnCopiar', 'resumenTexto', 'Copiar resumen');
  conectar('btnCopiarFamilia', 'familiaTexto', 'Copiar texto');
})();
</script>
@endpush
@endsection
