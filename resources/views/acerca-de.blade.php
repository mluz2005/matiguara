@extends(\Illuminate\Support\Facades\Auth::guard('estudiante')->check() ? 'layouts.estudiante' : 'layouts.app')

@section('titulo', 'Acerca de MatiGuará')

{{--
    VISTA: acerca-de.blade.php — "Acerca de MatiGuará"
    =========================================================================
    Pública: la ven estudiantes, docentes, familias, administración y quien
    todavía no inició sesión. Usa el diseño infantil si quien mira es un
    estudiante y el diseño sobrio en los demás casos.
    Todos los datos están en App\Support\AcercaDe (versión, autoría, contacto,
    créditos). Variables: $creditos
    =========================================================================
--}}

@php
    $esEstudiante = \Illuminate\Support\Facades\Auth::guard('estudiante')->check();
    $volver = $esEstudiante
        ? route('estudiante.juegos')
        : (auth()->check() ? url()->previous() : route('inicio'));
@endphp

@section('contenido')
<div class="container py-4" style="max-width:760px">

    <div class="text-center mb-4">
        <div style="font-size:3.5rem" aria-hidden="true">🦜</div>
        <h1 class="fw-bold mb-1" style="color:#1B3361">MatiGuará</h1>
        <p class="fs-5 mb-2" style="color:#44546F">
            Aplicación educativa con juegos de matemática, en castellano y guaraní,
            para niños y niñas de 6 a 9 años (primer ciclo de la Educación Escolar Básica).
        </p>
        <span class="badge rounded-pill px-3 py-2 fs-6" style="background:#0E6B7D">
            Versión {{ \App\Support\AcercaDe::VERSION }}
        </span>
        <div class="mt-2" style="color:#44546F;font-size:1rem">
            {{ \App\Support\AcercaDe::ESTADO_VERSION }} · {{ \App\Support\AcercaDe::FECHA }}
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold" style="color:#1B3361">¿Qué es?</h2>
            <p class="mb-0" style="color:#1F2937">
                MatiGuará convierte la práctica de la matemática en un juego. Tiene tres juegos
                (Suma de Frutas, Flash Matemático y Encuentra el par), niveles que se adaptan
                a cada niño, medallas y un panel para docentes y familias que muestra el avance.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold" style="color:#1B3361">Autoría</h2>
            <dl class="row mb-0" style="color:#1F2937">
                <dt class="col-sm-4">Autora</dt>
                <dd class="col-sm-8">{{ \App\Support\AcercaDe::AUTORA }}</dd>
                <dt class="col-sm-4">Tutora</dt>
                <dd class="col-sm-8">{{ \App\Support\AcercaDe::TUTORA }}</dd>
                <dt class="col-sm-4">Institución</dt>
                <dd class="col-sm-8">{{ \App\Support\AcercaDe::INSTITUCION }}</dd>
                <dt class="col-sm-4">Trabajo</dt>
                <dd class="col-sm-8 mb-0">{{ \App\Support\AcercaDe::TRABAJO }}</dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold" style="color:#1B3361">Contacto</h2>
            <p class="mb-0" style="color:#1F2937">
                @if(\App\Support\AcercaDe::CONTACTO)
                    Consultas o problemas con el sistema:
                    <a href="mailto:{{ \App\Support\AcercaDe::CONTACTO }}">{{ \App\Support\AcercaDe::CONTACTO }}</a>
                @else
                    Para consultas o problemas con el sistema, hablá con tu docente.
                @endif
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h2 class="h5 fw-bold" style="color:#1B3361">Créditos</h2>
            <ul class="list-unstyled mb-0" style="color:#1F2937">
                @foreach($creditos as $c)
                    <li class="mb-2"><strong>{{ $c['titulo'] }}.</strong> {{ $c['detalle'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold" style="color:#1B3361">Datos de niños y niñas</h2>
            <p style="color:#1F2937">
                MatiGuará guarda solo lo necesario para jugar y para que la docente pueda ver el avance:
                el nombre, el grado, el PIN (guardado de forma cifrada), las partidas, los puntajes y las medallas.
                Además, la docente puede cargar el <strong>correo electrónico del padre, madre o tutor</strong>, que se usa
                únicamente para que la familia pueda ingresar a ver el avance de su hijo o hija.
            </p>
            <ul style="color:#1F2937" class="mb-0">
                <li>Solo pueden ver el avance de un niño o una niña su docente, sus familiares vinculados y la administración del sistema.</li>
                <li>El correo de la familia no se comparte con nadie, no se usa para enviar mensajes ni publicidad, y la docente puede corregirlo o quitarlo cuando quiera.</li>
                <li>Si la docente pide un diagnóstico con inteligencia artificial, la IA recibe <strong>solo datos numéricos anonimizados</strong>: nunca el nombre ni el código del estudiante.</li>
                <li>No se muestran anuncios ni se venden datos.</li>
                <li>No se pide fotografía, dirección ni teléfono de los niños.</li>
            </ul>
        </div>
    </div>

    <div class="text-center">
        <a href="{{ $volver }}" class="btn btn-lg rounded-pill px-4 fw-bold"
           style="background:#177040;border:3px solid #0F5130;color:#fff">
            ◀ Volver
        </a>
    </div>
</div>
@endsection
