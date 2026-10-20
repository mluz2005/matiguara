@extends('layouts.estudiante')

@section('titulo', '¡Terminaste!')

{{--
    VISTA: estudiante/resumen-v2.blade.php  (REDISEÑO VISUAL — los tres juegos)
    =========================================================================
    Reemplaza a resumen.blade.php. Recibe EXACTAMENTE las mismas variables
    ($sesion, $estudiante, $pctOk, $racha, $logrosNuevos, $duracion,
    $siguienteNivel, $nivelParaJugarDeNuevo, $cambioNivel, $nivelDestino) y
    conserva las mismas rutas y formularios (incluida la regla de
    "Encuentra el par": sube / baja / repite). Solo cambia cómo se ve.

    Guaraní NUEVO (borrador, validar con una persona hablante):
      ¡Iporãiterei! · ¡Iporã! · ¡Eñeha'ã jey!
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/memoria-v2.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/juegos-v2.css') }}?v=1">
@endpush

@section('contenido')

@php
    $esMemoria = $sesion->juego->tipoMecanica?->tipo === 'memoria';

    // Mundo (colores y escena) según el juego
    if ($esMemoria) {
        $mundoRes = (string) min(5, max(1, (int) ceil($sesion->nivel->numero_nivel / 2)));
        $escenaRes = ['🌴','🌊','🌻','🌈','🌸','⭐'];
    } elseif ($sesion->nivel->tiempo_mostrar_ms) {
        $mundoRes = 'F';
        $escenaRes = ['⚡','⭐','✨','💡','🚀','⚡'];
    } else {
        $mundoRes = 'S';
        $escenaRes = ['🌳','🐝','🍎','🌻','🐛','🦋'];
    }

    if ($pctOk >= 80)      { $estrellasRes = 3; $estadoRes = 'feliz'; $tituloEs = '¡Fantástico!'; $tituloGn = '¡Iporãiterei!'; $subRes = 'Sos un crack de las matemáticas.'; }
    elseif ($pctOk >= 50)  { $estrellasRes = 2; $estadoRes = 'feliz'; $tituloEs = '¡Muy bien!';    $tituloGn = '¡Iporã!';        $subRes = 'Cada vez lo hacés mejor.'; }
    else                   { $estrellasRes = 1; $estadoRes = 'idle';  $tituloEs = '¡Buen intento!'; $tituloGn = "¡Eñeha'ã jey!"; $subRes = 'La práctica te hace más fuerte.'; }
@endphp

<div class="mv2 mv2-res mv2-w{{ $mundoRes }}" id="mv2Res">

    <div class="mv2-escena" aria-hidden="true">
        @foreach($escenaRes as $i => $adorno)<span class="e{{ $i + 1 }}">{{ $adorno }}</span>@endforeach
    </div>

    <section class="mv2-res-cartel">
        <div class="guara-grande">
            <div class="mv2-guara" data-estado="{{ $estadoRes }}">@include('estudiante._mv2-guara')</div>
        </div>

        <h2>{{ $tituloEs }}</h2>
        <h2 class="gn">{{ $tituloGn }}</h2>
        <p class="sub">{{ $subRes }}</p>

        <div class="mv2-estrellas" role="img" aria-label="{{ $estrellasRes }} de 3 estrellas">
            @for($i = 0; $i < 3; $i++)
                <span class="mv2-estrella{{ $i < $estrellasRes ? ' gana' : '' }}" style="--i:{{ $i }}">⭐</span>
            @endfor
        </div>

        {{-- Badge de nivel superado --}}
        @if($sesion->nivel_superado)
            <div class="mv2-res-superado">🎉 ¡Nivel {{ $sesion->nivel->numero_nivel }} superado!</div>
        @endif

        {{-- Estadísticas de la sesión --}}
        <div class="mv2-res-stats">
            <div class="mv2-res-stat v"><div class="ico">✅</div><b>{{ $sesion->aciertos }}</b><span>Aciertos</span></div>
            <div class="mv2-res-stat a"><div class="ico">⭐</div><b>{{ $sesion->puntaje_obtenido }}</b><span>Puntos</span></div>
            <div class="mv2-res-stat c"><div class="ico">📊</div><b>{{ $pctOk }}%</b><span>Porcentaje</span></div>
        </div>

        {{-- Racha de días --}}
        @if($racha && $racha->racha_actual_dias > 0)
            <div class="mv2-res-extra">
                <span class="mv2-res-ficha">
                    🔥 {{ $racha->racha_actual_dias }} día{{ $racha->racha_actual_dias > 1 ? 's' : '' }}
                    seguido{{ $racha->racha_actual_dias > 1 ? 's' : '' }} jugando
                </span>
            </div>
        @endif

        {{-- Logros nuevos --}}
        @if($logrosNuevos->isNotEmpty())
            <div class="mv2-res-logros">
                <h5>🎖️ ¡Logros nuevos!</h5>
                <div class="mv2-res-extra" style="margin-top:0">
                    @foreach($logrosNuevos as $logro)
                        <span class="mv2-res-ficha">{{ $logro->logro->emoji ?? '🏅' }} {{ $logro->logro->nombre_es }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Juego y nivel --}}
        <p class="mv2-res-juego">
            <i class="bi bi-controller me-1"></i>
            {{ $sesion->juego->nombre_es }}
            · Nivel {{ $sesion->nivel->numero_nivel }}
            @if($duracion)
                · {{ gmdate('i:s', $duracion) }} minutos
            @endif
        </p>
    </section>

    {{-- Botones de acción --}}
    <div class="mv2-res-botones dos">

        {{-- Pasar al nivel siguiente (aparece cuando se superó este nivel y hay otro) --}}
        @if(!empty($siguienteNivel))
            <div class="ancho">
                <form method="POST"
                      action="{{ $esMemoria
                            ? route('estudiante.memoria.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $siguienteNivel->id])
                            : route('estudiante.sesion.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $siguienteNivel->id]) }}">
                    @csrf
                    <button type="submit" class="mv2-btn amarillo">
                        <i class="bi bi-skip-forward-fill me-1"></i>
                        ¡Siguiente nivel: {{ $siguienteNivel->numero_nivel }}!
                    </button>
                </form>
            </div>
        @endif

        {{-- Jugar de nuevo. En "Encuentra el par" el botón lleva al nivel que decidió la
             regla (3 ganadas seguidas sube, 2 perdidas seguidas baja); si todavía no
             se cumplió, repite el mismo nivel. --}}
        <div>
            <form method="POST"
                  action="{{ $esMemoria
                        ? route('estudiante.memoria.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $nivelParaJugarDeNuevo ?? $sesion->nivel_id])
                        : route('estudiante.sesion.iniciar', ['juego' => $sesion->juego_id, 'nivel' => $sesion->nivel_id]) }}">
                @csrf
                <button type="submit" class="mv2-btn verde">
                    @if(($cambioNivel ?? null) === 'sube')
                        <i class="bi bi-arrow-up-circle me-1"></i>
                        ¡Subir al nivel {{ $nivelDestino->numero_nivel }}!
                    @elseif(($cambioNivel ?? null) === 'baja')
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Practicar en el nivel {{ $nivelDestino->numero_nivel }}
                    @else
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        ¡Jugar de nuevo!
                    @endif
                </button>
            </form>
        </div>

        {{-- Elegir otro juego --}}
        <div>
            <a href="{{ route('estudiante.juegos') }}" class="mv2-btn celeste">
                <i class="bi bi-grid me-1"></i>
                Elegir otro juego
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Festejo (adorno): lluvia de frutas y estrellas si salió bien. Respeta "menos movimiento".
(function () {
    @if($pctOk >= 50)
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var f = ['🎉','⭐','🎈','✨','🍎','🍓','🍌','🍇'];
    var cartel = document.querySelector('.mv2-res-cartel');
    if (!cartel || !cartel.animate) return;
    var r = cartel.getBoundingClientRect();
    var cx = r.left + r.width / 2, cy = r.top + 40;
    var n = @json($pctOk >= 80 ? 40 : 26);
    for (var i = 0; i < n; i++) (function (i) {
        setTimeout(function () {
            var s = document.createElement('span');
            s.className = 'mv2-confeti';
            s.textContent = f[Math.floor(Math.random() * f.length)];
            s.style.left = cx + 'px'; s.style.top = cy + 'px';
            document.body.appendChild(s);
            var a = Math.random() * Math.PI - Math.PI, d = 90 + Math.random() * 220;
            var dx = Math.cos(a) * d, dy = Math.sin(a) * d - 20;
            var an = s.animate([
                { transform: 'translate(-50%,-50%) scale(.3)', opacity: 1 },
                { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px)) scale(1.1) rotate(160deg)', opacity: 1, offset: .5 },
                { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + (dy + 260) + 'px)) scale(.8) rotate(320deg)', opacity: 0 }
            ], { duration: 1500 + Math.random() * 700, easing: 'cubic-bezier(.2,.7,.4,1)' });
            an.onfinish = function () { s.remove(); };
        }, i * 35);
    })(i);
    @endif
})();
</script>
@endpush
