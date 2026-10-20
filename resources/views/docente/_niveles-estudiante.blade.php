{{--
    PARCIAL: niveles del alumno (2026-10-12)
    Se incluye al final de docente/progreso-estudiante.blade.php.
    Muestra, por juego, el estado de cada nivel del grado del alumno y permite
    ABRIR niveles cerrados (uno por uno, o todos los de un juego).
    Variables: $estudiante, $grado
--}}
@php
    $resumenNiveles = \App\Support\DesbloqueoNiveles::resumenPorJuego($estudiante->id, (int) $grado);
    $nombreAlumno   = $estudiante->nombre_completo;
@endphp

<div class="card border-0 shadow-sm rounded-4 mt-3">
  <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
    <h6 class="fw-bold mb-1" style="color:#1B3361">
      <i class="bi bi-unlock-fill me-1"></i>Niveles de {{ $estudiante->nombre }}
    </h6>
    <p class="mb-0" style="font-size:.95rem;color:#44546F">
      Los niveles se abren solos cuando el alumno supera el anterior. Si querés que un
      alumno avance más rápido (o quedó trabado), podés <strong>abrirle</strong> niveles acá.
      Los niveles que ya jugó siguen abiertos.
    </p>
  </div>

  <div class="card-body p-3">

    @if(session('niveles_msg'))
      <div class="alert rounded-3 py-2 mb-3" role="status"
           style="background:#D5E8D4;border:1px solid #177040;color:#0F4D2C">
        ✅ {{ session('niveles_msg') }}
      </div>
    @endif
    @if(session('niveles_error'))
      <div class="alert rounded-3 py-2 mb-3" role="alert"
           style="background:#FDE2E0;border:1px solid #C0392B;color:#7A1F16">
        {{ session('niveles_error') }}
      </div>
    @endif

    <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.95rem;color:#1B3361">
      <span><span class="badge" style="background:#177040">✓</span> superado</span>
      <span><span class="badge" style="background:#1B3361">⭐</span> abierto</span>
      <span><span class="badge" style="background:#595959">🔒</span> cerrado</span>
    </div>

    @forelse($resumenNiveles as $fila)
      @php
        $juego   = $fila['juego'];
        $hayCerrados = collect($fila['niveles'])->contains(fn ($n) => $n['estado'] === 'cerrado');
      @endphp
      <div class="py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <strong style="color:#1B3361">{{ $juego->nombre_es }}</strong>

          @if($hayCerrados)
            <form method="POST"
                  action="{{ route('docente.estudiante.juego.abrir-todos', ['id' => $estudiante->id, 'juego' => $juego->id]) }}"
                  onsubmit="return confirm({{ \Illuminate\Support\Js::from('¿Abrir TODOS los niveles de ' . $juego->nombre_es . ' para ' . $nombreAlumno . '?') }})">
              @csrf
              <button type="submit" class="btn btn-sm rounded-3 fw-bold"
                      style="background:#fff;border:2px solid #0E6B7D;color:#0A5160;min-height:38px">
                Abrir todos los niveles
              </button>
            </form>
          @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
          @foreach($fila['niveles'] as $item)
            @php
              $n = $item['nivel']; $estado = $item['estado'];
            @endphp

            @if($estado === 'cerrado')
              <form method="POST"
                    action="{{ route('docente.estudiante.nivel.abrir', ['id' => $estudiante->id, 'nivel' => $n->id]) }}"
                    onsubmit="return confirm({{ \Illuminate\Support\Js::from('¿Abrir el nivel ' . $n->numero_nivel . ' de ' . $juego->nombre_es . ' para ' . $nombreAlumno . '?') }})">
                @csrf
                <button type="submit" class="btn btn-sm rounded-3 fw-bold"
                        style="background:#EEF1F6;border:2px solid #595959;color:#1B3361;min-width:64px;min-height:44px"
                        title="Nivel {{ $n->numero_nivel }}: cerrado. Tocá para abrirlo.">
                  🔒 {{ $n->numero_nivel }}
                  <small class="d-block" style="font-weight:normal;font-size:.875rem">Abrir</small>
                </button>
              </form>
            @else
              <span class="rounded-3 fw-bold d-inline-flex flex-column align-items-center justify-content-center"
                    style="min-width:64px;min-height:44px;padding:4px 8px;
                           background:{{ $estado === 'superado' ? '#177040' : '#1B3361' }};color:#fff"
                    title="Nivel {{ $n->numero_nivel }}: {{ $estado === 'superado' ? 'superado' : 'abierto' }}">
                {{ $estado === 'superado' ? '✓' : '⭐' }} {{ $n->numero_nivel }}
                <small style="font-weight:normal;font-size:.875rem">{{ $estado === 'superado' ? 'Superado' : 'Abierto' }}</small>
              </span>
            @endif
          @endforeach
        </div>
      </div>
    @empty
      <p class="text-muted text-center py-3 mb-0">No hay juegos con niveles para este grado.</p>
    @endforelse
  </div>
</div>
