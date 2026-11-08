{{--
  Gráfico de columnas (dibujo SVG propio, sin librerías).
  Recibe: $datos = [['etiqueta'=>'24/10','cantidad'=>3], ...], $max (tope del eje, mínimo 1),
          $uid (id único), $titulo (texto para lectores de pantalla), $colorBarra y $anchoVb (opcionales).
--}}
@php
  $n = count($datos);
  $max = max(1, (int) $max);
  $colorBarra = $colorBarra ?? '#1B3361';
  $W = $anchoVb ?? 520; $H = 210; $L = 30; $R = 8; $T = 22; $B = 40;
  $pw = $W - $L - $R; $ph = $H - $T - $B;
  $slot = $pw / max(1, $n);
  $ancho = $slot * 0.62;
  $paso = $n > 8 ? 2 : 1;
@endphp
<svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" role="img" aria-labelledby="{{ $uid }}-t {{ $uid }}-d"
     style="max-width:640px;height:auto;display:block">
  <title id="{{ $uid }}-t">{{ $titulo }}</title>
  <desc id="{{ $uid }}-d">@foreach($datos as $d){{ $d['etiqueta'] }}: {{ $d['cantidad'] }}. @endforeach</desc>
  @foreach([0, $max] as $g)
    @php $y = $T + $ph * (1 - $g / $max); @endphp
    <line x1="{{ $L }}" y1="{{ $y }}" x2="{{ $W - $R }}" y2="{{ $y }}" stroke="#CFD8E6" stroke-width="1" @if($g !== 0) stroke-dasharray="4 4" @endif/>
    <text x="{{ $L - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="12" fill="#55627A">{{ $g }}</text>
  @endforeach
  @foreach($datos as $i => $d)
    @php
      $x  = $L + $i * $slot + ($slot - $ancho) / 2;
      $h  = $ph * min($d['cantidad'], $max) / $max;
      $yb = $T + $ph - $h;
    @endphp
    @if($d['cantidad'] > 0)
      <rect x="{{ round($x, 1) }}" y="{{ round($yb, 1) }}" width="{{ round($ancho, 1) }}" height="{{ round($h, 1) }}" rx="3" fill="{{ $colorBarra }}">
        <title>{{ $d['etiqueta'] }}: {{ $d['cantidad'] }}</title>
      </rect>
      <text x="{{ round($x + $ancho / 2, 1) }}" y="{{ round($yb, 1) - 5 }}" text-anchor="middle" font-size="12" font-weight="700" fill="{{ $colorBarra }}">{{ $d['cantidad'] }}</text>
    @endif
    @if($i % $paso === 0)
      <text x="{{ round($x + $ancho / 2, 1) }}" y="{{ $H - 18 }}" text-anchor="middle" font-size="12" fill="#55627A">{{ $d['etiqueta'] }}</text>
    @endif
  @endforeach
</svg>
