@extends('layouts.app')

@section('titulo', $titulo ?? 'Panel Docente')

@section('contenido')
<div class="container-fluid py-4">
    <div class="row">

        {{-- Sidebar --}}
        <div class="col-lg-2 d-none d-lg-block">
            <div class="sidebar p-3">
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->routeIs('docente.panel') ? 'active' : '' }}"
                       href="{{ route('docente.panel') }}">
                        <i class="bi bi-grid-1x2"></i> Mi Panel
                    </a>
                    <a class="nav-link {{ request()->routeIs('docente.estudiantes') ? 'active' : '' }}"
                       href="{{ route('docente.estudiantes') }}">
                        <i class="bi bi-people"></i> Estudiantes
                    </a>
                    <a class="nav-link {{ request()->routeIs('docente.juegos') ? 'active' : '' }}"
                       href="{{ route('docente.juegos') }}">
                        <i class="bi bi-controller"></i> Juegos
                    </a>
                    <a class="nav-link {{ request()->routeIs('docente.cuentas') ? 'active' : '' }}"
                       href="{{ route('docente.cuentas') }}">
                        <i class="bi bi-person-plus"></i> Cuentas
                    </a>
                </nav>
            </div>
        </div>

        {{-- Contenido principal --}}
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold" style="color:#1B3361">
                    <i class="bi bi-grid-1x2 me-2"></i>{{ $titulo ?? 'Panel Docente' }}
                </h2>
                <span class="badge rounded-pill" style="background:#1B3361; font-size:.875rem">
                    <i class="bi bi-person-badge me-1"></i>Docente
                </span>
            </div>

            {{-- Panel principal --}}
            @if(isset($asignacion))
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Institución</div>
                                <h5 class="fw-bold">{{ $asignacion->institucion->nombre ?? 'Sin asignar' }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Grado</div>
                                <h5 class="fw-bold">{{ $asignacion->grado ?? '—' }}° Grado</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Año lectivo</div>
                                <h5 class="fw-bold">{{ $asignacion->anio_lectivo ?? '2026' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div style="font-size:4rem">🏗️</div>
                <h4 class="fw-bold mt-3">{{ $titulo ?? 'Panel' }}</h4>
                <p class="text-muted">Esta sección se completa en la semana correspondiente del plan de desarrollo.</p>
                <div class="badge bg-warning text-dark p-2 rounded-3">
                    <i class="bi bi-tools me-1"></i> En construcción — Incremento I3/I4
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
