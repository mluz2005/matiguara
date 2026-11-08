{{--
  Gráficos del grupo (panel principal). Recibe $grupo (GraficosService::delGrupo).
  Usa un solo color a propósito: el color no debe leerse como «bueno» o «malo» frente a la clase.
--}}
@php $apoyo = $grupo['apoyo']; $hayApoyo = $apoyo['pocos_aciertos'] || $apoyo['sin_jugar']; @endphp

<div class="row g-3 mb-3">

  {{-- % de aciertos por alumno --}}
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
        <h6 class="fw-bold mb-0" style="color:#1B3361"><i class="bi bi-bar-chart-fill me-1"></i>Aciertos por alumno</h6>
        <small class="text-muted">Porcentaje de respuestas correctas en todas sus sesiones. Orden alfabético.</small>
      </div>
      <div class="card-body p-3">
        @foreach($grupo['aciertos'] as $a)
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="small text-truncate" style="width:110px" title="{{ $a['nombre'] }}">{{ $a['nombre'] }}</div>
            <div class="flex-grow-1">
              @if($a['valor'] !== null)
                <div class="progress rounded-3" style="height:14px" role="progressbar"
                     aria-label="Aciertos de {{ $a['nombre'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $a['valor'] }}">
                  <div class="progress-bar rounded-3" style="width:{{ $a['valor'] }}%;background:#1B3361"></div>
                </div>
              @else
                <div class="rounded-3" style="height:14px;background:#EEF1F6"></div>
              @endif
            </div>
            <div class="small fw-bold text-end" style="width:84px">
              @if($a['valor'] !== null)
                {{ $a['valor'] }}% <span class="text-muted fw-normal">({{ $a['sesiones'] }})</span>
              @else
                <span class="text-muted fw-normal">sin datos</span>
              @endif
            </div>
          </div>
        @endforeach
        <p class="text-muted mb-0 mt-2" style="font-size:.78rem">Entre paréntesis, las sesiones completadas: con pocas sesiones el porcentaje todavía dice poco.</p>
      </div>
    </div>
  </div>

  {{-- Actividad de los últimos 14 días --}}
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
        <h6 class="fw-bold mb-0" style="color:#1B3361"><i class="bi bi-calendar-week me-1"></i>Quiénes jugaron cada día</h6>
        <small class="text-muted">Últimos {{ \App\Services\GraficosService::DIAS_ACTIVIDAD }} días, de {{ $grupo['total'] }} alumnos.</small>
      </div>
      <div class="card-body p-3">
        @include('docente.graficos._columnas', [
            'datos'  => $grupo['actividad'],
            'max'    => $grupo['total'],
            'uid'    => 'gc-actividad',
            'anchoVb' => 440,
            'titulo' => 'Cantidad de alumnos que jugaron cada día en las últimas dos semanas',
        ])
      </div>
    </div>
  </div>
</div>

{{-- Alumnos que podrían necesitar apoyo --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
    <h6 class="fw-bold mb-0" style="color:#1B3361"><i class="bi bi-life-preserver me-1"></i>Podrían necesitar apoyo</h6>
    <small class="text-muted">Es un aviso para mirar con más atención, no una calificación.</small>
  </div>
  <div class="card-body p-3">
    @if($apoyo['pocos_aciertos'])
      <p class="mb-2 small">
        <strong>Pocos aciertos en sus últimas sesiones</strong>
        <span class="text-muted">(menos del {{ \App\Services\GraficosService::UMBRAL_APOYO_PCT }}% en las últimas {{ \App\Services\GraficosService::SESIONES_APOYO }}, con al menos {{ \App\Services\GraficosService::MIN_SESIONES_APOYO }}):</span>
        {{ implode(', ', $apoyo['pocos_aciertos']) }}
      </p>
    @endif
    @if($apoyo['sin_jugar'])
      <p class="mb-2 small">
        <strong>Hace más de {{ \App\Services\GraficosService::DIAS_SIN_JUGAR }} días que no juegan:</strong>
        {{ implode(', ', $apoyo['sin_jugar']) }}
      </p>
    @endif
    @if(!$hayApoyo)
      <p class="mb-2 small">Por ahora nadie cumple estos criterios.</p>
    @endif
    @if($apoyo['sin_empezar'])
      <p class="mb-0 small text-muted">Todavía no empezaron a jugar: {{ implode(', ', $apoyo['sin_empezar']) }}</p>
    @endif
  </div>
</div>

{{-- En qué nivel está el grupo, por juego --}}
@if($grupo['niveles'])
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
    <h6 class="fw-bold mb-0" style="color:#1B3361"><i class="bi bi-layers me-1"></i>En qué nivel está el grupo</h6>
    <small class="text-muted">Nivel más alto que alcanzó cada alumno, dentro del grado que cursa hoy.</small>
  </div>
  <div class="card-body p-3">
    <div class="row g-3">
      @foreach($grupo['niveles'] as $i => $j)
        <div class="col-lg-4">
          <div class="fw-semibold small mb-1" style="color:#1B3361">{{ $j['juego'] }}</div>
          @include('docente.graficos._columnas', [
              'datos'  => collect($j['por_nivel'])->map(fn ($c, $nivel) => ['etiqueta' => (string) $nivel, 'cantidad' => $c])->values()->all(),
              'max'    => $grupo['total'],
              'uid'    => 'gc-nivel-' . $i,
              'titulo' => 'Cantidad de alumnos en cada nivel de ' . $j['juego'],
              'colorBarra' => '#0E6B7D',
              'anchoVb'    => 340,
          ])
          <div class="text-muted" style="font-size:.78rem">
            Número de nivel en el eje inferior.
            @if($j['sin_empezar'] > 0) Sin empezar este juego: {{ $j['sin_empezar'] }}. @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endif
