{{--
    PARCIAL: estudiante/_limite-tiempo.blade.php  (RN-L10)
    =========================================================================
    Barra delgada y suave arriba de la pantalla que muestra cuánto tiempo de
    juego queda en la sesión. Sin números ni cuenta regresiva (no apura al
    niño). Cuando queda poco (<= 60 s o <= 15 %) cambia de color y aparece un
    aviso: "terminá este ejercicio y descansamos". El corte real lo hace el
    servidor (middleware LimiteTiempoJuego) en la pantalla siguiente.
    Se incluye desde layouts/estudiante.blade.php solo si hay `$tiempoJuego`.
    Guaraní: BORRADOR a validar.
    =========================================================================
--}}
<div id="barraTiempo" role="progressbar" aria-label="Tiempo de juego que queda"
     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $tiempoJuego['limite_seg'] > 0 ? round(100 * $tiempoJuego['restante_seg'] / $tiempoJuego['limite_seg']) : 100 }}"
     data-restante="{{ (int) $tiempoJuego['restante_seg'] }}"
     data-limite="{{ (int) $tiempoJuego['limite_seg'] }}"
     style="position:fixed;left:0;right:0;top:0;height:6px;z-index:1200;background:rgba(27,51,97,.15)">
    <div id="barraTiempoRelleno"
         style="height:100%;width:100%;background:#0E6B7D;border-radius:0 3px 3px 0;transition:width 1s linear, background-color .6s"></div>
</div>
<div id="avisoTiempo" role="status" aria-live="polite"
     style="display:none;position:fixed;left:50%;top:14px;transform:translateX(-50%);z-index:1200;
            width:calc(100% - 32px);max-width:420px;text-align:center;background:#FFF3C4;color:#1B3361;
            border:3px solid #FFC94D;border-radius:20px;padding:10px 14px;font-weight:700;line-height:1.3;
            box-shadow:0 4px 0 rgba(0,0,0,.2)">
    <div>🦜 ¡Ya casi! Terminá este ejercicio y descansamos un ratito.</div>
    <div style="font-size:.95rem">Mbyky! Emohu'ã ko ejercicio ha ñapytu'u sapy'ami.</div>
</div>
<script>
(function () {
    var barra = document.getElementById('barraTiempo');
    if (!barra) return;
    var relleno = document.getElementById('barraTiempoRelleno');
    var aviso = document.getElementById('avisoTiempo');
    var limite = parseInt(barra.dataset.limite, 10) || 0;
    var fin = Date.now() + (parseInt(barra.dataset.restante, 10) || 0) * 1000;
    var avisado = false;

    function tic() {
        var seg = Math.max(0, (fin - Date.now()) / 1000);
        var pct = limite > 0 ? Math.min(100, 100 * seg / limite) : 100;
        relleno.style.width = pct + '%';
        barra.setAttribute('aria-valuenow', Math.round(pct));
        if (seg <= 60 || pct <= 15) {
            relleno.style.background = '#FFC94D';
            if (!avisado) {
                avisado = true;
                aviso.style.display = 'block';
                setTimeout(function () { aviso.style.display = 'none'; }, 6000);
            }
        }
        if (seg <= 0) clearInterval(id);
    }
    var id = setInterval(tic, 1000);
    tic();
})();
</script>
