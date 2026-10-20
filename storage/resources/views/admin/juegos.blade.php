@extends('layouts.app')
@section('titulo','Catálogo de Juegos')
@section('contenido')
<div class="container py-3">
  <h2 class="fw-bold mb-4" style="color:#1B3361">
    <i class="bi bi-controller me-2"></i>Catálogo de Juegos
  </h2>
  <div class="row g-3">
    @foreach($juegos as $juego)
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body p-3">
          <div class="fw-bold" style="color:#1B3361">{{ $juego->nombre_es }}</div>
          <div class="fw-bold" style="color:#1B3361">{{ $juego->nombre_gn }}</div>
          <div class="mt-2">
            <span class="badge rounded-pill" style="background:#DAE8FC;color:#1B3361">
              {{ $juego->tipoMecanica?->nombre }}
            </span>
            @if($juego->tiempo_mostrar_ms)
              <span class="badge rounded-pill ms-1" style="background:#FFF2CC;color:#8A5A00">
                ⚡ Flash {{ $juego->tiempo_mostrar_ms/1000 }}s
              </span>
            @endif
          </div>
          <div class="mt-2 small text-muted">
            {{ $juego->niveles()->count() }} niveles configurados
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endsection
