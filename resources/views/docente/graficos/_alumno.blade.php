{{--
  Gráficos de un alumno (pantalla de progreso y de diagnóstico IA):
  evolución de aciertos por sesión y errores por tipo. Recibe $grafico (GraficosService::delAlumno).
--}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
    <h6 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-graph-up me-1"></i>Cómo viene jugando{{ isset($estudiante) ? ' ' . $estudiante->nombre : '' }}
    </h6>
    <small class="text-muted">
      @if($grafico['sesiones'] > 0)
        Últimas {{ $grafico['sesiones'] }} {{ $grafico['sesiones'] === 1 ? 'sesión completada' : 'sesiones completadas' }}.
      @else
        Todavía no completó ninguna sesión.
      @endif
    </small>
  </div>
  <div class="card-body p-3">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="fw-semibold small mb-1" style="color:#1B3361">Aciertos por sesión (%)</div>
        @if(count($grafico['puntos']) >= 2)
          @include('docente.graficos._linea', ['puntos' => $grafico['puntos'], 'uid' => 'gl-' . ($uid ?? 'a')])
        @elseif(count($grafico['puntos']) === 1)
          <p class="text-muted small mb-0">
            Hay una sola sesión ({{ $grafico['puntos'][0]['valor'] }}% de aciertos). Con dos o más sesiones se dibuja la evolución.
          </p>
        @else
          <p class="text-muted small mb-0">Se dibujará cuando complete sus primeras sesiones.</p>
        @endif
      </div>
      <div class="col-lg-5">
        <div class="fw-semibold small mb-1" style="color:#1B3361">Errores por tipo</div>
        @if($grafico['total_errores'] > 0)
          @php $tope = max(array_column($grafico['errores'], 'cantidad')); @endphp
          @foreach($grafico['errores'] as $e)
            <div class="mb-2">
              <div class="d-flex justify-content-between small">
                <span class="fw-semibold">{{ $e['etiqueta'] }}</span>
                <span>{{ $e['cantidad'] }}</span>
              </div>
              <div class="progress rounded-3" style="height:10px" role="progressbar"
                   aria-label="Errores de {{ $e['etiqueta'] }}" aria-valuemin="0" aria-valuemax="{{ $tope }}" aria-valuenow="{{ $e['cantidad'] }}">
                <div class="progress-bar rounded-3" style="width:{{ $tope > 0 ? round($e['cantidad'] / $tope * 100) : 0 }}%;background:#1B3361"></div>
              </div>
              <div class="text-muted" style="font-size:.78rem">{{ $e['ayuda'] }}</div>
            </div>
          @endforeach
        @else
          <p class="text-muted small mb-0">No registra errores en estas sesiones.</p>
        @endif
      </div>
    </div>
    <p class="text-muted mt-3 mb-0" style="font-size:.78rem">
      Son datos del juego, no una evaluación: sirven para mirar tendencias junto con lo que la docente observa en el aula.
    </p>
  </div>
</div>
