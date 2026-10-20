<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de acceso — MatiGuará</title>
    {{--
        FICHA DE ACCESO IMPRIMIBLE (para llevar a casa)
        - Una por niño/a, dos por hoja A4. Cada ficha se recorta por la línea punteada.
        - Tiene dos partes: "Para jugar" (el niño/a) y "Para la familia".
        - El PIN NO se imprime (el sistema solo guarda su versión cifrada): hay casillas
          para que el niño/a o la docente lo escriban a mano.
        - No usa Bootstrap: se ve igual aunque no haya internet.
        Variables: $fichas = lista de [nombre, codigo, correo, grado, escuela]
    --}}
    <style>
        :root { --azul:#1B3361; --verde:#177040; --sol:#FFC94D; --crema:#FFFDF2; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: "Segoe UI", Arial, sans-serif; color:var(--azul); background:#EEF3F8; }
        .barra { position:sticky; top:0; z-index:5; background:#fff; border-bottom:3px solid var(--azul);
                 padding:10px 16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .barra button, .barra a { font:inherit; font-weight:700; padding:8px 18px; border-radius:10px; cursor:pointer;
                 border:3px solid var(--azul); background:var(--verde); color:#fff; text-decoration:none; }
        .barra a { background:#fff; color:var(--azul); }
        .barra span { font-size:.95rem; }
        .hoja { max-width:200mm; margin:14px auto; }
        .ficha { background:#fff; border:3px solid var(--azul); border-radius:14px; padding:10px 12px 8px; margin-bottom:10mm;
                 break-inside:avoid; page-break-inside:avoid; position:relative; }
        .ficha::after { content:"✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -";
                 position:absolute; left:0; right:0; bottom:-8mm; text-align:center; color:#7a8aa5; font-size:.8rem; white-space:nowrap; overflow:hidden; }
        .cab { display:flex; justify-content:space-between; align-items:center; gap:8px; border-bottom:3px dashed var(--azul); padding-bottom:6px; margin-bottom:8px; }
        .cab .marca { font-size:1.25rem; font-weight:800; }
        .cab .datos { text-align:right; font-size:.8rem; line-height:1.25; }
        .nombre { font-size:1.15rem; font-weight:800; margin-bottom:8px; }
        .cols { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .caja { border:2.5px solid var(--azul); border-radius:12px; padding:8px 10px; background:var(--crema); }
        .caja h2 { margin:0 0 6px; font-size:1rem; }
        .caja.nino h2 { color:var(--verde); }
        .etq { font-size:.78rem; font-weight:700; margin-top:4px; }
        .codigo { font-family:Consolas, monospace; font-size:1.45rem; font-weight:800; letter-spacing:2px; background:#fff;
                  border:2px solid var(--azul); border-radius:8px; padding:2px 8px; display:inline-block; }
        .correo { font-weight:700; word-break:break-all; font-size:.95rem; }
        .sin-correo { background:#FFF2CC; border:2px solid #8A5A00; border-radius:8px; padding:4px 8px; font-size:.85rem; color:#5c3b00; }
        .pin { display:flex; gap:6px; margin:2px 0 4px; }
        .pin i { width:30px; height:36px; border:2.5px solid var(--azul); border-radius:8px; background:#fff; display:block; }
        .pin i.lleno { font-style:normal; font-weight:800; font-size:1.4rem; text-align:center; line-height:31px; font-family:Consolas, monospace; }
        ol { margin:6px 0 0; padding-left:0; list-style:none; font-size:.88rem; }
        ol li { display:flex; gap:6px; align-items:center; margin-top:3px; }
        ol li b { width:22px; height:22px; flex:0 0 22px; border-radius:50%; background:var(--azul); color:#fff; text-align:center; line-height:22px; font-size:.8rem; }
        .pie { margin-top:6px; font-size:.78rem; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        @page { size:A4; margin:10mm; }
        @media print {
            body { background:#fff; }
            .barra { display:none; }
            .hoja { margin:0; max-width:none; }
            .ficha { margin-bottom:14mm; }
            .ficha:nth-child(2n) { margin-bottom:0; }
            .ficha:nth-child(2n)::after { display:none; }
            .ficha:nth-child(2n+1):last-child::after { display:none; }
            .ficha:nth-child(2n) { page-break-after:always; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <button type="button" onclick="window.print()">🖨️ Imprimir</button>
        <a href="{{ route('docente.cuentas') }}">← Volver a Cuentas</a>
        <span>
            {{ count($fichas) }} {{ count($fichas) === 1 ? 'ficha' : 'fichas' }} · dos por hoja · recortá por la línea punteada.
            @if(collect($fichas)->contains(fn ($f) => !empty($f['pin'])))
                Esta ficha <strong>incluye el PIN</strong>, que se muestra una sola vez: imprimila ahora.
            @else
                El PIN <strong>no se imprime</strong>: anotalo a mano en las casillas, junto con el niño o la niña.
            @endif
        </span>
    </div>

    <div class="hoja">
    @foreach($fichas as $f)
        <section class="ficha">
            <div class="cab">
                <div class="marca">🦜 MatiGuará · Ficha de acceso</div>
                <div class="datos">
                    {{ date('Y') }}@if($f['grado']) · {{ $f['grado'] }}.º grado @endif
                    @if($f['escuela'])<br>{{ $f['escuela'] }}@endif
                </div>
            </div>

            <div class="nombre">👧👦 {{ $f['nombre'] }}</div>

            <div class="cols">
                {{-- Para el niño o la niña --}}
                <div class="caja nino">
                    <h2>🎮 Para jugar (niño/a)</h2>
                    <div class="etq">Mi código</div>
                    <span class="codigo">{{ $f['codigo'] }}</span>
                    @if($f['pin'])
                        <div class="etq">Mi PIN secreto (4 números)</div>
                        <div class="pin">
                            @foreach(str_split($f['pin']) as $digito)<i class="lleno">{{ $digito }}</i>@endforeach
                        </div>
                    @else
                        <div class="etq">Mi PIN secreto (4 números, anotalo a mano)</div>
                        <div class="pin"><i></i><i></i><i></i><i></i></div>
                    @endif
                    <ol>
                        <li><b>1</b> Abro MatiGuará</li>
                        <li><b>2</b> Toco <strong>¡Quiero jugar!</strong></li>
                        <li><b>3</b> Escribo mi código</li>
                        <li><b>4</b> Toco mi PIN y <strong>¡Entrar a jugar!</strong></li>
                    </ol>
                </div>

                {{-- Para la familia --}}
                <div class="caja">
                    <h2>👪 Para la familia</h2>
                    <div class="etq">Su correo registrado</div>
                    @if($f['correo'])
                        <div class="correo">{{ $f['correo'] }}</div>
                    @else
                        <div class="sin-correo">Todavía no hay correo cargado. Dígaselo a la docente.</div>
                    @endif
                    <div class="etq">Código de su hijo/a</div>
                    <span class="codigo" style="font-size:1.15rem">{{ $f['codigo'] }}</span>
                    <ol>
                        <li><b>1</b> Abra MatiGuará</li>
                        <li><b>2</b> Toque <strong>Soy padre/madre</strong></li>
                        <li><b>3</b> Escriba su correo y el código</li>
                        <li><b>4</b> Toque <strong>Ingresar</strong></li>
                    </ol>
                </div>
            </div>

            <div class="pie">
                <span>🌐 Dirección: <strong>{{ url('/') }}</strong></span>
                <span>🔒 El PIN es un secreto: no se lo cuento a otros niños.</span>
            </div>
        </section>
    @endforeach
    </div>
</body>
</html>
