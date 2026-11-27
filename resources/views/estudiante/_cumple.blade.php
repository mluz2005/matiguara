{{--
    Saludo de cumpleaños de Mati (solo si hoy cumple años). Castellano y guaraní. No bloquea nada.

    · En el MENÚ de juegos ('tarjeta'): Mati festeja con confeti (WebP animado, ~6 segundos: 4 vueltas) y
      después queda quieto en el último cuadro. Cada vez que el niño entra ese día se repite.
    · DENTRO de un juego ('aviso'): aviso corto con Mati quieto, una sola vez por día, que no distrae.
    · Si el equipo tiene activada la opción «reducir movimiento», Mati se ve siempre quieto.
    Imágenes: public/img/guara/mati-cumple-anim.webp (animada) y mati-cumple.webp (quieta).
--}}
@php
    $modoCumple = \App\Support\Cumpleanos::modoDeSaludo($est ?? null, request()->routeIs('estudiante.juegos'));
@endphp
@if($modoCumple)
    <style>
        .cumple { display:flex; align-items:center; gap:12px; background:#FFF2CC; color:#1B3361;
                  border:3px solid #E6A700; border-radius:22px; padding:10px 16px; box-shadow:0 4px 14px rgba(0,0,0,.18); }
        .cumple img { width:64px; height:auto; max-height:80px; object-fit:contain; flex:none; }
        .cumple .cumple-es { font-weight:800; font-size:1.15rem; line-height:1.2; }
        .cumple .cumple-gn { font-weight:700; font-size:1rem; color:#7A4F00; }
        .cumple .cumple-frase { font-size:.9rem; margin-top:2px; }
        .cumple-tarjeta { margin:12px auto 0; max-width:560px; }
        .cumple-tarjeta img { width:104px; max-height:none; }
        /* Dentro de un juego: flota arriba unos segundos y se va solo; no recibe toques. */
        .cumple-aviso { position:fixed; top:8px; left:50%; transform:translateX(-50%); z-index:9999; width:min(94vw,520px);
                        pointer-events:none; animation: cumple-fuera 9s ease forwards; }
        @keyframes cumple-fuera { 0%,85% { opacity:1; } 100% { opacity:0; visibility:hidden; } }
        @media (prefers-reduced-motion: reduce) { .cumple-aviso { animation-duration: 9s; } }
    </style>
    <div class="cumple cumple-{{ $modoCumple }}" role="status" aria-live="polite">
        @if($modoCumple === 'tarjeta')
            <picture>
                {{-- «Reducir movimiento»: imagen quieta --}}
                <source media="(prefers-reduced-motion: reduce)" srcset="{{ asset('img/guara/mati-cumple.webp') }}">
                <img src="{{ asset('img/guara/mati-cumple-anim.webp') }}" alt="" width="208" height="279" decoding="async">
            </picture>
        @else
            <img src="{{ asset('img/guara/mati-cumple.webp') }}" alt="" width="320" height="435" decoding="async">
        @endif
        <div>
            <div class="cumple-es">{{ \App\Support\Cumpleanos::saludoEs($est->nombre) }}</div>
            <div class="cumple-gn" lang="gn">{{ \App\Support\Cumpleanos::saludoGn($est->nombre) }}</div>
            @if($modoCumple === 'tarjeta')
                <div class="cumple-frase">{{ \App\Support\Cumpleanos::FRASE_ES }}</div>
                <div class="cumple-frase cumple-gn" lang="gn">{{ \App\Support\Cumpleanos::FRASE_GN }}</div>
            @endif
        </div>
    </div>
@endif
