/* =========================================================================
   numeros.js — «Mati y los números» (MatiGuará)
   Pantalla única: Mati presenta cada parte, el niño responde pregunta por
   pregunta y al final se lleva una figurita. El servidor decide qué
   preguntas van (GeneradorItems) y corrige; acá solo se dibuja y se mide el
   tiempo. La respuesta del juego es la MISMA para aciertos y errores: no hay
   puntos, sonidos ni colores que digan si estuvo bien.
   Guaraní: versión REVISADA (planilla Guarani_MatiYLosNumeros_revisado). Los textos vienen de
   App\Support\Numeros\TextosNumeros por NM_CFG.tx; acá no hay guaraní escrito a mano.
   ========================================================================= */
(function () {
  'use strict';

  var C = window.NM_CFG;
  if (!C) { return; }

  var escena  = document.getElementById('nm-escena');
  var barra   = document.getElementById('nm-prog-bar');
  var barraBx = document.querySelector('.nm-prog');
  var btnSalir = document.getElementById('nm-salir');
  var btnVoz   = document.getElementById('nm-voz');
  var aviso    = document.getElementById('nm-aviso');

  var inicioPagina = Date.now();
  var inicioViaje  = Date.now();
  var vozActiva    = true;
  var respondidas  = 0;
  var cerrando     = false;
  var terminado    = false;
  var audioActual  = null;

  // Textos (es + gn) que manda el servidor: C.tx = { ui: {...}, pre: {...} }.
  var TX = C.tx || { ui: {}, pre: {} };
  function U(k) { return TX.ui[k] || { es: '', gn: '' }; }
  function P(k) { return TX.pre[k] || { es: '', gn: '' }; }
  function fill(t, vars) {
    return String(t).replace(/\{(\w+)\}/g, function (m, k) { return vars && vars[k] !== undefined ? vars[k] : m; });
  }

  var NOMBRES_P = {};
  C.partes.forEach(function (p) { NOMBRES_P[p.parte] = p; });

  // ── Utilidades ─────────────────────────────────────────────────────────

  function el(tag, cls, txt) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    if (txt !== undefined && txt !== null) { e.textContent = txt; }
    return e;
  }
  /** Elemento con la línea en castellano y, debajo, la línea en guaraní (chica, en cursiva). */
  function bi(tag, cls, t) {
    var e = el(tag, cls, t.es);
    if (t.gn) {
      var g = el('span', 'nm-gn', t.gn);
      g.lang = 'gn';
      e.appendChild(g);
    }
    return e;
  }
  function esperar(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
  function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
  function minus(s) { return String(s).replace('-', '−'); }

  function api(metodo, url, datos) {
    var op = {
      method: metodo, credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
    };
    if (datos) { op.headers['Content-Type'] = 'application/json'; op.body = JSON.stringify(datos); }
    return fetch(url, op).then(function (r) {
      if (r.status === 423) { return r.json().then(function (j) { j.descanso = true; return j; }); }
      if (!r.ok) { throw new Error('http ' + r.status); }
      return r.json();
    });
  }

  /** Reintenta unas veces (conexiones malas de celular). Devuelve null si no pudo. */
  function conReintentos(fn, veces) {
    var intento = 0;
    function uno() {
      return fn().catch(function () {
        intento++;
        if (intento >= veces) { return null; }
        return esperar(700 * intento).then(uno);
      });
    }
    return uno();
  }

  function mostrar() {
    escena.replaceChildren.apply(escena, arguments);
    escena.scrollTop = 0;
  }

  function avance() {
    var pct = C.total ? Math.min(100, Math.round(100 * respondidas / C.total)) : 0;
    barra.style.width = pct + '%';
    barraBx.setAttribute('aria-valuenow', String(pct));
  }

  function agotado() {
    return C.limiteSeg > 0 && (Date.now() - inicioPagina) / 1000 >= C.restanteSeg;
  }

  function toast(t) {
    aviso.replaceChildren();
    aviso.appendChild(el('span', '', t.es));
    if (t.gn) { var g = el('span', 'nm-gn', t.gn); g.lang = 'gn'; aviso.appendChild(g); }
    aviso.hidden = false;
    clearTimeout(toast.t); toast.t = setTimeout(function () { aviso.hidden = true; }, 2600);
  }

  // ── Voz de Mati ────────────────────────────────────────────────────────

  function pararVoz() {
    if (audioActual) { try { audioActual.pause(); } catch (e) { /* nada */ } audioActual = null; }
    if ('speechSynthesis' in window) { try { window.speechSynthesis.cancel(); } catch (e) { /* nada */ } }
  }

  /** Castellano: grabación si existe, si no la voz del dispositivo. Guaraní: solo grabación. */
  function hablar(parte, leng, siempre) {
    if (!vozActiva && !siempre) { return; }
    pararVoz();
    var info = NOMBRES_P[parte];
    var v = C.voces && C.voces[parte] ? C.voces[parte][leng] : null;
    if (v) {
      audioActual = new Audio(v);
      var p = audioActual.play();
      if (p && p.catch) { p.catch(function () { /* el navegador pidió un toque antes */ }); }
      return;
    }
    if (leng === 'es' && 'speechSynthesis' in window && info) {
      try {
        var u = new SpeechSynthesisUtterance(info.ies);
        u.lang = 'es-ES'; u.rate = 0.9;
        window.speechSynthesis.speak(u);
      } catch (e) { /* nada */ }
    }
  }

  btnVoz.addEventListener('click', function () {
    vozActiva = !vozActiva;
    btnVoz.setAttribute('aria-pressed', vozActiva ? 'true' : 'false');
    btnVoz.textContent = vozActiva ? '🔊' : '🔇';
    if (!vozActiva) { pararVoz(); }
  });

  // ── Dibujos ────────────────────────────────────────────────────────────

  function img(nombre, cls) {
    var i = new Image();
    i.src = C.img + nombre + '.webp';
    i.alt = ''; i.decoding = 'async'; i.draggable = false;
    if (cls) { i.className = cls; }
    return i;
  }

  /** Número con palillos: c paquetes de 100, d atados de 10 y u sueltas; 0 = nido vacío. */
  function grupo(rep, altura, extra) {
    var g = el('div', 'nm-grupo' + (extra ? ' ' + extra : ''));
    g.style.setProperty('--h', altura + 'px');
    if (!rep.c && !rep.d && !rep.u) { g.appendChild(img('nido-de-mati', 'nido')); return g; }
    var i;
    for (i = 0; i < rep.c; i++) { g.appendChild(img('paquete-ramitas', 'paq')); }
    for (i = 0; i < rep.d; i++) { g.appendChild(img('atado-ramitas', 'ato')); }
    for (i = 0; i < rep.u; i++) { g.appendChild(img('ramita-suelta')); }
    return g;
  }
  /** Altura de los palillos en las cuentas: más chicos, porque van dos grupos en la misma fila. */
  function alturaCuenta(rep) {
    var n = (rep.c || 0) + (rep.d || 0) + (rep.u || 0);
    return n > 14 ? 22 : n > 10 ? 26 : n > 6 ? 32 : 40;
  }
  function alturaPara(rep, grande) {
    var n = (rep.c || 0) + (rep.d || 0) + (rep.u || 0);
    if (grande) { return n > 14 ? 52 : n > 10 ? 62 : n > 6 ? 74 : 88; }
    return n > 14 ? 34 : n > 10 ? 44 : n > 6 ? 56 : 68;
  }

  function rng(seed) {
    var a = seed >>> 0;
    return function () {
      a = (a + 0x6D2B79F5) >>> 0;
      var t = a;
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  function colocar(n, r, seed) {
    var f = rng(seed), pts = [], minD = 2 * r + 1.6, tries = 0;
    while (pts.length < n && tries < 8000) {
      var x = r + 2 + f() * (100 - 2 * r - 4), y = r + 2 + f() * (100 - 2 * r - 4);
      var ok = true;
      for (var i = 0; i < pts.length; i++) {
        if (Math.hypot(pts[i].x - x, pts[i].y - y) < minD) { ok = false; break; }
      }
      if (ok) { pts.push({ x: x, y: y }); }
      tries++;
      if (tries % 600 === 0) { minD *= 0.93; }
    }
    return pts;
  }

  function svgPuntos(n, r, seed, color) {
    var NS = 'http://www.w3.org/2000/svg';
    var s = document.createElementNS(NS, 'svg');
    s.setAttribute('viewBox', '0 0 100 100');
    s.setAttribute('aria-hidden', 'true');
    colocar(n, r, seed).forEach(function (p) {
      var c = document.createElementNS(NS, 'circle');
      c.setAttribute('cx', p.x.toFixed(1)); c.setAttribute('cy', p.y.toFixed(1)); c.setAttribute('r', r.toFixed(2));
      c.setAttribute('fill', color || '#0E6B7D'); c.setAttribute('stroke', '#1B3361'); c.setAttribute('stroke-width', '0.8');
      s.appendChild(c);
    });
    return s;
  }

  // ── Piezas comunes de pregunta ─────────────────────────────────────────

  /** Bloquea las opciones y marca la elegida: igual para aciertos y errores. */
  function bloquear(contenedor, elegido) {
    var botones = contenedor.querySelectorAll('button');
    for (var i = 0; i < botones.length; i++) { botones[i].disabled = true; }
    if (elegido) { elegido.classList.add('elegida'); }
  }

  /** Botón de opción. `contenido` puede ser texto o un nodo. */
  function opcion(contenido, valor, ctx, cont, clase) {
    var b = el('button', 'nm-op' + (clase ? ' ' + clase : ''));
    b.type = 'button';
    b.dataset.valor = String(valor);
    if (typeof contenido === 'string') { b.textContent = contenido; } else { b.appendChild(contenido); }
    if (!ctx.demo) {
      b.addEventListener('click', function () {
        ctx.responder(valor, function () { bloquear(cont, b); });
      });
    } else { b.tabIndex = -1; }
    return b;
  }

  function enunciado(t, chico) { return bi('p', 'nm-enunciado' + (chico ? ' chico' : ''), t); }

  function marcarDemo(cont, valor) {
    var objetivo = cont.querySelector('[data-valor="' + String(valor) + '"]');
    if (objetivo) { objetivo.classList.add('demo-ok'); }
  }

  // ── Dibujantes de cada tipo de pregunta ────────────────────────────────
  // Cada uno recibe (item, ctx) y devuelve { nodo, listo?, demoOk? }.
  //   ctx.activar()           → desde ahí se mide el tiempo de respuesta
  //   ctx.responder(v, lock)  → envía la respuesta (una sola vez)
  //   ctx.demo                → demostración: solo se mira, no se responde

  var DIBUJA = {};

  DIBUJA.toque = function (item, ctx) {
    var cielo = el('div', 'nm-cielo');
    var b = el('button', 'nm-luz');
    b.type = 'button'; b.hidden = true;
    b.style.left = item.datos.x + '%'; b.style.top = item.datos.y + '%';
    b.setAttribute('aria-label', 'Luciérnaga');
    b.appendChild(img('luciernaga-encendida'));
    cielo.appendChild(b);
    if (!ctx.demo) {
      b.addEventListener('click', function () {
        ctx.responder('toque', function () { b.classList.add('on'); b.disabled = true; });
      });
    } else { b.tabIndex = -1; }
    return {
      nodo: [enunciado(ctx.demo ? P('luzDemo') : P('luz'), ctx.demo), cielo],
      listo: function () {
        if (ctx.demo) { b.hidden = false; b.classList.add('demo-ok'); return; }
        setTimeout(function () { b.hidden = false; ctx.activar(); }, 600 + Math.round(Math.random() * 800));
      }
    };
  };

  DIBUJA.num_a_cantidad = function (item, ctx) {
    var cont = el('div', 'nm-opciones c3');
    item.opciones.forEach(function (o) {
      cont.appendChild(opcion(grupo(o, alturaPara(o)), o.valor, ctx, cont));
    });
    var num = el('div', 'nm-numerote', String(item.datos.numero));
    return {
      nodo: [enunciado(P('ramitas'), true), num, cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.datos.numero); }
    };
  };

  DIBUJA.cantidad_a_num = function (item, ctx) {
    var cont = el('div', 'nm-opciones c3');
    item.opciones.forEach(function (n) { cont.appendChild(opcion(String(n), n, ctx, cont)); });
    var rep = { c: item.datos.c, d: item.datos.d, u: item.datos.u };
    var g = grupo(rep, alturaPara(rep, true), 'nm-grupo-grande');
    return {
      nodo: [enunciado(P('numero'), true), g, cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.datos.numero); }
    };
  };

  DIBUJA.mas_puntos = function (item, ctx) {
    var d = item.datos;
    var nMax = Math.max(d.izq, d.der);
    var r0 = nMax > 30 ? 3.8 : 4.6;
    var paneles = el('div', 'nm-paneles');
    var lados = [['izq', d.izq, d.semilla], ['der', d.der, d.semilla + 77]];
    lados.forEach(function (l) {
      var r = d.control === 'area' ? r0 * Math.sqrt(nMax / l[1]) : r0;
      var b = el('button', 'nm-panel');
      b.type = 'button'; b.dataset.valor = l[0];
      b.setAttribute('aria-label', 'Puntos de un lado');
      b.appendChild(svgPuntos(l[1], r, l[2], '#0E6B7D'));
      if (!ctx.demo) {
        b.addEventListener('click', function () { ctx.responder(l[0], function () { bloquear(paneles, b); }); });
      } else { b.tabIndex = -1; }
      paneles.appendChild(b);
    });
    return {
      nodo: [enunciado(P('puntos')), paneles],
      listo: ctx.activar, demoOk: function () { marcarDemo(paneles, d.izq > d.der ? 'izq' : 'der'); }
    };
  };

  DIBUJA.mayor = function (item, ctx) {
    var cont = el('div', 'nm-mayor');
    [['izq', item.datos.izq], ['der', item.datos.der]].forEach(function (l) {
      cont.appendChild(opcion(String(l[1]), l[0], ctx, cont));
    });
    return {
      nodo: [enunciado(P('grande')), cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.datos.izq > item.datos.der ? 'izq' : 'der'); }
    };
  };

  DIBUJA.puntos_rapidos = function (item, ctx) {
    var d = item.datos;
    var tarjeta = el('div', 'nm-flash oculto', '');
    var cont = el('div', 'nm-opciones fila');
    cont.hidden = true;
    for (var i = 0; i <= d.max; i++) { cont.appendChild(opcion(String(i), i, ctx, cont, 'chica')); }

    function ponerPuntos() {
      tarjeta.className = 'nm-flash';
      tarjeta.replaceChildren(d.n > 0 ? svgPuntos(d.n, 6.5, d.semilla, '#FFC94D') : document.createTextNode(''));
    }
    function taparPuntos() {
      tarjeta.className = 'nm-flash oculto';
      tarjeta.replaceChildren(document.createTextNode('?'));
    }
    return {
      nodo: [enunciado(ctx.demo ? P('rapidoDemo') : P('rapido'), ctx.demo), tarjeta, cont],
      listo: function () {
        if (ctx.demo) { ponerPuntos(); cont.hidden = false; return; }
        taparPuntos();
        esperar(700).then(function () {
          ponerPuntos();
          return esperar(d.ms);
        }).then(function () {
          taparPuntos();
          cont.hidden = false;
          ctx.activar();
        });
      },
      demoOk: function () { marcarDemo(cont, d.n); }
    };
  };

  DIBUJA.linea = function (item, ctx) {
    var d = item.datos;
    var caja = el('div', 'nm-linea-caja');
    var linea = el('div', 'nm-linea');
    linea.appendChild(el('div', 'eje'));
    [0, 100].forEach(function (pct) {
      var t = el('div', 'tope'); t.style.left = pct + '%'; linea.appendChild(t);
      var e = el('div', 'etiq', pct === 0 ? '0' : String(d.max)); e.style.left = pct + '%'; linea.appendChild(e);
    });
    var marca = el('div', 'marca'); linea.appendChild(marca);
    caja.appendChild(linea);

    var obj = el('p', 'nm-linea-obj', String(d.objetivo));
    var listoBtn = bi('button', 'nm-boton', U('listo'));
    listoBtn.type = 'button'; listoBtn.disabled = true;
    var valor = null;

    function poner(clientX) {
      var r = linea.getBoundingClientRect();
      var f = Math.max(0, Math.min(1, (clientX - r.left) / r.width));
      valor = f * d.max;
      marca.style.left = (f * 100) + '%';
      marca.classList.add('on');
      listoBtn.disabled = false;
    }
    if (!ctx.demo) {
      var abajo = false;
      linea.addEventListener('pointerdown', function (e) { abajo = true; try { linea.setPointerCapture(e.pointerId); } catch (x) { /* nada */ } poner(e.clientX); });
      linea.addEventListener('pointermove', function (e) { if (abajo) { poner(e.clientX); } });
      linea.addEventListener('pointerup', function () { abajo = false; });
      linea.addEventListener('pointercancel', function () { abajo = false; });
      listoBtn.addEventListener('click', function () {
        if (valor === null) { return; }
        ctx.responder(valor.toFixed(1), function () { listoBtn.disabled = true; marca.classList.add('elegida'); });
      });
    }
    return {
      nodo: [enunciado(P('linea'), true), obj, caja, ctx.demo ? el('span') : listoBtn],
      listo: function () {
        if (ctx.demo) {
          marca.style.left = (100 * d.objetivo / d.max) + '%';
          marca.className = 'marca on demo';
          return;
        }
        ctx.activar();
      }
    };
  };

  DIBUJA.signo = function (item, ctx) {
    var d = item.datos;
    var ec = el('div', 'nm-ecuacion');
    ec.appendChild(el('span', '', String(d.a)));
    ec.appendChild(el('span', 'hueco', '?'));
    ec.appendChild(el('span', '', String(d.b)));
    ec.appendChild(el('span', '', '='));
    ec.appendChild(el('span', '', String(d.c)));
    var cont = el('div', 'nm-opciones dos nm-signos');
    item.opciones.forEach(function (o) { cont.appendChild(opcion(o === '+' ? '+' : '−', o, ctx, cont)); });
    return {
      nodo: [enunciado(P('signo'), true), ec, cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.correcta); }
    };
  };

  DIBUJA.cuenta_palillos = function (item, ctx) {
    var d = item.datos;
    var fila = el('div', 'nm-cuenta');
    fila.appendChild(grupo(d.repA, alturaCuenta(d.repA)));
    fila.appendChild(el('span', 'sig', d.op === '+' ? '+' : '−'));
    fila.appendChild(grupo(d.repB, alturaCuenta(d.repB)));
    fila.appendChild(el('span', 'sig', '='));
    fila.appendChild(el('span', 'res', '?'));
    var cont = el('div', 'nm-opciones c3');
    item.opciones.forEach(function (n) { cont.appendChild(opcion(String(n), n, ctx, cont)); });
    return {
      nodo: [enunciado(P('cuenta'), true), fila, cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.correcta); }
    };
  };

  DIBUJA.secuencia = function (item, ctx) {
    var fila = el('div', 'nm-secuencia');
    item.datos.seq.forEach(function (n) {
      fila.appendChild(el('div', 'caja' + (n === null ? ' falta' : ''), n === null ? '?' : String(n)));
    });
    var cont = el('div', 'nm-opciones c3');
    item.opciones.forEach(function (n) { cont.appendChild(opcion(String(n), n, ctx, cont)); });
    return {
      nodo: [enunciado(P('falta'), true), fila, cont],
      listo: ctx.activar, demoOk: function () { marcarDemo(cont, item.correcta); }
    };
  };

  // ── Flujo de una pregunta ──────────────────────────────────────────────

  function preguntar(info, item) {
    return new Promise(function (resolve) {
      var t0 = null, respondida = false;
      var ctx = {
        demo: false,
        activar: function () { t0 = performance.now(); },
        responder: function (resp, bloquearFn) {
          if (respondida || t0 === null) { return; }
          respondida = true;
          var ms = Math.round(performance.now() - t0);
          if (bloquearFn) { bloquearFn(); }
          guardar(info.parte, item.orden, String(resp), ms).then(function () { return esperar(600); }).then(resolve);
        }
      };
      var r = DIBUJA[item.tipo](item, ctx);
      mostrar.apply(null, r.nodo);
      if (r.listo) { r.listo(); }
    });
  }

  function guardar(parte, orden, resp, ms) {
    return conReintentos(function () {
      return api('POST', C.urls.responder, { parte: parte, orden: orden, respuesta: resp, tiempo_ms: ms });
    }, 3).then(function (r) {
      if (r === null) { toast(U('sinConexion')); }
      else if (r.descanso) { /* el cierre lo maneja agotado() */ }
      respondidas++;
      avance();
    });
  }

  // ── Pantallas ──────────────────────────────────────────────────────────

  /** titulo puede ser texto o {es, gn}. */
  function matiGlobo(imagen, titulo, es, gn) {
    var cont = el('div', 'nm-mati');
    var i = new Image(); i.src = imagen; i.alt = 'Mati, la mascota'; i.decoding = 'async';
    cont.appendChild(i);
    var g = el('div', 'nm-globo');
    if (titulo) { g.appendChild(typeof titulo === 'string' ? el('h2', '', titulo) : bi('h2', '', titulo)); }
    if (es) { g.appendChild(el('p', 'es', es)); }
    if (gn) { var p = el('p', 'gn', gn); p.lang = 'gn'; g.appendChild(p); }
    cont.appendChild(g);
    return cont;
  }

  function botonGrande(t, fn) {
    var b = typeof t === 'string' ? el('button', 'nm-boton', t) : bi('button', 'nm-boton', t);
    b.type = 'button';
    b.addEventListener('click', function () { b.disabled = true; fn(); });
    return b;
  }

  function pantallaIntro() {
    return new Promise(function (resolve) {
      var v = C.viajeNombre;
      var it = U('intro');
      mostrar(
        matiGlobo(C.mati.senala,
          U('hola'),
          fill(it.es, { viaje: v.es }),
          fill(it.gn, { viaje: v.gn })),
        botonGrande(U('vamos'), resolve)
      );
    });
  }

  function pantallaParte(info, demo) {
    return new Promise(function (resolve) {
      var voces = el('div', 'nm-fila nm-voces');
      var bEs = el('button', '', '🔊 Castellano'); bEs.type = 'button';
      bEs.addEventListener('click', function () { hablar(info.parte, 'es', true); });
      voces.appendChild(bEs);
      if (C.voces && C.voces[info.parte] && C.voces[info.parte].gn) {
        var bGn = el('button', '', U('vozGn').gn || '🔊 Guaraní'); bGn.type = 'button';
        bGn.addEventListener('click', function () { hablar(info.parte, 'gn', true); });
        voces.appendChild(bGn);
      }

      // Demostración: la misma pregunta, con la respuesta señalada. Solo se mira.
      var ctx = { demo: true, activar: function () {}, responder: function () {} };
      var d = DIBUJA[demo.tipo](demo, ctx);
      var area = el('div', 'nm-demo');
      area.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:10px;width:100%;pointer-events:none;transform:scale(.82);transform-origin:top center;margin-bottom:-40px;';
      (Array.isArray(d.nodo) ? d.nodo : [d.nodo]).forEach(function (n) { area.appendChild(n); });

      mostrar(
        matiGlobo(C.mati.senala, info.es + ' · ' + info.gn, info.ies, info.ign),
        voces,
        area,
        botonGrande(U('empezar'), function () { pararVoz(); resolve(); })
      );
      if (d.listo) { d.listo(); }
      if (d.demoOk) { d.demoOk(); }
      hablar(info.parte, 'es', false);
    });
  }

  function pantallaError(mensaje, reintentar) {
    return new Promise(function (resolve) {
      mostrar(
        matiGlobo(C.mati.piensa, U('errTitulo'), mensaje, ''),
        botonGrande(U('errBoton'), function () { resolve(); })
      );
    }).then(reintentar);
  }

  function pedirParte(parte) {
    return conReintentos(function () {
      return api('GET', C.urls.parte.replace('__P__', String(parte)));
    }, 3).then(function (r) {
      if (r && !r.error && !r.descanso) { return r; }
      if (r && r.descanso) { return { descanso: true }; }
      return pantallaError('Revisá tu internet y tocá el botón.', function () { return pedirParte(parte); });
    });
  }

  // ── Cierre ─────────────────────────────────────────────────────────────

  function cerrarViaje() {
    var seg = Math.round((Date.now() - inicioViaje) / 1000);
    return conReintentos(function () {
      return api('POST', C.urls.cerrar, { duracion_s: seg });
    }, 3);
  }

  function irA(url) { window.location.href = url; }

  function pantallaFin(r) {
    pararVoz();
    if (r && r.completa && r.figurita) {
      var marco = el('div', 'nm-figu-marco');
      var f = new Image(); f.src = C.img + r.figurita.img + '.webp'; f.alt = r.figurita.es; f.className = 'nm-figu';
      marco.appendChild(f);
      mostrar(
        matiGlobo(C.mati.despidiendo, U('finTitulo'),
          fill(U('finTexto').es, { figurita: r.figurita.es }),
          fill(U('finTexto').gn, { figurita: r.figurita.gn })),
        marco,
        botonGrande(U('volverJuegos'), function () { irA(C.urls.juegos); })
      );
    } else {
      mostrar(
        matiGlobo(C.mati.despidiendo, U('descTitulo'),
          U('descTexto').es, U('descTexto').gn),
        botonGrande(U('volverJuegos'), function () { irA(C.urls.juegos); })
      );
    }
  }

  function terminar(completa, porTiempo) {
    if (terminado) { return Promise.resolve(); }
    terminado = true;
    barra.style.width = completa ? '100%' : barra.style.width;
    return cerrarViaje().then(function (r) {
      if (porTiempo) {
        mostrar(
          matiGlobo(C.mati.despidiendo, U('tiempoTitulo'),
            U('tiempoTexto').es, U('tiempoTexto').gn),
          botonGrande(U('irDescansar'), function () { irA(C.urls.descanso); })
        );
        return;
      }
      pantallaFin(r || { completa: completa, figurita: null });
    });
  }

  // ── Salir (dos toques, RF-22) ──────────────────────────────────────────

  var armado = null;
  /** El texto del botón va en dos líneas: castellano y, debajo, guaraní. */
  function textoSalir(t) {
    var e = btnSalir.lastElementChild;
    e.replaceChildren();
    e.appendChild(document.createTextNode(t.es));
    if (t.gn) { var g = el('small', 'nm-gn', t.gn); g.lang = 'gn'; e.appendChild(g); }
  }
  textoSalir(U('salir'));
  btnSalir.addEventListener('click', function () {
    if (cerrando) { return; }
    if (!armado) {
      btnSalir.classList.add('armado');
      textoSalir(U('salirOtra'));
      armado = setTimeout(function () {
        armado = null;
        btnSalir.classList.remove('armado');
        textoSalir(U('salir'));
      }, 3000);
      return;
    }
    clearTimeout(armado);
    cerrando = true;
    pararVoz();
    (terminado ? Promise.resolve() : cerrarViaje()).then(function () { irA(C.urls.juegos); });
  });

  // ── Programa principal ─────────────────────────────────────────────────

  function jugar() {
    avance();
    return pantallaIntro().then(function () {
      var i = 0;
      function siguienteParte() {
        if (i >= C.partes.length) { return terminar(true, false); }
        var info = C.partes[i++];
        return pedirParte(info.parte).then(function (datos) {
          if (datos.descanso) { return terminar(false, true); }
          // Si el niño actualizó la página, lo ya contestado cuenta para la barra y no se repite.
          respondidas += datos.respondidos.length;
          avance();
          if (datos.respondidos.length >= datos.items.length) { return siguienteParte(); }
          return pantallaParte(info, datos.demo).then(function () {
            var k = 0;
            function siguientePregunta() {
              // Preguntas ya contestadas (si el niño actualizó la página) no se repiten.
              while (k < datos.items.length && datos.respondidos.indexOf(datos.items[k].orden) >= 0) { k++; }
              if (k >= datos.items.length) { return siguienteParte(); }
              var item = datos.items[k++];
              return preguntar(info, item).then(function () {
                if (terminado || cerrando) { return null; }
                // Se terminó el tiempo de juego: se guarda lo hecho y se va a descansar.
                if (agotado()) { return terminar(false, true); }
                return siguientePregunta();
              });
            }
            return siguientePregunta();
          });
        });
      }
      return siguienteParte();
    });
  }

  jugar().catch(function (e) {
    if (window.console) { console.error(e); }
    toast(U('salioMal'));
  });
})();
