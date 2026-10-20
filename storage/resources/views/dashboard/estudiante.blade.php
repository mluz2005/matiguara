@extends('layouts.estudiante')

@section('titulo', $titulo ?? '¡A Jugar!')

@section('contenido')

@if(isset($juegos) && $juegos->count() > 0)
    {{-- Vista de selección de juego --}}
    <h3 class="fw-bold text-center mb-4" style="color:#1B3361">
        🎮 ¿A qué querés jugar hoy?
    </h3>

    <div class="row g-3">
        @foreach($juegos as $juego)
            <div class="col-6 col-md-4">
                <a href="#" class="btn btn-juego w-100 text-decoration-none"
                   style="background:linear-gradient(135deg, #DAE8FC, #D5E8D4);
                          border:3px solid #1B3361; color:#1B3361;">
                    <span class="icono-juego">🍎</span>
                    <span class="d-block">{{ $juego->nombre_es }}</span>
                    <span class="d-block">{{ $juego->nombre_gn }}</span>
                </a>
            </div>
        @endforeach
    </div>
@else
    {{-- Placeholder mientras se implementa el flujo completo --}}
    <div class="text-center py-5">
        <div style="font-size:5rem">🎯</div>
        <h3 class="fw-bold mt-3" style="color:#1B3361">{{ $titulo ?? '¡A Jugar!' }}</h3>
        <p class="text-muted">Los juegos se cargan en la Semana 2.</p>
        <span class="badge bg-warning text-dark p-2 rounded-3 fs-6">
            <i class="bi bi-tools me-1"></i> En construcción — Semana 2
        </span>
    </div>
@endif

@endsection
