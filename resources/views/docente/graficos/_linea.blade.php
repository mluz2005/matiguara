{{--
  Gráfico de línea: % de aciertos por sesión (dibujo SVG propio, sin librerías).
  Recibe: $puntos = [['etiqueta'=>'24/10','valor'=>75,'detalle'=>'Suma de Frutas · nivel 2'], ...], $uid (id único).
--}}
@php
  $n = count($puntos);
  $W = 520; $H = 230; $L = 40; $R = 24; $T = 22; $B = 38; $in = 18;   // $in: margen para que no se corten el primer y el último punto
  $pw = $W - $L - $R; $ph = $H - $T - $B;
  $xs = []; $ys = [];
  foreach ($puntos as $i => $p) {
      $xs[$i] = $L + ($n > 1 ? $in + $i * ($pw - 2 * $in) / ($n - 1) : $pw / 2);
      $ys[$i] = $T + $ph * (1 - $p['valor'] / 100);
  }
  $linea = collect($puntos)->map(fn ($p, $i) => round($xs[$i], 1) . ',' . round($ys[$i], 1))->implode(' ');
  $paso  = $n > 7 ? 2 : 1;
@endphp
<svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" role="img" aria-labelledby="{{ $uid }}-t {{ $uid }}-d"
     style="max-width:640px;height:auto;display:block">
  <title id="{{ $uid }}-t">Porcentaje de aciertos en las últimas {{ $n }} sesiones</title>
  <desc id="{{ $uid }}-d">@foreach($puntos as $p){{ $p['etiqueta'] }}: {{ $p['valor'] }}%. @endforeach</desc>
  @foreach([0, 50, 100] as $g)
    @php $y = $T + $ph * (1 - $g / 100); @endphp
    <line x1="{{ $L }}" y1="{{ $y }}" x2="{{ $W - $R }}" y2="{{ $y }}" stroke="#CFD8E6" stroke-width="1" @if($g !== 0) stroke-dasharray="4 4" @endif/>
    <text x="{{ $L - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="12" fill="#55627A">{{ $g }}%</text>
  @endforeach
  <polyline fill="none" stroke="#1B3361" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $linea }}"/>
  @foreach($puntos as $i => $p)
    <circle cx="{{ round($xs[$i], 1) }}" cy="{{ round($ys[$i], 1) }}" r="4.5" fill="#fff" stroke="#1B3361" stroke-width="2.5">
      <title>{{ $p['etiqueta'] }} · {{ $p['detalle'] }}: {{ $p['valor'] }}% de aciertos</title>
    </circle>
    @if($n <= 10)
      <text x="{{ round($xs[$i], 1) }}" y="{{ round($ys[$i], 1) - 10 }}" text-anchor="middle" font-size="12" font-weight="700" fill="#1B3361">{{ $p['valor'] }}</text>
    @endif
    @if($i % $paso === 0 || $i === $n - 1)
      <text x="{{ round($xs[$i], 1) }}" y="{{ $H - 14 }}" text-anchor="middle" font-size="12" fill="#55627A">{{ $p['etiqueta'] }}</text>
    @endif
  @endforeach
</svg>
