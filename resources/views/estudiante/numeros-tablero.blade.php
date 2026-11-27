@extends('layouts.estudiante')

@section('titulo', 'Mati y los números — MatiGuarã')

{{-- Este juego no lleva música de fondo: así el niño puede escuchar la voz de Mati y concentrarse. --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/numeros-tablero.blade.php — «Mati y los números» (tamizaje)
    =========================================================================
    Variables: $sesion, $juego, $config (las arma NumerosController::mostrarTablero).
    Toda la lógica vive en public/js/numeros.js y los estilos en public/css/numeros.css
    (aislados bajo #nm-root). La pantalla ocupa toda la ventana, como «El camino a la escuela».
    No hay puntos, estrellas ni resultados a la vista: solo preguntas y una figurita al final.
    Guaraní revisado (planilla Guarani_MatiYLosNumeros_revisado): los textos vienen de
    App\Support\Numeros\TextosNumeros y llegan a numeros.js por NM_CFG.tx.
    =========================================================================
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('css/numeros.css') }}?v=2">
<style>
    .decoracion-lateral,
    .header-estudiante,
    .franja-frutas,
    .nav-inferior { display: none !important; }
    body main.container-fluid {
        position: static !important; z-index: auto !important;
        max-width: none !important; padding: 0 !important; margin: 0 !important;
    }
    html, body { overflow: hidden; height: 100%; overscroll-behavior: none; }
</style>
@endpush

@section('contenido')
<div id="nm-root">
 <header class="nm-top">
  <button class="nm-ib" id="nm-salir" type="button" aria-label="Salir del juego">← <span>Salir</span></button>
  <div class="nm-prog" role="progressbar" aria-label="Avance del viaje" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i id="nm-prog-bar"></i></div>
  <button class="nm-ib" id="nm-voz" type="button" aria-pressed="true" aria-label="Voz de Mati">🔊</button>
 </header>

 <main class="nm-escena" id="nm-escena" aria-live="polite"></main>

 <div class="nm-aviso" id="nm-aviso" hidden role="status"></div>
</div>
@endsection

@push('scripts')
<script>window.NM_CFG = @json($config);</script>
<script src="{{ asset('js/numeros.js') }}?v=2"></script>
@endpush
