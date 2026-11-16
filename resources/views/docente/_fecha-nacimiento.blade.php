{{-- Fecha de nacimiento OPCIONAL (día, mes, año). Solo sirve para el saludo de cumpleaños de Mati. --}}
@php
    $pref  = $pref ?? 'cr';
    $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
    $anioHoy = (int) date('Y');
    $vd = old('nac_dia'); $vm = old('nac_mes'); $va = old('nac_anio');
@endphp
<div class="mb-3">
  <label class="form-label fw-semibold small">
    Fecha de nacimiento <span class="text-muted fw-normal">(opcional)</span>
  </label>
  <div class="row g-2">
    <div class="col-3">
      <select name="nac_dia" id="{{ $pref }}NacDia" class="form-select rounded-3 px-2" aria-label="Día de nacimiento">
        <option value="">Día</option>
        @for($i = 1; $i <= 31; $i++)<option value="{{ $i }}" @selected((string) $vd === (string) $i)>{{ $i }}</option>@endfor
      </select>
    </div>
    <div class="col-5">
      <select name="nac_mes" id="{{ $pref }}NacMes" class="form-select rounded-3 px-2" aria-label="Mes de nacimiento">
        <option value="">Mes</option>
        @foreach($meses as $n => $nombreMes)<option value="{{ $n }}" @selected((string) $vm === (string) $n)>{{ $nombreMes }}</option>@endforeach
      </select>
    </div>
    <div class="col-4">
      <select name="nac_anio" id="{{ $pref }}NacAnio" class="form-select rounded-3 px-2" aria-label="Año de nacimiento">
        <option value="">Año</option>
        @for($a = $anioHoy - \App\Support\Cumpleanos::EDAD_MIN; $a >= $anioHoy - \App\Support\Cumpleanos::EDAD_MAX; $a--)
          <option value="{{ $a }}" @selected((string) $va === (string) $a)>{{ $a }}</option>
        @endfor
      </select>
    </div>
  </div>
  <div class="form-text">
    Sirve solo para que Mati lo salude el día de su cumpleaños. No sale del sistema ni se envía a ningún servicio externo.
  </div>
  @error('nac_dia')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>
