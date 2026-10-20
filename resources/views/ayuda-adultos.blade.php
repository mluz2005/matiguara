@extends('layouts.app')

@section('titulo', 'Ayuda')

{{--
    VISTA: ayuda-adultos.blade.php — Ayuda para docentes, familias y administración
    =========================================================================
    Muestra la guía del perfil de quien inició sesión. Los textos están en
    App\Support\AyudaAdultos (se corrigen ahí, sin tocar esta vista).
    La primera sección se ve abierta; las demás se abren al tocarlas.
    Al imprimir, se despliegan todas.
    Variables: $rol ('docente' | 'padre' | 'admin'), $titulo ['es','gn'], $secciones
    =========================================================================
--}}

@php
    // A las familias se les muestra con letra más grande y sin vocabulario técnico.
    $grande = $rol === 'padre';
@endphp

@section('contenido')
<div class="container py-4" style="max-width:820px">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-imprimir">
        <div>
            <h1 class="h3 fw-bold mb-0" style="color:#1B3361">
                <i class="bi bi-question-circle-fill me-1" aria-hidden="true"></i>
                {{ $titulo['es'] }}
            </h1>
            <div class="fw-semibold" style="color:#44546F">{{ $titulo['gn'] }}</div>
        </div>
        <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.print()">
            <i class="bi bi-printer me-1" aria-hidden="true"></i> Imprimir esta guía
        </button>
    </div>

    <p class="mb-3" style="color:#1F2937;font-size:{{ $grande ? '1.2rem' : '1.05rem' }}">
        Tocá cada título para ver los pasos.
    </p>

    <div class="accordion" id="acordeonAyuda">
        @foreach($secciones as $i => $sec)
            <div class="accordion-item rounded-3 mb-2 overflow-hidden" id="{{ $sec['id'] }}" style="border:2px solid #1B3361">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }} fw-bold"
                            type="button" data-bs-toggle="collapse"
                            data-bs-target="#panel-{{ $sec['id'] }}"
                            aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-controls="panel-{{ $sec['id'] }}"
                            style="font-size:{{ $grande ? '1.3rem' : '1.1rem' }};color:#1B3361">
                        <i class="bi bi-{{ $sec['icono'] }} me-2" aria-hidden="true"></i>
                        {{ $sec['titulo'] }}
                        @if(!empty($sec['gn']))
                            <span class="ms-2 fw-semibold" style="color:#44546F">· {{ $sec['gn'] }}</span>
                        @endif
                    </button>
                </h2>
                <div id="panel-{{ $sec['id'] }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                     data-bs-parent="#acordeonAyuda">
                    <div class="accordion-body" style="font-size:{{ $grande ? '1.2rem' : '1.05rem' }};color:#1F2937;line-height:1.55">
                        @if(!empty($sec['intro']))
                            <p>{!! \App\Support\AyudaAdultos::formatear($sec['intro']) !!}</p>
                        @endif

                        @if(!empty($sec['pasos']))
                            <ol class="ayuda-pasos ps-0 mb-2" style="list-style:none;counter-reset:paso">
                                @foreach($sec['pasos'] as $paso)
                                    <li class="d-flex gap-3 mb-2">
                                        <span class="ayuda-num-adulto" aria-hidden="true"></span>
                                        <span>{!! \App\Support\AyudaAdultos::formatear($paso) !!}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif

                        @if(!empty($sec['notas']))
                            <ul class="mb-0">
                                @foreach($sec['notas'] as $nota)
                                    <li class="mb-1">{!! \App\Support\AyudaAdultos::formatear($nota) !!}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 p-3 rounded-3" style="background:#EAF6FF;border:2px solid #1B3361;color:#1F2937;font-size:{{ $grande ? '1.15rem' : '1rem' }}">
        <strong>¿Sigue sin funcionar?</strong>
        @if(\App\Support\AcercaDe::CONTACTO)
            Escribinos a <a href="mailto:{{ \App\Support\AcercaDe::CONTACTO }}">{{ \App\Support\AcercaDe::CONTACTO }}</a>.
        @else
            Consultá con la docente.
        @endif
        <a href="{{ route('acerca') }}" class="ms-2">Acerca de MatiGuará</a>
    </div>
</div>
@endsection

@push('styles')
<style>
.ayuda-pasos { counter-reset: paso; }
.ayuda-num-adulto { counter-increment: paso; flex: 0 0 auto; width:34px; height:34px; border-radius:50%;
                    background:#1B3361; color:#fff; font-weight:800; display:inline-flex;
                    align-items:center; justify-content:center; }
.ayuda-num-adulto::before { content: counter(paso); }
.accordion-button:not(.collapsed) { background:#DAE8FC; color:#1B3361; box-shadow:none; }
.accordion-button:focus { box-shadow: 0 0 0 .25rem rgba(27,51,97,.35); }
html { scroll-behavior: smooth; }
/* Al abrir un enlace con ancla (ayuda#crear-cuenta) la sección queda a la vista */
.accordion-item { scroll-margin-top: 80px; }
@media print {
    .navbar, footer, .no-imprimir, .alert { display:none !important; }
    .accordion-collapse { display:block !important; height:auto !important; }
    .accordion-button::after { display:none; }
    .accordion-item { break-inside: avoid; }
}
</style>
@endpush

@push('scripts')
<script>
// Si se llega con un ancla (ej.: ayuda#crear-cuenta), se abre esa sección.
(function () {
    const id = (location.hash || '').replace('#', '');
    if (!id) return;
    const panel = document.getElementById('panel-' + id);
    if (panel && window.bootstrap) {
        bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
        document.getElementById(id).scrollIntoView();
    }
})();
</script>
@endpush
