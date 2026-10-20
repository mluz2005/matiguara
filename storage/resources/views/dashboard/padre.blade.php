@extends('layouts.app')

@section('titulo', $titulo ?? 'Panel de Seguimiento')

@section('contenido')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color:#1B3361">
            <i class="bi bi-house-heart me-2"></i>{{ $titulo ?? 'Panel de Seguimiento' }}
        </h2>
        <span class="badge rounded-pill bg-info text-dark">Padre/Tutor</span>
    </div>

    @if(isset($estudiante) && $estudiante)
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold">👤 {{ $estudiante->nombre_completo }}</h5>
            <p class="text-muted mb-0">Seguimiento de progreso — Semana 6</p>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
        <div style="font-size:4rem">📊</div>
        <h4 class="fw-bold mt-3">{{ $titulo ?? 'Panel' }}</h4>
        <p class="text-muted">Esta sección se implementa en la Semana 6.</p>
        <span class="badge bg-warning text-dark p-2 rounded-3">
            <i class="bi bi-tools me-1"></i> En construcción — Semana 6
        </span>
    </div>
</div>
@endsection
