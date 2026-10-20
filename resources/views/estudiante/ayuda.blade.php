@extends('layouts.estudiante')

@section('titulo', '¿Cómo se juega? — MatiGuará')

{{-- Esta pantalla suena con la misma música del menú (MP3); el layout no arranca la generada por código. --}}
@section('musica_propia', '1')

{{--
    VISTA: estudiante/ayuda.blade.php — "¿Cómo se juega?"
    =========================================================================
    Ayuda para niños de 6 a 9 años: UN paso por pantalla, con un dibujo grande,
    una frase corta en castellano y otra en guaraní, y botones 🔊 para escucharla.
      - 🔊 Castellano: archivo public/audio/ayuda/es/{clave}.mp3 si existe; si no, la voz del dispositivo.
      - 🔊 Guaraní:    archivo public/audio/ayuda/gn/{clave}.mp3; el botón solo aparece si el archivo existe.
    Los textos están en App\Support\AyudaJuegos (guaraní = borrador a validar).
    Variables: $juego (o null = ayuda general), $titulo, $pasos
    =========================================================================
--}}

@section('contenido')

<div class="text-center mb-3">
    <h1 class="titulo-sticker fs-3 d-inline-block mb-1">❓ ¿Cómo se juega?</h1>
    <div class="fw-bold" style="color:#1B3361;font-size:1.15rem">{{ $titulo }}</div>
</div>

{{-- Sin JavaScript se ven todos los pasos en lista; con JavaScript, uno por vez. --}}
<div id="ayudaPasos"></div>
<noscript>
    @foreach($pasos as $i => $p)
        <div class="tarjeta-infantil p-3 mb-3 text-center">
            <strong>{{ $i + 1 }}.</strong> {{ implode(' ', $p['dibujo']) }}
            <div class="fw-bold">{{ $p['es'] }}</div>
            <div class="fw-bold">{{ $p['gn'] }}</div>
        </div>
    @endforeach
</noscript>

<div class="text-center mt-4">
    <a href="{{ route('acerca') }}" style="color:#1B3361;font-size:1rem">ℹ️ Acerca de MatiGuará</a>
</div>

<audio id="musicaAyuda" loop preload="none">
    <source src="{{ asset('audio/musica/dashboard_juegos.mp3') }}" type="audio/mpeg">
</audio>

@endsection

@push('styles')
<style>
.ayuda-paso { background:#fff; border:4px solid #1B3361; border-radius:24px; box-shadow:0 6px 0 #1B3361;
              padding:16px; text-align:center; position:relative; margin-top:14px; }
.ayuda-num  { position:absolute; top:-18px; left:-8px; width:44px; height:44px; border-radius:50%;
              background:#FFD84D; border:3px solid #7A5200; color:#3D2B00; font-weight:800; font-size:1.4rem;
              display:flex; align-items:center; justify-content:center; }
.ayuda-dibujo { min-height:140px; border-radius:18px; background:#DFF1FF; display:flex; flex-wrap:wrap;
                align-items:center; justify-content:center; gap:10px; padding:12px; margin-bottom:12px;
                font-size:clamp(2.2rem, 11vw, 3.2rem); font-weight:800; color:#1B3361; line-height:1.15; }
.ayuda-carta { display:inline-flex; align-items:center; justify-content:center; min-width:64px; height:64px;
               padding:0 10px; border-radius:14px; border:3px solid #1B3361; background:#fff; font-size:1.9rem; }
.ayuda-carta.si { background:#FFD84D; }
.ayuda-es, .ayuda-gn { font-size:1.3rem; font-weight:800; color:#1B3361; line-height:1.3; }
.ayuda-gn { margin-top:2px; }
.ayuda-audios { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:12px; }
.ayuda-btn { border:3px solid #1B3361; background:#D5ECFF; color:#1B3361; border-radius:16px; min-height:48px;
             padding:8px 16px; font-weight:800; font-size:1.05rem; box-shadow:0 4px 0 #1B3361; }
.ayuda-btn:active { transform:translateY(3px); box-shadow:0 1px 0 #1B3361; }
.ayuda-btn:hover  { background:#BEE0FF; }
.ayuda-btn[disabled] { opacity:.45; box-shadow:none; }
.ayuda-btn.sonando { background:#FFD84D; }
.ayuda-puntos { display:flex; gap:8px; justify-content:center; margin:18px 0 8px; }
.ayuda-puntos i { width:16px; height:16px; border-radius:50%; background:#fff; border:3px solid #1B3361; }
.ayuda-puntos i.on { background:#FFD84D; }
.ayuda-nav { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
.ayuda-nav .ayuda-btn { min-width:130px; font-size:1.15rem; }
.ayuda-nav .principal { background:#177040; color:#fff; border-color:#0F5130; box-shadow:0 5px 0 #0F5130; }
.ayuda-nav .principal:hover { background:#1C8A4F; }
.ayuda-nav .jugar { background:#FFD84D; color:#3D2B00; border-color:#7A5200; box-shadow:0 5px 0 #7A5200; text-decoration:none;
                    display:inline-flex; align-items:center; justify-content:center; }
.ayuda-nav .jugar:hover { background:#FFCB1F; }
@media (prefers-reduced-motion: reduce) { .ayuda-btn:active { transform:none; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const PASOS = @json($pasos);
    const URL_VOLVER = @json(route('estudiante.juegos'));
    const ES_GENERAL = @json($juego === null);
    const contenedor = document.getElementById('ayudaPasos');
    let actual = 0;

    // ── Música del menú (mismo comportamiento que en el menú de juegos) ──
    const musica = document.getElementById('musicaAyuda');
    musica.volume = 0.25;
    const VOLUMEN_MUSICA = 0.25, VOLUMEN_BAJO = 0.08;
    const quiereMusica = () => { try { return localStorage.getItem('matiguara_musica') !== '0'; } catch (e) { return true; } };
    let musicaActiva = quiereMusica();
    function arrancarMusica() { if (musicaActiva && musica.paused) musica.play().catch(() => {}); }
    document.addEventListener('click', arrancarMusica);
    document.addEventListener('touchstart', arrancarMusica);
    window.MusicaPropia = {
        alternar() { musicaActiva = !musicaActiva; if (musicaActiva) musica.play().catch(() => {}); else musica.pause(); return musicaActiva; }
    };
    window.addEventListener('pagehide', () => musica.pause());
    if (typeof actualizarBotonMusica === 'function') actualizarBotonMusica(musicaActiva);

    // Mientras se escucha la ayuda, la música baja para que se entienda la voz.
    const bajarMusica = () => { musica.volume = VOLUMEN_BAJO; };
    const subirMusica = () => { musica.volume = VOLUMEN_MUSICA; };

    // ── Voz ──────────────────────────────────────────────────────────────
    let audioActual = null;
    function detenerVoz() {
        if (audioActual) { audioActual.pause(); audioActual = null; }
        if (window.speechSynthesis) speechSynthesis.cancel();
        document.querySelectorAll('.ayuda-btn.sonando').forEach(b => b.classList.remove('sonando'));
        subirMusica();
    }
    function sinEmojis(t) {
        return t.replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}]/gu, '').replace(/\s+/g, ' ').trim();
    }
    function hablar(paso, idioma, boton) {
        detenerVoz();
        boton.classList.add('sonando');
        bajarMusica();
        const fin = () => { boton.classList.remove('sonando'); subirMusica(); };
        const url = idioma === 'es' ? paso.audio_es : paso.audio_gn;
        if (url) {
            audioActual = new Audio(url);
            audioActual.volume = 0.95;
            audioActual.onended = fin;
            audioActual.play().catch(fin);
        } else if (idioma === 'es' && window.AudioService) {
            // Voz del dispositivo (respeta el botón 🔊 del encabezado).
            AudioService.leerEnunciado(sinEmojis(paso.es), 'es', fin);
            setTimeout(fin, 8000); // respaldo por si el navegador no avisa el final
        } else {
            fin();
        }
    }

    // ── Dibujo ───────────────────────────────────────────────────────────
    function dibujar(dibujo) {
        const cont = document.createElement('div');
        cont.className = 'ayuda-dibujo';
        cont.setAttribute('aria-hidden', 'true');
        dibujo.forEach(pieza => {
            const m = /^\[(.+?)(\*)?\]$/.exec(pieza);
            const el = document.createElement('span');
            if (m) { el.className = 'ayuda-carta' + (m[2] ? ' si' : ''); el.textContent = m[1]; }
            else   { el.textContent = pieza; }
            cont.appendChild(el);
        });
        return cont;
    }

    function boton(texto, clase, onClick, etiqueta) {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'ayuda-btn ' + (clase || ''); b.textContent = texto;
        if (etiqueta) b.setAttribute('aria-label', etiqueta);
        b.addEventListener('click', onClick);
        return b;
    }

    // ── Pantalla de un paso ──────────────────────────────────────────────
    function mostrar() {
        detenerVoz();
        const p = PASOS[actual];
        contenedor.innerHTML = '';

        const tarjeta = document.createElement('div');
        tarjeta.className = 'ayuda-paso';
        const num = document.createElement('div'); num.className = 'ayuda-num'; num.textContent = actual + 1;
        tarjeta.appendChild(num);
        tarjeta.appendChild(dibujar(p.dibujo));

        const es = document.createElement('div'); es.className = 'ayuda-es'; es.id = 'ayudaEs'; es.textContent = p.es;
        const gn = document.createElement('div'); gn.className = 'ayuda-gn'; gn.id = 'ayudaGn'; gn.textContent = p.gn;
        tarjeta.appendChild(es); tarjeta.appendChild(gn);

        const audios = document.createElement('div'); audios.className = 'ayuda-audios';
        const bEs = boton('🔊 Castellano', '', () => hablar(p, 'es', bEs), 'Escuchar en castellano');
        audios.appendChild(bEs);
        if (p.audio_gn) {
            const bGn = boton('🔊 Guaraní', '', () => hablar(p, 'gn', bGn), 'Escuchar en guaraní');
            audios.appendChild(bGn);
        }
        tarjeta.appendChild(audios);
        contenedor.appendChild(tarjeta);

        // Puntitos de avance
        const puntos = document.createElement('div'); puntos.className = 'ayuda-puntos';
        puntos.setAttribute('aria-label', 'Paso ' + (actual + 1) + ' de ' + PASOS.length);
        PASOS.forEach((_, i) => { const d = document.createElement('i'); if (i === actual) d.className = 'on'; puntos.appendChild(d); });
        contenedor.appendChild(puntos);

        // Botones
        const nav = document.createElement('div'); nav.className = 'ayuda-nav';
        if (actual > 0) nav.appendChild(boton('◀ Atrás', '', () => { actual--; mostrar(); }));
        if (actual < PASOS.length - 1) {
            nav.appendChild(boton('Siguiente ▶', 'principal', () => { actual++; mostrar(); }));
        } else {
            const a = document.createElement('a');
            a.href = URL_VOLVER; a.className = 'ayuda-btn jugar';
            a.textContent = ES_GENERAL ? '¡Listo! ▶' : '¡A jugar! ▶';
            nav.appendChild(a);
        }
        contenedor.appendChild(nav);
    }

    mostrar();
})();
</script>
@endpush
