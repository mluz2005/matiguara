@extends('layouts.estudiante')

@section('titulo', 'Mati y los números — MatiGuarã')

{{--
    VISTA: estudiante/numeros-hoy.blade.php
    Mati invita a seguir otro día (RF-14: se sugiere un viaje por día) y a jugar otros juegos.
    El niño igual puede seguir si quiere: el servidor lo anota. Textos en App\Support\Numeros\TextosNumeros::UI
    (guaraní revisado, planilla Guarani_MatiYLosNumeros_revisado).
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/numeros.css') }}?v=2">
@endpush

@section('contenido')
<div class="nm-hoy">
    <img src="{{ asset('img/numeros/mati-despidiendo.webp') }}" alt="" width="190" height="190">
    @php
        $u = \App\Support\Numeros\TextosNumeros::UI;
    @endphp
    <div class="nm-hoy-globo">
        <p class="es">{!! str_replace('{viaje}', '<b>' . e($viaje['es']) . '</b>', e($u['hoy1']['es'])) !!}</p>
        <p class="gn" lang="gn">{{ \App\Support\Numeros\TextosNumeros::rellenar($u['hoy1']['gn'], ['viaje' => $viaje['gn']]) }}</p>
        <p class="es">{{ $u['hoy2']['es'] }}</p>
        <p class="gn" lang="gn">{{ $u['hoy2']['gn'] }}</p>
    </div>
    <a class="nm-btn nm-btn-oro" href="{{ route('estudiante.juegos') }}">{{ $u['hoyJugar']['es'] }}<small class="nm-gn" lang="gn">{{ $u['hoyJugar']['gn'] }}</small></a>

    <form method="POST" action="{{ route('estudiante.numeros.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
        @csrf
        <input type="hidden" name="igual" value="1">
        <button type="submit" class="nm-btn-suave">{{ $u['hoySeguir']['es'] }}<small class="nm-gn" lang="gn">{{ $u['hoySeguir']['gn'] }}</small></button>
    </form>
</div>
@endsection
