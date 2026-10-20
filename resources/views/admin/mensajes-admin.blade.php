@extends('layouts.app')
@section('titulo','Mensajes de Retroalimentación')
@section('contenido')
<div class="container py-4 text-center">
  <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto" style="max-width:500px">
    <div style="font-size:3rem">🏗️</div>
    <h4 class="fw-bold mt-3" style="color:#1B3361">Mensajes de Retroalimentación</h4>
    <p class="text-muted">Implementación completa en la Semana 6.</p>
    <span class="badge bg-warning text-dark p-2 rounded-3">
      <i class="bi bi-tools me-1"></i>En construcción
    </span>
    <br>
    <a href="{{ route('admin.panel') }}" class="btn btn-sm mt-3 rounded-3"
       style="background:#1B3361;color:white">
      <i class="bi bi-arrow-left me-1"></i>Volver al panel
    </a>
  </div>
</div>
@endsection
