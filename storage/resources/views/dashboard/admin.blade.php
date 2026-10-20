@extends('layouts.app')

@section('titulo', $titulo ?? 'Panel de Administración')

@section('contenido')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color:#1B3361">
            <i class="bi bi-gear-wide-connected me-2"></i>{{ $titulo ?? 'Panel de Administración' }}
        </h2>
        <span class="badge rounded-pill" style="background:#7B1FA2">Admin</span>
    </div>

    @if(isset($totalInstituciones))
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">Instituciones</div>
                        <h3 class="fw-bold">{{ $totalInstituciones }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        @foreach([
            ['Instituciones','admin.instituciones.index','building','1B3361'],
            ['Docentes','admin.docentes','person-badge','0E6B7D'],
            ['Juegos','admin.juegos','controller','177040'],
            ['Mensajes','admin.mensajes','chat-quote','8A5A00'],
        ] as [$lbl, $ruta, $icon, $color])
            <div class="col-md-3">
                <a href="{{ route($ruta) }}" class="card card-stat text-decoration-none h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-{{ $icon }}" style="font-size:2.5rem; color:#{{ $color }}"></i>
                        <h5 class="fw-bold mt-2" style="color:#{{ $color }}">{{ $lbl }}</h5>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
