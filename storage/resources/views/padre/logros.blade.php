@extends('layouts.app')
@section('titulo','Logros de '.$estudiante->nombre)
@section('contenido')
<div class="container py-3" style="max-width:600px">

  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('padre.panel') }}" class="btn btn-sm rounded-3"
       style="background:#f8f9fa;border:1px solid #dee2e6">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="fw-bold mb-0" style="color:#8A5A00">
      🏅 Logros de {{ $estudiante->nombre }}
    </h2>
  </div>

  @if($logros->isEmpty())
    <div class="card border-0 rounded-4 p-5 text-center shadow-sm">
      <div style="font-size:3rem">🌱</div>
      <p class="text-muted mt-3">Todavía no tiene logros. ¡Pronto llegará el primero!</p>
    </div>
  @else
    <div class="row g-3">
      @foreach($logros as $l)
        <div class="col-6 col-sm-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100"
               style="background:#FFF2CC;border:2px solid #8A5A00 !important">
            <div style="font-size:2.5rem">🏅</div>
            <div class="fw-bold mt-2" style="color:#8A5A00">{{ $l->logro->nombre_es }}</div>
            <div class="fw-bold" style="color:#8A5A00">{{ $l->logro->nombre_gn }}</div>
            <div class="small mt-2" style="color:#854F0B">
              {{ $l->fecha_obtenido->format('d/m/Y') }}
            </div>
            <span class="badge rounded-pill mt-2"
                  style="background:#8A5A00;color:#fff;font-size:.875rem">
              {{ $l->logro->rareza }}
            </span>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
