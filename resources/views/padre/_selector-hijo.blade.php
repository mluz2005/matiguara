{{-- Selector de hijo/a: solo aparece si la familia tiene más de un niño/a vinculado.
     Variables: $hijos (vínculos con estudiante), $estudiante (el que se está viendo),
     $rutaSelector (nombre de la ruta a la que vuelve: padre.panel o padre.logros) --}}
@if(isset($hijos) && $hijos->count() > 1)
  <div class="mb-3">
    <div class="small fw-bold mb-1" style="color:#1B3361">👧👦 Tus hijos/as — tocá un nombre para verlo:</div>
    <div class="d-flex flex-wrap gap-2">
      @foreach($hijos as $h)
        @php $activo = (int) $h->estudiante_id === (int) $estudiante->id; @endphp
        <a href="{{ route($rutaSelector ?? 'padre.panel', ['hijo' => $h->estudiante_id]) }}"
           class="btn rounded-pill fw-bold"
           style="font-size:1.05rem;padding:8px 18px;{{ $activo
              ? 'background:#1B3361;color:#fff;border:2px solid #1B3361'
              : 'background:#fff;color:#1B3361;border:2px solid #1B3361' }}"
           @if($activo) aria-current="true" @endif>
          {{ $h->estudiante->nombre }}
        </a>
      @endforeach
    </div>
  </div>
@endif
