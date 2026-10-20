@extends('layouts.app')
@section('titulo','Gestionar Cuentas')
@section('contenido')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:#1B3361">
      <i class="bi bi-person-plus me-2"></i>Gestionar Cuentas
    </h2>
  </div>

  {{-- Avisos de toda la pantalla (arriba, para que se vean también al guardar desde "Editar") --}}
  @if(!empty($faltaMigracion))
    <div class="alert alert-warning rounded-3 py-2" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <strong>Falta actualizar la base de datos.</strong> Para guardar el correo de la familia hay que ejecutar
      <code>php artisan migrate</code> en la carpeta del proyecto. Mientras tanto, lo demás funciona igual.
    </div>
  @endif
  @if(session('mensaje'))
    <div class="alert alert-success rounded-3 py-2" role="alert">
      {{ session('mensaje') }}
      @if(session('ficha_id'))
        <div class="mt-2">
          <a href="{{ route('docente.cuentas.ficha', ['id' => session('ficha_id'), 'con_pin' => 1]) }}"
             target="_blank" rel="noopener" class="btn btn-sm fw-bold rounded-3"
             style="background:#177040;color:#fff">
            <i class="bi bi-printer me-1"></i>Imprimir ficha con el PIN
          </a>
          <span class="small ms-1">El PIN se muestra <strong>una sola vez</strong>: después ya no se puede ver.</span>
        </div>
      @endif
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-3 py-2" role="alert">
      <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $errors->first() }}
    </div>
  @endif

  <div class="row g-3">

    {{-- ═══ COLUMNA IZQUIERDA: datos del alumno ═══
         Dos modos en el mismo lugar:
           • "Nuevo alumno"  → formulario para crear una cuenta (el de siempre)
           • "Editar alumno" → se completa solo al tocar un alumno de la lista
         No depende de ventanitas (modales): es HTML y JavaScript simples. --}}
    <div class="col-md-5">

      {{-- ── MODO: crear ── --}}
      <div class="card border-0 shadow-sm rounded-4" id="panelCrear">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#177040">
            <i class="bi bi-person-plus-fill me-1"></i>Nuevo alumno — crear cuenta
          </h6>
          <a href="{{ route('ayuda') }}#crear-cuenta" class="small" style="color:#1B3361">¿Necesitás ayuda?</a>
        </div>
        <div class="card-body p-3">
          <form method="POST" action="{{ route('docente.cuentas.crear') }}">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold small">Nombre</label>
              <input type="text" name="nombre" class="form-control rounded-3"
                     value="{{ old('nombre') }}" required placeholder="Ej: Ana">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Apellido</label>
              <input type="text" name="apellido" class="form-control rounded-3"
                     value="{{ old('apellido') }}" required placeholder="Ej: López">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">
                PIN (4 dígitos — elegir junto al estudiante)
              </label>
              <input type="text" name="pin" class="form-control rounded-3 fw-bold"
                     maxlength="4" inputmode="numeric" pattern="\d{4}"
                     placeholder="0000" required style="letter-spacing:8px;font-size:1.2rem">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Confirmar PIN</label>
              <input type="text" name="pin_confirmation" class="form-control rounded-3 fw-bold"
                     maxlength="4" inputmode="numeric" pattern="\d{4}"
                     placeholder="0000" required style="letter-spacing:8px;font-size:1.2rem">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">
                Correo del padre, madre o tutor <span class="text-muted fw-normal">(opcional)</span>
              </label>
              <input type="email" name="email_tutor" class="form-control rounded-3"
                     value="{{ old('email_tutor') }}" maxlength="191" autocomplete="off"
                     placeholder="familia@ejemplo.com">
              <div class="form-text">
                Con este correo y el código del niño/a, la familia entra a ver su avance.
                Si hoy no lo tenés, lo cargás después: tocá al alumno en la lista.
              </div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn fw-bold rounded-3"
                      style="background:#177040;color:white">
                <i class="bi bi-person-check me-1"></i>Crear cuenta y matrícula
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- ── MODO: editar (oculto hasta tocar un alumno) ── --}}
      <div class="card border-0 shadow-sm rounded-4" id="panelEditar" style="display:none;border:2px solid #1B3361 !important">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <h6 class="fw-bold mb-0" style="color:#1B3361">
              <i class="bi bi-pencil-square me-1"></i>Datos del alumno
            </h6>
            <span id="edEstado" class="badge rounded-pill"></span>
          </div>
          <div class="mt-1 small text-muted">
            Código de acceso: <code id="edCodigo" class="fs-6"></code>
          </div>
        </div>
        <div class="card-body p-3">
          <form method="POST" id="formEditar" action="">
            @csrf
            @method('PUT')
            <input type="hidden" name="accion" value="guardar">
            <div class="mb-3">
              <label class="form-label fw-semibold small">Nombre</label>
              <input type="text" name="nombre" id="edNombre" class="form-control rounded-3" required maxlength="100">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Apellido</label>
              <input type="text" name="apellido" id="edApellido" class="form-control rounded-3" required maxlength="100">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">Correo del padre, madre o tutor</label>
              <input type="email" name="nuevo_correo" id="edCorreo" class="form-control rounded-3"
                     maxlength="191" autocomplete="off" placeholder="familia@ejemplo.com">
              <div class="form-text">
                La familia entra con este correo y el código del alumno. Si lo dejás vacío, se quita.
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">
                Nuevo PIN <span class="text-muted fw-normal">(solo si querés cambiarlo)</span>
              </label>
              <input type="text" name="nuevo_pin" id="edPin" class="form-control rounded-3 fw-bold"
                     maxlength="4" inputmode="numeric" pattern="\d{4}" autocomplete="off"
                     placeholder="0000" style="letter-spacing:8px;font-size:1.2rem">
              <div class="form-text">Dejalo vacío para no cambiar el PIN actual.</div>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn fw-bold rounded-3" style="background:#1B3361;color:white">
                <i class="bi bi-check2-circle me-1"></i>Guardar cambios
              </button>
              <a href="#" id="edFicha" target="_blank" rel="noopener" class="btn rounded-3"
                 style="background:#DAE8FC;color:#1B3361;border:1px solid #1B3361">
                <i class="bi bi-printer me-1"></i>Imprimir ficha
              </a>
            </div>
          </form>

          {{-- Activar / desactivar: formulario aparte (no se puede anidar dentro del de arriba) --}}
          <form method="POST" id="formEstado" action="" class="mt-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="accion" id="edAccionEstado" value="desactivar">
            <div class="d-grid">
              <button type="submit" id="edBtnEstado" class="btn btn-sm rounded-3"></button>
            </div>
          </form>

          {{-- Cierre del año lectivo (RF-18): 1.° y 2.° pasan de grado; 3.° egresa --}}
          <form method="POST" id="formCerrar" action="" class="mt-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="accion" value="cerrar_matricula">
            <div class="d-grid">
              <button type="submit" id="edBtnCerrar" class="btn btn-sm rounded-3 btn-outline-primary"></button>
            </div>
          </form>

          <div class="d-grid mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" onclick="cuentaNuevo()">
              <i class="bi bi-plus-circle me-1"></i>Nuevo alumno
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- ═══ COLUMNA DERECHA: lista de cuentas ═══ --}}
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white rounded-top-4 border-0 pt-3 px-3">
          <h6 class="fw-bold mb-0" style="color:#1B3361">
            <i class="bi bi-people-fill me-1"></i>
            Cuentas registradas ({{ $matriculas->count() }})
          </h6>
          <div class="small text-muted mt-1">
            <i class="bi bi-hand-index me-1"></i>Tocá un alumno para ver y editar sus datos a la izquierda.
          </div>
          @if($matriculas->isNotEmpty())
            <a href="{{ route('docente.cuentas.fichas') }}" target="_blank" rel="noopener"
               class="btn btn-sm rounded-3 mt-2"
               style="background:#DAE8FC;color:#1B3361;border:1px solid #1B3361;font-size:.875rem">
              <i class="bi bi-printer me-1"></i>Imprimir fichas del grado
            </a>
          @endif
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead style="background:#f8f9fa">
                <tr>
                  <th class="ps-3 py-2 small">Nombre</th>
                  <th class="py-2 small">Código</th>
                  <th class="text-center py-2 small">Estado</th>
                  <th class="text-center py-2 small">Acción</th>
                </tr>
              </thead>
              <tbody>
                @forelse($matriculas as $mat)
                  <tr class="fila-alumno" style="cursor:pointer"
                      data-id="{{ $mat->estudiante_id }}"
                      data-nombre="{{ $mat->estudiante->nombre }}"
                      data-apellido="{{ $mat->estudiante->apellido }}"
                      data-codigo="{{ $mat->estudiante->codigo_acceso }}"
                      data-correo="{{ $mat->estudiante->email_tutor }}"
                      data-activo="{{ $mat->estudiante->activo ? 1 : 0 }}"
                      data-grado="{{ $mat->grado }}"
                      data-url-editar="{{ route('docente.cuentas.editar', $mat->estudiante_id) }}"
                      data-url-ficha="{{ route('docente.cuentas.ficha', $mat->estudiante_id) }}"
                      onclick="cuentaSeleccionar(this)">
                    <td class="ps-3 small">
                      <div class="fw-bold">
                        {{ $mat->estudiante->apellido }}, {{ $mat->estudiante->nombre }}
                      </div>
                      @if($mat->estudiante->email_tutor)
                        <div class="text-muted" style="font-size:.8rem">
                          <i class="bi bi-envelope"></i> {{ $mat->estudiante->email_tutor }}
                        </div>
                      @else
                        <div style="font-size:.8rem;color:#8A5A00">
                          <i class="bi bi-envelope-exclamation"></i> Sin correo de la familia
                        </div>
                      @endif
                    </td>
                    <td>
                      <code class="small">{{ $mat->estudiante->codigo_acceso }}</code>
                    </td>
                    <td class="text-center">
                      @if($mat->estudiante->activo)
                        <span class="badge rounded-pill bg-success">Activo</span>
                      @else
                        <span class="badge rounded-pill bg-secondary">Inactivo</span>
                      @endif
                    </td>
                    <td class="text-center text-nowrap">
                      <a href="{{ route('docente.cuentas.ficha', $mat->estudiante_id) }}" target="_blank" rel="noopener"
                         class="btn btn-sm rounded-3 me-1"
                         style="background:#DAE8FC;color:#1B3361;font-size:.875rem"
                         title="Imprimir la ficha de acceso"
                         onclick="event.stopPropagation()">
                        <i class="bi bi-printer me-1"></i>Ficha
                      </a>
                      <button type="button" class="btn btn-sm rounded-3"
                              style="background:#FFF2CC;color:#8A5A00;font-size:.875rem">
                        <i class="bi bi-pencil me-1"></i>Editar
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                      Sin cuentas registradas aún.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  /* Alumno seleccionado en la lista */
  .table > tbody > tr.fila-sel > td { background-color: #EAF2FF; box-shadow: inset 0 0 0 9999px #EAF2FF; }
  .table > tbody > tr.fila-sel > td:first-child { box-shadow: inset 0 0 0 9999px #EAF2FF, inset 4px 0 0 #1B3361; }
</style>
@endpush
@push('scripts')
<script>
// Pasa la columna izquierda al modo "Editar alumno" con los datos del alumno tocado.
// JavaScript simple: no depende de Bootstrap (funciona aunque sus scripts no carguen).
function cuentaSeleccionar(fila) {
    var d = fila.dataset;
    document.getElementById('panelCrear').style.display  = 'none';
    document.getElementById('panelEditar').style.display = 'block';

    document.getElementById('formEditar').action = d.urlEditar;
    document.getElementById('formEstado').action = d.urlEditar;
    document.getElementById('edNombre').value   = d.nombre;
    document.getElementById('edApellido').value = d.apellido;
    document.getElementById('edCorreo').value   = d.correo;
    document.getElementById('edPin').value      = '';
    document.getElementById('edCodigo').textContent = d.codigo;
    document.getElementById('edFicha').href = d.urlFicha;

    var activo = d.activo === '1';
    var badge  = document.getElementById('edEstado');
    badge.textContent = activo ? 'Activo' : 'Inactivo';
    badge.className   = 'badge rounded-pill ' + (activo ? 'bg-success' : 'bg-secondary');

    var btn = document.getElementById('edBtnEstado');
    document.getElementById('edAccionEstado').value = activo ? 'desactivar' : 'reactivar';
    btn.textContent = activo ? 'Desactivar cuenta (no podrá entrar a jugar)' : 'Reactivar cuenta';
    btn.className   = 'btn btn-sm rounded-3 ' + (activo ? 'btn-outline-danger' : 'btn-outline-success');
    var grado = parseInt(d.grado, 10);
    document.getElementById('formCerrar').action = d.urlEditar;
    document.getElementById('edBtnCerrar').textContent = grado >= 3
        ? 'Cerrar año: egresa de 3.er grado'
        : 'Cerrar año: pasa a ' + (grado + 1) + '.° grado';
    document.getElementById('formCerrar').onsubmit = function () {
        return confirm(grado >= 3
            ? '¿Cerrar el año de ' + d.nombre + '? Egresa de 3.er grado y su cuenta se desactivará sola cuando se cumplan los meses de conservación de la institución.'
            : '¿Cerrar el año de ' + d.nombre + '? Pasa a ' + (grado + 1) + '.° grado con el mismo docente.');
    };
    document.getElementById('formEstado').onsubmit = function () {
        return activo ? confirm('¿Desactivar la cuenta de ' + d.nombre + '? No podrá entrar a jugar hasta que la reactives.') : true;
    };

    document.querySelectorAll('tr.fila-alumno').forEach(function (tr) { tr.classList.remove('fila-sel'); });
    fila.classList.add('fila-sel');

    // En pantallas chicas, llevar la vista al formulario
    if (window.innerWidth < 768) {
        document.getElementById('panelEditar').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Vuelve al formulario de "Nuevo alumno".
function cuentaNuevo() {
    document.getElementById('panelEditar').style.display = 'none';
    document.getElementById('panelCrear').style.display  = 'block';
    document.querySelectorAll('tr.fila-alumno').forEach(function (tr) { tr.classList.remove('fila-sel'); });
}

@if(session('seleccionado'))
// Después de guardar, deja seleccionado al mismo alumno con sus datos ya actualizados.
(function () {
    var fila = document.querySelector('tr.fila-alumno[data-id="{{ session('seleccionado') }}"]');
    if (fila) cuentaSeleccionar(fila);
})();
@endif
</script>
@endpush
@endsection
