{{-- Selector de perfil compartido por los tres logins. Recibe $actual: 'estudiante' | 'docente' | 'familia'
     Guaraní REVISADO 2026-11-19 (App\Support\TextosComunes::ROLES / INGRESO, COM-LOG-27..30). --}}
@php
    $tc = \App\Support\TextosComunes::class;
    $ico = ['estudiante' => '🧒', 'docente' => '🧑‍🏫', 'familia' => '👨‍👩‍👧'];
    $rutas = ['estudiante' => route('estudiante.login'), 'docente' => route('login'), 'familia' => route('familia.login')];
@endphp
<nav class="lg2-roles" aria-label="Elegí quién sos · Ñanembosako'i mávapa nde">
    <h2>{{ $tc::INGRESO['quienSos']['es'] }}<span class="lg2-gn" lang="gn">{{ $tc::INGRESO['quienSos']['gn'] }}</span></h2>
    <ul>
        @foreach($tc::ROLES as $clave => $r)
            <li>
                @if($clave === $actual)
                    <span class="lg2-rol actual" aria-current="page">
                        <span class="ico" aria-hidden="true">{{ $ico[$clave] }}</span>
                        <span>{{ $r['nombre']['es'] }}<span class="lg2-gn" lang="gn">{{ $r['nombre']['gn'] }}</span><br><small>{{ $tc::INGRESO['estasAca']['es'] }}</small><span class="lg2-gn forma" lang="gn">{{ $tc::INGRESO['estasAca']['gn'] }}</span></span>
                    </span>
                @else
                    <a class="lg2-rol" href="{{ $rutas[$clave] }}">
                        <span class="ico" aria-hidden="true">{{ $ico[$clave] }}</span>
                        <span>{{ $r['nombre']['es'] }}<span class="lg2-gn" lang="gn">{{ $r['nombre']['gn'] }}</span><br><small>{{ $r['forma']['es'] }}</small><span class="lg2-gn forma" lang="gn">{{ $r['forma']['gn'] }}</span></span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
    <p class="lg2-pie"><a href="{{ route('acerca') }}">{{ $tc::INGRESO['acerca']['es'] }}<span class="lg2-gn" lang="gn">{{ $tc::INGRESO['acerca']['gn'] }}</span></a></p>
</nav>
