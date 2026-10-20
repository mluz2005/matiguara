/**
 * memoria-v2.js — Lógica + capa visual de "Encuentra el par" (rediseño v2)
 * =========================================================================
 * La LÓGICA DEL JUEGO es la misma de memoria-tablero.blade.php (vistazo
 * inicial, cronómetro con bonus por acierto, registro de cada intento,
 * cierre de sesión con reintento y respaldo, cambio de nivel 3/2). No cambia
 * ni un endpoint ni un dato que se envía al servidor.
 *
 * Lo NUEVO es solo presentación: MatiGuará reacciona, confeti, racha (combo),
 * palabra en guaraní al acertar, barra de tiempo y estrellas del cartel final.
 *
 * Se configura con window.MV2_CFG (lo define la vista Blade):
 *   sesionId, totalPares, tiempoLimiteSeg, bonusTiempoSeg, nivelNumero,
 *   urlListaJuegos, urlMovimiento, urlCerrar, csrf
 * Ganchos opcionales (para pruebas): navegar(url), iniciarNivel(urlIniciar, urlResumen)
 *
 * TEXTOS EN GUARANÍ: son borradores. Igual que el resto del proyecto, deben
 * ser validados por una persona hablante antes de usarse en producción.
 * =========================================================================
 */
(function () {
    'use strict';

    const CFG = window.MV2_CFG;
    if (!CFG) return;

    const SESION_ID         = CFG.sesionId;
    const TOTAL_PARES       = CFG.totalPares;
    const TIEMPO_LIMITE_SEG = CFG.tiempoLimiteSeg;
    const BONUS_TIEMPO_SEG  = CFG.bonusTiempoSeg;
    const NIVEL_NUMERO      = CFG.nivelNumero;
    const URL_LISTA_JUEGOS  = CFG.urlListaJuegos;
    const URL_MOVIMIENTO    = CFG.urlMovimiento;
    const URL_CERRAR        = CFG.urlCerrar;
    const CSRF_TOKEN        = CFG.csrf;

    const navegar = CFG.navegar || function (url) { window.location.href = url; };
    const iniciarNivelNuevo = CFG.iniciarNivel || irAlNivelNuevoPorDefecto;

    const $ = (id) => document.getElementById(id);
    const reducirMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const DURACION_MENSAJE_MS = 1500;
    // Igual que antes: 10 s en el Nivel 1 y baja 0.5 s por nivel, nunca menos de 2 s.
    const DURACION_CARTAS_MS = Math.max(2000, 10000 - (NIVEL_NUMERO - 1) * 500);

    // ── Estado (idéntico al original) ────────────────────────────────────
    let tiempoRestante     = TIEMPO_LIMITE_SEG;
    let paresEncontrados   = 0;
    let puntajeTotal       = 0;
    let cartasVolteadas    = [];
    let bloqueado          = false;
    let timerInterval      = null;
    let juegoTerminado     = false;
    let vistazoActivo      = true;
    let finalizandoEnCurso = false;
    let desenlaceDecidido  = false;

    // Estado solo visual (no se guarda ni se envía)
    let erroresLocales = 0;
    let racha          = 0;
    let primeraJugada  = true;

    function marcarDesenlace() {
        if (desenlaceDecidido) return false;
        desenlaceDecidido = true;
        return true;
    }

    const raiz = $('mv2');
    const cartas = Array.from(document.querySelectorAll('.carta-memoria'));

    // ── Sonidos propios del juego (los mismos MP3 de siempre) ────────────
    function reproducirSonidoPropio(id) {
        const el = $(id);
        if (!el) return;
        el.currentTime = 0;
        el.play().catch(function () {});
    }

    // ── MatiGuará: expresiones y globo de diálogo ────────────────────────
    const guara = $('guara');
    let guaraTimer = null;
    function guaraEstado(estado, ms) {
        if (!guara) return;
        guara.dataset.estado = estado;
        clearTimeout(guaraTimer);
        if (estado !== 'idle') guaraTimer = setTimeout(function () { guara.dataset.estado = 'idle'; }, ms || 1100);
    }
    function decir(es, gn) {
        const b = $('burbuja');
        if (!b) return;
        $('burbujaEs').textContent = es;
        $('burbujaGn').textContent = gn || '';
        b.classList.remove('late'); void b.offsetWidth; b.classList.add('late');
    }

    // ── Confeti de frutas ────────────────────────────────────────────────
    const CONFETI = ['🍎', '🍌', '🍊', '🍇', '🍓', '🍉', '⭐'];
    function confeti(origen, cantidad) {
        if (reducirMovimiento || !origen || !origen.animate) return;
        const r = origen.getBoundingClientRect();
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        for (let i = 0; i < cantidad; i++) {
            const s = document.createElement('span');
            s.className = 'mv2-confeti';
            s.textContent = CONFETI[Math.floor(Math.random() * CONFETI.length)];
            s.style.left = cx + 'px';
            s.style.top = cy + 'px';
            document.body.appendChild(s);
            const ang = Math.random() * Math.PI * 2;
            const dist = 70 + Math.random() * 120;
            const dx = Math.cos(ang) * dist, dy = Math.sin(ang) * dist - 50;
            const giro = (Math.random() * 360 - 180).toFixed(0);
            const anim = s.animate([
                { transform: 'translate(-50%,-50%) scale(.3)', opacity: 1 },
                { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px)) scale(1.1) rotate(' + giro + 'deg)', opacity: 1, offset: .55 },
                { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + (dy + 90) + 'px)) scale(.8) rotate(' + giro + 'deg)', opacity: 0 },
            ], { duration: 950 + Math.random() * 450, easing: 'cubic-bezier(.2,.7,.4,1)' });
            anim.onfinish = function () { s.remove(); };
        }
    }

    // ── Cartel (intro / transición / resultado) ──────────────────────────
    function mostrarCartel(id, html) {
        let overlay = $(id);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = id;
            overlay.className = 'mv2-overlay';
            overlay.setAttribute('role', 'status');
            document.body.appendChild(overlay);
        }
        overlay.innerHTML = '<div class="mv2-cartel">' + html + '</div>';
        return overlay;
    }
    function quitarCartel(id) {
        const o = $(id);
        if (o) o.remove();
    }
    function guaraGrande(estado) {
        if (!guara) return '';
        return '<div class="mv2-guara guara-grande" data-estado="' + estado + '">' + guara.innerHTML + '</div>';
    }
    function escaparHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    // ── Vistazo inicial (misma secuencia que antes) ──────────────────────
    function iniciarVistazo() {
        cartas.forEach(function (c) { c.classList.add('volteada'); });
        decir('¡Mirá bien las cartas!', 'Jaikuaapaite umi kuatia...');

        mostrarCartel('overlayVistazo',
            guaraGrande('feliz') +
            '<h3>¡Memorizá bien las cartas!</h3>' +
            '<p class="gn">Jaikuaapaite umi kuatia...</p>');

        setTimeout(function () {
            quitarCartel('overlayVistazo');

            // La barra de tiempo se convierte en la "barra de memorizar": se vacía
            // mientras las cartas están a la vista. No agrega nada al tablero, así
            // que nada se mueve de lugar.
            const caja = $('timerRing'), barra = $('timerBarra'), etiqueta = $('timerEtiqueta');
            caja.classList.add('vistazo');
            if (etiqueta) etiqueta.textContent = '👀 ¡Memorizá!';
            barra.style.transition = 'none';
            barra.style.width = '100%';
            void barra.offsetWidth;
            barra.style.transition = 'width ' + DURACION_CARTAS_MS + 'ms linear';
            barra.style.width = '0%';

            setTimeout(function () {
                cartas.forEach(function (c) { c.classList.remove('volteada'); });
                caja.classList.remove('vistazo');
                if (etiqueta) etiqueta.textContent = '⏱ Tiempo';
                barra.style.transition = 'none';
                barra.style.width = '100%';
                void barra.offsetWidth;
                barra.style.transition = '';
                vistazoActivo = false;
                decir('¡A jugar!', 'Jaha ñembosarái!');
                iniciarTemporizador();
            }, DURACION_CARTAS_MS);
        }, DURACION_MENSAJE_MS);
    }

    // ── Temporizador (misma lógica; cambia solo cómo se dibuja) ──────────
    function iniciarTemporizador() {
        timerInterval = setInterval(function () {
            tiempoRestante--;
            actualizarDisplayTiempo();
            if (tiempoRestante <= 0) {
                clearInterval(timerInterval);
                if (marcarDesenlace()) finalizarJuego(false);
            }
        }, 1000);
    }

    function actualizarDisplayTiempo() {
        const t = Math.max(0, tiempoRestante);
        $('timerNumero').textContent =
            String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');

        const ratio = Math.min(1, t / TIEMPO_LIMITE_SEG);
        $('timerBarra').style.width = (ratio * 100) + '%';

        const caja = $('timerRing');
        caja.classList.toggle('poco',  ratio <= 0.2);
        caja.classList.toggle('medio', ratio > 0.2 && ratio <= 0.4);
    }

    function agregarBonusTiempo() {
        tiempoRestante += BONUS_TIEMPO_SEG;
        actualizarDisplayTiempo();
        const caja = $('timerRing');
        const extra = document.createElement('span');
        extra.className = 'mv2-extra';
        extra.textContent = '+' + BONUS_TIEMPO_SEG + ' s';
        caja.appendChild(extra);
        setTimeout(function () { extra.remove(); }, 1150);
    }

    // ── Clic / teclado sobre una carta ───────────────────────────────────
    cartas.forEach(function (carta) {
        carta.addEventListener('click', function () { manejarClicCarta(carta); });
        carta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); manejarClicCarta(carta); }
        });
    });

    function manejarClicCarta(carta) {
        if (vistazoActivo || bloqueado || juegoTerminado) return;
        if (carta.classList.contains('volteada') || carta.classList.contains('encontrada')) return;
        if (cartasVolteadas.length >= 2) return;

        intentarIniciarMusicaAuto();

        if (primeraJugada) {
            primeraJugada = false;
            const ayuda = $('mv2Ayuda'); if (ayuda) ayuda.classList.add('oculta');
        }

        carta.classList.add('volteada');
        carta.setAttribute('aria-label', 'Carta boca arriba');
        if (window.AudioService && AudioService.reproducirFeedback) AudioService.reproducirFeedback('clic');

        cartasVolteadas.push({ el: carta, tarjetaId: parseInt(carta.dataset.tarjetaId, 10), tiempoInicio: Date.now() });

        if (cartasVolteadas.length === 2) {
            bloqueado = true;
            setTimeout(compararCartas, 600); // esperar a que termine el giro
        }
    }

    function compararCartas() {
        const a = cartasVolteadas[0], b = cartasVolteadas[1];
        const esAcierto = a.tarjetaId === b.tarjetaId;
        const tiempoMs  = Date.now() - a.tiempoInicio;

        if (esAcierto) {
            a.el.classList.add('encontrada');
            b.el.classList.add('encontrada');
            a.el.setAttribute('aria-label', 'Par encontrado');
            b.el.setAttribute('aria-label', 'Par encontrado');
            paresEncontrados++;
            $('paresEncontrados').textContent = paresEncontrados;
            llenarHueco(a.el);
            reproducirSonidoPropio('sonidoAcierto');
            agregarBonusTiempo();

            racha++;
            confeti(a.el, 8); confeti(b.el, 8);
            guaraEstado('feliz', 1200);
            // Globo: la palabra en castellano y en guaraní (si son iguales, un elogio en guaraní)
            const es = a.el.dataset.es || '', gn = a.el.dataset.gn || '';
            if (racha >= 3) decir('¡Qué racha! ×' + racha, 'Iporãiterei!');
            else decir('¡' + es + '!', (gn && gn !== es) ? gn : 'Iporã!');
            mostrarCombo();
        } else {
            erroresLocales++;
            racha = 0;
            mostrarCombo();
            a.el.classList.add('error');
            b.el.classList.add('error');
            reproducirSonidoPropio('sonidoError');
            guaraEstado('uy', 700);
            decir('¡Casi! Probá otra vez', "Eñeha'ã jey");
            setTimeout(function () {
                a.el.classList.remove('volteada', 'error');
                b.el.classList.remove('volteada', 'error');
                a.el.setAttribute('aria-label', 'Carta boca abajo');
                b.el.setAttribute('aria-label', 'Carta boca abajo');
            }, 520);
        }

        registrarMovimiento(a.tarjetaId, b.tarjetaId, tiempoMs);

        cartasVolteadas = [];
        bloqueado = false;

        if (paresEncontrados >= TOTAL_PARES) {
            clearInterval(timerInterval);
            setTimeout(function () {
                if (marcarDesenlace()) finalizarJuego(true);
            }, 900);
        }
    }

    // Marca el siguiente hueco de la fila de progreso con lo que se encontró
    function llenarHueco(carta) {
        const huecos = document.querySelectorAll('#mv2Progreso .mv2-hueco');
        const h = huecos[paresEncontrados - 1];
        if (!h) return;
        h.classList.add('lleno');
        const modo = carta.dataset.modo;
        h.innerHTML = (modo === 'normal') ? (carta.dataset.emoji || '⭐') : '<b>' + escaparHtml(carta.dataset.valor || '') + '</b>';
    }

    function mostrarCombo() {
        const c = $('mv2Combo');
        if (!c) return;
        if (racha >= 2) {
            c.hidden = false;
            c.textContent = '🔥 ×' + racha;
            c.style.animation = 'none'; void c.offsetWidth; c.style.animation = '';
        } else {
            c.hidden = true;
        }
    }

    // ── Comunicación con el servidor (sin cambios) ───────────────────────
    async function fetchConLimite(url, opciones, timeoutMs) {
        const controlador = new AbortController();
        const timer = setTimeout(function () { controlador.abort(); }, timeoutMs);
        try {
            const resp = await fetch(url, Object.assign({}, opciones, { signal: controlador.signal }));
            clearTimeout(timer);
            if (!resp.ok) throw new Error('Respuesta HTTP ' + resp.status);
            return await resp.json();
        } catch (e) {
            clearTimeout(timer);
            throw e;
        }
    }

    async function registrarMovimiento(tarjetaAId, tarjetaBId, tiempoMs) {
        try {
            const datos = await fetchConLimite(URL_MOVIMIENTO, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ tarjeta_a_id: tarjetaAId, tarjeta_b_id: tarjetaBId, tiempo_ms: tiempoMs }),
            }, 6000);

            puntajeTotal += datos.puntos || 0;
            $('puntajeActual').textContent = puntajeTotal;

            if (datos.cambio_nivel && datos.redirect_url && marcarDesenlace()) {
                juegoTerminado = true;
                clearInterval(timerInterval);
                mostrarTransicion(datos.cambio_nivel, datos.redirect_url);
            }
        } catch (e) {
            console.warn('No se pudo registrar el movimiento:', e);
        }
    }

    function mostrarTransicion(tipo, redirectUrl) {
        const sube = tipo === 'sube';
        mostrarCartel('overlayTransicion',
            guaraGrande(sube ? 'feliz' : 'idle') +
            '<h3>' + (sube ? '¡Subiste de nivel!' : 'Vamos a practicar un poco más') + '</h3>');
        if (sube) confeti($('overlayTransicion').firstChild, 30);
        setTimeout(function () { navegar(redirectUrl); }, 1800);
    }

    async function finalizarJuego(completadoATiempo) {
        if (finalizandoEnCurso) return;
        finalizandoEnCurso = true;
        juegoTerminado = true;
        clearInterval(timerInterval);

        mostrarCartel('overlayCierre',
            guaraGrande(completadoATiempo ? 'feliz' : 'idle') +
            '<h3>' + (completadoATiempo ? '¡Encontraste todos los pares!' : '¡Se acabó el tiempo!') + '</h3>' +
            '<p>Guardando tu resultado</p><div class="mv2-puntitos"><span></span><span></span><span></span></div>');
        if (completadoATiempo) confeti($('overlayCierre').firstChild, 40);

        desvanecerMusica(600);

        const opciones = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ completado_a_tiempo: completadoATiempo }),
        };

        for (let intento = 1; intento <= 2; intento++) {
            try {
                const datos = await fetchConLimite(URL_CERRAR, opciones, 5000);
                if (datos.redirect_url) {
                    mostrarResultadoNivel(datos, completadoATiempo);
                    if (datos.cambio_nivel && datos.iniciar_url) {
                        const espera = (datos.medallas && datos.medallas.length) ? 3800 : 2600;
                        setTimeout(function () { iniciarNivelNuevo(datos.iniciar_url, datos.redirect_url); }, espera);
                    } else {
                        setTimeout(function () { navegar(datos.redirect_url); }, 2600);
                    }
                    return;
                }
                break;
            } catch (e) {
                console.warn('No se pudo cerrar la sesión (intento ' + intento + '):', e);
                if (intento < 2) await new Promise(function (r) { setTimeout(r, 500); });
            }
        }

        mostrarCartel('overlayCierre',
            guaraGrande('idle') +
            '<h3>No pudimos guardar el resultado ahora</h3>' +
            '<p>Pero, ¡jugaste muy bien! Volviendo a los juegos...</p>');
        setTimeout(function () { navegar(URL_LISTA_JUEGOS); }, 2500);
    }

    function irAlNivelNuevoPorDefecto(urlIniciar, urlResumen) {
        try {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = urlIniciar;
            const t = document.createElement('input');
            t.type = 'hidden'; t.name = '_token'; t.value = CSRF_TOKEN;
            f.appendChild(t);
            document.body.appendChild(f);
            f.submit();
        } catch (e) {
            console.warn('No se pudo iniciar el nivel nuevo:', e);
            navegar(urlResumen);
        }
    }

    // Estrellas SOLO visuales (no se guardan): según cuántos errores hubo.
    function estrellasLocales(completado) {
        if (!completado) return 0;
        const r = erroresLocales / Math.max(1, TOTAL_PARES);
        return r <= 0.5 ? 3 : (r <= 1.5 ? 2 : 1);
    }

    function mostrarResultadoNivel(datos, completadoATiempo) {
        let titulo, detalle = '', estado;
        const nroNivel = datos.siguiente_nivel_numero ? ' ' + datos.siguiente_nivel_numero : '';

        if (datos.cambio_nivel === 'sube') {
            titulo = '¡Subiste al nivel' + nroNivel + '!'; estado = 'feliz';
        } else if (datos.cambio_nivel === 'baja') {
            titulo = 'Vamos a practicar en el nivel' + nroNivel; estado = 'idle';
        } else if (completadoATiempo) {
            titulo = '¡Muy bien, lo lograste!'; estado = 'feliz';
            detalle = '<p>Vas ' + datos.racha_consecutivas + ' de ' + datos.racha_meta + ' partidas seguidas para subir de nivel.</p>';
        } else {
            titulo = '¡Casi! Seguí practicando.'; estado = 'idle';
        }

        if (datos.cambio_nivel && datos.medallas && datos.medallas.length) {
            const lista = datos.medallas.map(function (m) {
                return '<div style="font-size:1.2rem;margin-top:6px">' + m.emoji + ' ' + escaparHtml(m.nombre) + '</div>';
            }).join('');
            detalle += '<p style="margin-top:12px">¡Ganaste una medalla nueva!</p>' + lista;
        }
        if (datos.cambio_nivel && datos.iniciar_url) {
            detalle += '<p>Preparando el nivel' + nroNivel + '...</p>';
        }

        const n = estrellasLocales(completadoATiempo);
        let estrellas = '';
        if (completadoATiempo) {
            estrellas = '<div class="mv2-estrellas" aria-label="' + n + ' de 3 estrellas">';
            for (let i = 0; i < 3; i++) estrellas += '<span class="mv2-estrella' + (i < n ? ' gana' : '') + '" style="--i:' + i + '">⭐</span>';
            estrellas += '</div>';
        }

        mostrarCartel('overlayCierre', guaraGrande(estado) + '<h3>' + titulo + '</h3>' + estrellas + detalle);
        if (completadoATiempo) confeti($('overlayCierre').firstChild, 36);
    }

    // ── Música de fondo (igual que antes) ────────────────────────────────
    let musicaActiva = false;
    let musicaAutoIntentada = false;
    const musicaEl = $('musicaFondoJuego');
    if (musicaEl) musicaEl.volume = 0.35;

    function desvanecerMusica(duracionMs) {
        if (!musicaEl || musicaEl.paused) return;
        const pasos = 12;
        const paso = musicaEl.volume / pasos;
        let i = 0;
        const t = setInterval(function () {
            i++;
            musicaEl.volume = Math.max(0, musicaEl.volume - paso);
            if (i >= pasos) { clearInterval(t); musicaEl.pause(); musicaEl.volume = 0.35; }
        }, duracionMs / pasos);
    }

    function pintarBotonMusica() {
        const btn = $('btnMusicaJuego');
        if (btn) btn.textContent = musicaActiva ? '🎵 Música' : '🔇 Música';
    }

    function toggleMusicaJuego() {
        if (!musicaEl) return false;
        if (musicaActiva) { musicaEl.pause(); musicaActiva = false; }
        else { musicaEl.play().catch(function () { console.warn('No se pudo iniciar la música'); }); musicaActiva = true; }
        pintarBotonMusica();
        musicaAutoIntentada = true;
        try { localStorage.setItem('matiguara_musica', musicaActiva ? '1' : '0'); } catch (e) {}
        if (typeof window.actualizarBotonMusica === 'function') window.actualizarBotonMusica(musicaActiva);
        return musicaActiva;
    }
    const btnMus = $('btnMusicaJuego');
    if (btnMus) btnMus.addEventListener('click', toggleMusicaJuego);
    window.MusicaPropia = { alternar: toggleMusicaJuego };

    function intentarIniciarMusicaAuto() {
        if (musicaAutoIntentada || !musicaEl) return;
        musicaAutoIntentada = true;
        try { if (localStorage.getItem('matiguara_musica') === '0') return; } catch (e) {}
        musicaEl.play().then(function () {
            musicaActiva = true;
            pintarBotonMusica();
            if (typeof window.actualizarBotonMusica === 'function') window.actualizarBotonMusica(true);
        }).catch(function () {});
    }

    // Gancho para el prototipo: detener el cronómetro al cambiar de pantalla
    window.MV2 = { detener: function () { clearInterval(timerInterval); juegoTerminado = true; } };

    // ── Inicio ───────────────────────────────────────────────────────────
    actualizarDisplayTiempo();
    iniciarVistazo();
})();
