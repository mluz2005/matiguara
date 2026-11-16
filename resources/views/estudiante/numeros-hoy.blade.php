@extends('layouts.estudiante')

@section('titulo', 'Mati y los números — MatiGuarã')

{{--
    VISTA: estudiante/numeros-hoy.blade.php
    Mati invita a seguir otro día (RF-14: se sugiere un viaje por día) y a jugar otros juegos.
    El niño igual puede seguir si quiere: el servidor lo anota. Guaraní: BORRADOR a validar.
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/numeros.css') }}?v=1">
@endpush

@section('contenido')
<div class="nm-hoy">
    <img src="{{ asset('img/numeros/mati-despidiendo.webp') }}" alt="" width="190" height="190">
    <div class="nm-hoy-globo">
        <p class="es">¡Hoy ya hiciste un viaje! Mañana seguimos con <b>{{ $viaje['es'] }}</b>.</p>
        <p class="gn" lang="gn">Ko árape ejapóma peteĩ viaje! Ko'ẽrõ jaha jey.</p>
        <p class="es">Mientras tanto, ¡jugá otros juegos!</p>
    </div>
    <a class="nm-btn nm-btn-oro" href="{{ route('estudiante.juegos') }}">🎮 Jugar otros juegos</a>

    <form method="POST" action="{{ route('estudiante.numeros.iniciar', ['juego' => $juego->id, 'nivel' => $nivel->id]) }}">
        @csrf
        <input type="hidden" name="igual" value="1">
        <button type="submit" class="nm-btn-suave">Seguir igual</button>
    </form>
</div>
@endsection
