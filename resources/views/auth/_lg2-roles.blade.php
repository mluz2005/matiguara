{{-- Selector de perfil compartido por los tres logins. Recibe $actual: 'estudiante' | 'docente' | 'familia' --}}
@php
    $rolesLg2 = [
        'estudiante' => ['🧒', 'Estudiante',                'Con mi código y PIN',    route('estudiante.login')],
        'docente'    => ['🧑‍🏫', 'Docente o administrador/a', 'Con correo y contraseña', route('login')],
        'familia'    => ['👨‍👩‍👧', 'Familia',                   'Con correo y código',    route('familia.login')],
    ];
@endphp
<nav class="lg2-roles" aria-label="Elegí quién sos">
    <h2>¿Quién sos?</h2>
    <ul>
        @foreach($rolesLg2 as $clave => $r)
            <li>
                @if($clave === $actual)
                    <span class="lg2-rol actual" aria-current="page">
                        <span class="ico" aria-hidden="true">{{ $r[0] }}</span>
                        <span>{{ $r[1] }}<br><small>Estás acá</small></span>
                    </span>
                @else
                    <a class="lg2-rol" href="{{ $r[3] }}">
                        <span class="ico" aria-hidden="true">{{ $r[0] }}</span>
                        <span>{{ $r[1] }}<br><small>{{ $r[2] }}</small></span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
    <p class="lg2-pie"><a href="{{ route('acerca') }}">ℹ️ Acerca de MatiGuará</a></p>
</nav>
