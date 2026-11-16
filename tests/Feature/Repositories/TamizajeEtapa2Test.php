<?php

namespace Tests\Feature\Repositories;

use App\Models\AsignacionDocente;
use App\Models\ConfigInstitucion;
use App\Models\DetalleNumeros;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Models\RespuestaNumeros;
use App\Models\TamizajeAviso;
use App\Models\TamizajeEstado;
use App\Models\TamizajeFicha;
use App\Models\Usuario;
use App\Repositories\SesionRepository;
use App\Services\NumerosService;
use App\Services\TamizajeService;
use Database\Seeders\MatiNumerosSeeder;

/**
 * «Mati y los números» — Etapa 2: semáforo, campanita, ficha de observación, viaje no válido,
 * cortes de la institución, fecha de nacimiento y saludo de cumpleaños.
 */
class TamizajeEtapa2Test extends RepositorioTestCase
{
    private const PARTES = [
        1 => [2 => 'numeros_cantidades', 3 => 'numeros_cantidades', 5 => 'numeros_cantidades'],
        2 => [4 => 'sentido_cantidad', 6 => 'sentido_cantidad', 7 => 'sentido_cantidad'],
        3 => [8 => 'signos_calculo', 9 => 'signos_calculo', 10 => 'numeros_cantidades'],
    ];

    private Usuario $usuario;
    private Docente $docente;
    private Juego $juego;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MatiNumerosSeeder::class);
        $this->juego = Juego::where('nombre_es', MatiNumerosSeeder::NOMBRE_ES)->firstOrFail();

        $this->usuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente = Docente::where('usuario_id', $this->usuario->id)->firstOrFail();

        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);

        $this->actingAs($this->usuario);
    }

    // ── Ayudas ───────────────────────────────────────────────────────────

    private function alumnoPropio(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    private function alumnoAjeno(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', '!=', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    /**
     * Deja un viaje ya hecho en la base (sin jugar por HTTP): sesión, detalle y 10 respuestas por parte
     * más una del calentamiento. $pct = % de aciertos por área (100 si no se indica).
     */
    private function viajeHecho(Estudiante $est, int $ciclo, int $viaje, array $pct = [], array $o = []): DetalleNumeros
    {
        $grado = (int) $est->matriculaActiva->grado;
        $nivel = Nivel::where('juego_id', $this->juego->id)->where('grado', $grado)->where('numero_nivel', $viaje)->firstOrFail();

        $sesion = app(SesionRepository::class)->crear([
            'estudiante_id' => $est->id,
            'juego_id'      => $this->juego->id,
            'nivel_id'      => $nivel->id,
            'fecha_inicio'  => now()->subMinutes(5),
            'fecha_fin'     => $o['en_curso'] ?? false ? null : now(),
            'estado'        => ($o['en_curso'] ?? false) ? 'en_curso' : 'completada',
        ]);

        $filas = [];
        $filas[] = $this->fila($sesion->id, 1, 0, 'velocidad', true, [], 0);
        foreach (self::PARTES[$viaje] as $parte => $area) {
            $p = $pct[$area] ?? 100;
            for ($i = 0; $i < 10; $i++) {
                $filas[] = $this->fila($sesion->id, $parte, $i, $area, ($i * 10) < $p, $o, $i);
            }
        }
        RespuestaNumeros::insert($filas);

        return DetalleNumeros::create([
            'sesion_id'       => $sesion->id,
            'viaje'           => $viaje,
            'ciclo'           => $ciclo,
            'semilla'         => 12345,
            'grado'           => $grado,
            'partes_json'     => [],
            'completa'        => !($o['en_curso'] ?? false) && ($o['completa'] ?? true),
            'confiable'       => $o['confiable'] ?? true,
            'valida'          => $o['valida'] ?? true,
            'total_preguntas' => count($filas),
            'respondidas'     => count($filas),
        ]);
    }

    private function fila(int $sesionId, int $parte, int $orden, string $area, bool $bien, array $o, int $i): array
    {
        return [
            'sesion_id' => $sesionId, 'parte' => $parte, 'orden' => $orden, 'item_clave' => "t:$parte:$orden",
            'area' => $area, 'nivel' => $o['nivel'] ?? 2, 'fija' => $i < 5 ? 1 : 0, 'cero' => 0,
            'respuesta' => '1', 'es_correcto' => $bien ? 1 : 0, 'tipo_error' => $bien ? null : 'lejano',
            'tiempo_ms' => 2500, 'azar' => 0, 'error_pct' => null, 'respondido_en' => now(),
        ];
    }

    /** Un ciclo entero (los tres viajes). */
    private function cicloHecho(Estudiante $est, int $ciclo, array $pct = [], array $o = []): void
    {
        foreach ([1, 2, 3] as $viaje) {
            $this->viajeHecho($est, $ciclo, $viaje, $pct, $o);
        }
    }

    private function servicio(): TamizajeService
    {
        return app(TamizajeService::class);
    }

    // ── Semáforo y avisos ────────────────────────────────────────────────

    public function test_sin_ciclos_completos_la_lista_dice_que_faltan_datos()
    {
        $r = $this->get(route('docente.tamizaje'))->assertOk();

        $r->assertSee('Atención numérica');
        $r->assertSee('Todavía sin datos suficientes');
        $r->assertSee('Esto no es un diagnóstico');
    }

    public function test_dos_ciclos_buenos_dan_verde_y_no_avisan()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);
        $this->assertNull($this->servicio()->actualizar($a->id)['resultado']['semaforo'], 'un ciclo no alcanza');

        $this->cicloHecho($a, 2);
        $res = $this->servicio()->actualizar($a->id);

        $this->assertSame('verde', $res['resultado']['semaforo']);
        $this->assertNull($res['aviso']);
        $this->assertSame('verde', TamizajeEstado::where('estudiante_id', $a->id)->firstOrFail()->semaforo);
        $this->assertSame(0, TamizajeAviso::count());
    }

    public function test_empeorar_a_amarillo_o_rojo_avisa_una_sola_vez_y_mejorar_no_avisa()
    {
        $a = $this->alumnoPropio();
        $mal = ['sentido_cantidad' => 70];   // 70 % = amarillo

        $this->cicloHecho($a, 1, $mal);
        $this->cicloHecho($a, 2, $mal);
        $this->servicio()->actualizar($a->id);
        $this->assertSame(1, TamizajeAviso::where('estudiante_id', $a->id)->count());
        $aviso = TamizajeAviso::firstOrFail();
        $this->assertSame('amarillo', $aviso->semaforo);
        $this->assertSame($this->docente->id, (int) $aviso->docente_id);
        $this->assertNull($aviso->leido_en);

        // Recalcular sin novedades no repite el aviso.
        $this->servicio()->actualizar($a->id);
        $this->assertSame(1, TamizajeAviso::count());

        // Empeora a rojo: segundo aviso.
        $this->cicloHecho($a, 3, ['sentido_cantidad' => 20]);
        $this->servicio()->actualizar($a->id);
        $this->assertSame('rojo', TamizajeEstado::firstOrFail()->semaforo);
        $this->assertSame(['amarillo', 'rojo'], TamizajeAviso::orderBy('id')->pluck('semaforo')->all());

        // Mejora (los ciclos nuevos son buenos): sin aviso nuevo.
        foreach ([4, 5, 6] as $c) {
            $this->cicloHecho($a, $c);
        }
        $this->servicio()->actualizar($a->id);
        $this->assertSame('verde', TamizajeEstado::firstOrFail()->semaforo);
        $this->assertSame(2, TamizajeAviso::count());
    }

    public function test_cerrar_un_viaje_completo_recalcula_el_semaforo_del_nino()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);
        $this->viajeHecho($a, 2, 1);
        $this->viajeHecho($a, 2, 2);
        $this->assertSame(0, TamizajeEstado::count());

        // El tercer viaje del ciclo 2 queda «en curso» con todas sus respuestas y se cierra como el juego.
        $detalle = $this->viajeHecho($a, 2, 3, [], ['en_curso' => true]);
        $res = app(NumerosService::class)->cerrar($detalle->sesion);

        $this->assertTrue($res['completa']);
        $estado = TamizajeEstado::where('estudiante_id', $a->id)->first();
        $this->assertNotNull($estado, 'cerrar el viaje debe recalcular el semáforo');
        $this->assertSame('verde', $estado->semaforo);
    }

    public function test_la_campanita_muestra_el_aviso_y_verlo_lo_marca_como_leido()
    {
        $a = $this->alumnoPropio();
        $mal = ['sentido_cantidad' => 40];
        $this->cicloHecho($a, 1, $mal);
        $this->cicloHecho($a, 2, $mal);
        $this->servicio()->actualizar($a->id);

        $this->get(route('docente.panel'))->assertOk()
            ->assertSee('Avisos de «Mati y los números»')
            ->assertSee('Avisos (1 sin leer)', false)
            ->assertSee($a->apellido)
            ->assertSee('Mirar con más atención y conversar');

        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk();

        $this->assertNotNull(TamizajeAviso::firstOrFail()->leido_en);
        $this->get(route('docente.panel'))->assertSee('No hay avisos nuevos.');
    }

    public function test_marcar_todos_los_avisos_como_leidos()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1, ['sentido_cantidad' => 40]);
        $this->cicloHecho($a, 2, ['sentido_cantidad' => 40]);
        $this->servicio()->actualizar($a->id);
        $this->assertSame(1, TamizajeAviso::sinLeer()->count());

        $this->post(route('docente.avisos.leer'))->assertRedirect();

        $this->assertSame(0, TamizajeAviso::sinLeer()->count());
    }

    public function test_la_lista_ordena_primero_a_quienes_hay_que_mirar()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1, ['sentido_cantidad' => 40]);
        $this->cicloHecho($a, 2, ['sentido_cantidad' => 40]);
        $this->servicio()->actualizar($a->id);

        $r = $this->get(route('docente.tamizaje'))->assertOk();
        $filas = $r->viewData('filas');

        $this->assertSame($a->id, $filas->first()['estudiante']->id);
        $this->assertSame('rojo', $filas->first()['semaforo']);
        $this->assertSame(1, $r->viewData('cuentas')['rojo']);
        $this->assertSame($filas->count() - 1, $r->viewData('cuentas')['sin']);
    }

    // ── Detalle, permisos y ficha ────────────────────────────────────────

    public function test_el_detalle_muestra_las_areas_los_viajes_y_las_preguntas()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);
        $this->cicloHecho($a, 2, ['sentido_cantidad' => 70]);

        $r = $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk();

        $r->assertSee('Números y cantidades')
            ->assertSee('Sentido de la cantidad')
            ->assertSee('Signos y cálculo')
            ->assertSee('Ciclo 2 · Viaje 2')
            ->assertSee('Copiar resumen')
            ->assertSee('Esto no es un diagnóstico');
        $this->assertSame(2, $r->viewData('resultado')['ciclos_completos']);
        $this->assertStringContainsString('Mati y los números — resumen de', $r->viewData('resumen'));
        $this->assertStringContainsString($a->nombre, $r->viewData('resumen'));
    }

    public function test_otro_docente_no_ve_el_detalle_ni_puede_cambiar_nada()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);
        $detalle = DetalleNumeros::firstOrFail();

        $this->actingAs(Usuario::where('email', 'docente2@unae.edu.py')->firstOrFail());

        $this->get(route('docente.tamizaje.alumno', $a->id))->assertRedirect(route('docente.tamizaje'));
        $this->post(route('docente.tamizaje.ficha', $a->id), ['nota' => 'x'])->assertSessionHasErrors('error');
        $this->post(route('docente.tamizaje.validez', $detalle->id), ['accion' => 'invalidar', 'motivo' => 'otro'])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, TamizajeFicha::count());
        $this->assertTrue((bool) $detalle->fresh()->valida);
    }

    public function test_el_docente_no_ve_alumnos_ajenos_en_la_lista()
    {
        $ajeno = $this->alumnoAjeno();
        $ids = $this->get(route('docente.tamizaje'))->assertOk()->viewData('filas')->pluck('estudiante.id');

        $this->assertFalse($ids->contains($ajeno->id));
    }

    public function test_guardar_la_ficha_de_observacion_con_casillas_y_nota()
    {
        $a = $this->alumnoPropio();

        $this->post(route('docente.tamizaje.ficha', $a->id), [
            'observaciones' => ['cuenta_dedos', 'se_frustra'],
            'nota'          => '  Se pone nervioso en los controles.  ',
        ])->assertRedirect(route('docente.tamizaje.alumno', $a->id))->assertSessionHas('mensaje');

        $f = TamizajeFicha::where('estudiante_id', $a->id)->firstOrFail();
        $this->assertSame(['cuenta_dedos', 'se_frustra'], $f->observaciones);
        $this->assertSame('Se pone nervioso en los controles.', $f->nota);
        $this->assertSame($this->docente->id, (int) $f->docente_id);

        // Guardar de nuevo reemplaza, no duplica; todo es opcional.
        $this->post(route('docente.tamizaje.ficha', $a->id), []);
        $f = TamizajeFicha::where('estudiante_id', $a->id)->firstOrFail();
        $this->assertSame(1, TamizajeFicha::count());
        $this->assertSame([], $f->observaciones);
        $this->assertNull($f->nota);
    }

    public function test_la_ficha_rechaza_casillas_inventadas_y_notas_largas()
    {
        $a = $this->alumnoPropio();

        $this->post(route('docente.tamizaje.ficha', $a->id), ['observaciones' => ['tiene_discalculia']])
            ->assertSessionHasErrors('observaciones.0');
        $this->post(route('docente.tamizaje.ficha', $a->id), ['nota' => str_repeat('a', 301)])
            ->assertSessionHasErrors('nota');

        $this->assertSame(0, TamizajeFicha::count());
    }

    // ── Viaje no válido (RF-20) ──────────────────────────────────────────

    public function test_marcar_un_viaje_no_valido_lo_saca_del_semaforo_y_el_nino_lo_vuelve_a_hacer()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);
        $this->cicloHecho($a, 2);
        $this->servicio()->actualizar($a->id);
        $this->assertSame('verde', TamizajeEstado::firstOrFail()->semaforo);

        $viaje = DetalleNumeros::where('ciclo', 1)->where('viaje', 2)->firstOrFail();
        $this->post(route('docente.tamizaje.validez', $viaje->id), ['accion' => 'invalidar', 'motivo' => 'distraido'])
            ->assertRedirect(route('docente.tamizaje.alumno', $a->id))->assertSessionHas('mensaje');

        $viaje->refresh();
        $this->assertFalse((bool) $viaje->valida);
        $this->assertSame('distraido', $viaje->motivo_no_valida);
        $this->assertNotNull($viaje->marcada_no_valida_en);
        $this->assertNull(TamizajeEstado::firstOrFail()->semaforo, 'el ciclo 1 quedó incompleto: vuelve a faltar un ciclo');

        // Le vuelve a tocar justo ese viaje (no se desordenan los ciclos).
        $estado = app(NumerosService::class)->estadoDe($a->id, $this->juego->id);
        $this->assertSame(1, $estado['ciclo']);
        $this->assertSame(2, $estado['viaje']);
        $this->assertSame([1, 3], $estado['viajes_hechos']);

        // Volver a contarlo.
        $this->post(route('docente.tamizaje.validez', $viaje->id), ['accion' => 'validar']);
        $this->assertTrue((bool) $viaje->fresh()->valida);
        $this->assertNull($viaje->fresh()->motivo_no_valida);
        $this->assertSame('verde', TamizajeEstado::firstOrFail()->semaforo);
    }

    public function test_la_validez_rechaza_acciones_y_motivos_inventados()
    {
        $a = $this->alumnoPropio();
        $detalle = $this->viajeHecho($a, 1, 1);

        $this->post(route('docente.tamizaje.validez', $detalle->id), ['accion' => 'borrar'])->assertSessionHasErrors('accion');
        $this->post(route('docente.tamizaje.validez', $detalle->id), ['accion' => 'invalidar', 'motivo' => 'porque_si'])
            ->assertSessionHasErrors('motivo');
        $this->assertTrue((bool) $detalle->fresh()->valida);
    }

    public function test_el_estado_del_nino_sin_huecos_sigue_el_orden_de_siempre()
    {
        $a = $this->alumnoPropio();
        $svc = app(NumerosService::class);

        $e = $svc->estadoDe($a->id, $this->juego->id);
        $this->assertSame([1, 1, 0], [$e['ciclo'], $e['viaje'], $e['hechos_en_ciclo']]);

        $this->viajeHecho($a, 1, 1);
        $this->viajeHecho($a, 1, 2);
        $e = $svc->estadoDe($a->id, $this->juego->id);
        $this->assertSame([1, 3, 2, 2], [$e['ciclo'], $e['viaje'], $e['hechos_en_ciclo'], $e['completos']]);

        $this->viajeHecho($a, 1, 3);
        $e = $svc->estadoDe($a->id, $this->juego->id);
        $this->assertSame([2, 1, 0, 3], [$e['ciclo'], $e['viaje'], $e['hechos_en_ciclo'], $e['completos']]);
    }

    // ── Datos de demostración ────────────────────────────────────────────

    public function test_el_comando_demo_crea_viajes_de_prueba_calcula_el_semaforo_y_se_limpia()
    {
        $a = $this->alumnoPropio();

        $this->artisan('numeros:demo', ['codigo' => $a->codigo_acceso, '--perfil' => 'rojo', '--ciclos' => 2])->assertExitCode(0);

        $this->assertSame(6, DetalleNumeros::count());
        $this->assertSame('rojo', TamizajeEstado::where('estudiante_id', $a->id)->firstOrFail()->semaforo);
        $this->assertSame(1, TamizajeAviso::where('estudiante_id', $a->id)->count());
        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk()->assertSee('Mirar con más atención y conversar');

        $this->artisan('numeros:demo', ['codigo' => $a->codigo_acceso, '--limpiar' => true])->assertExitCode(0);

        $this->assertSame(0, DetalleNumeros::count());
        $this->assertSame(0, RespuestaNumeros::count());
        $this->assertSame(0, TamizajeAviso::count());
        $this->assertNull(TamizajeEstado::where('estudiante_id', $a->id)->firstOrFail()->semaforo);
    }

    public function test_el_comando_demo_rechaza_un_codigo_o_un_perfil_que_no_existen()
    {
        $this->artisan('numeros:demo', ['codigo' => 'NO-EXISTE'])->assertExitCode(1);
        $this->artisan('numeros:demo', ['codigo' => $this->alumnoPropio()->codigo_acceso, '--perfil' => 'azul'])->assertExitCode(1);
        $this->assertSame(0, DetalleNumeros::count());
    }

    // ── Cortes de la institución ─────────────────────────────────────────

    public function test_los_cortes_de_la_institucion_cambian_el_color()
    {
        $a = $this->alumnoPropio();
        $mal = ['sentido_cantidad' => 70];
        $this->cicloHecho($a, 1, $mal);
        $this->cicloHecho($a, 2, $mal);

        $this->assertSame('amarillo', $this->servicio()->calcular($a->id)['semaforo'], 'con 75/60 el 70 % es amarillo');

        ConfigInstitucion::where('institucion_id', $a->matriculaActiva->institucion_id)
            ->update(['tamizaje_corte_verde' => 90, 'tamizaje_corte_amarillo' => 80]);

        $this->assertSame('rojo', $this->servicio()->calcular($a->fresh()->id)['semaforo'], 'con 90/80 el 70 % es rojo');
    }

    public function test_el_administrador_guarda_los_cortes_y_se_validan()
    {
        $this->actingAs(Usuario::where('email', 'admin@unae.edu.py')->firstOrFail());
        $inst = $this->alumnoPropio()->matriculaActiva->institucion;
        $base = ['dias_reinicio_racha_default' => 3, 'meses_conservacion_egresados' => 4];

        $this->get(route('admin.instituciones.config', $inst->id))->assertOk()->assertSee('Cortes del semáforo');

        $this->put(route('admin.instituciones.config.guardar', $inst->id), $base + [
            'tamizaje_corte_verde' => 80, 'tamizaje_corte_amarillo' => 65,
        ])->assertSessionHas('mensaje');
        $c = ConfigInstitucion::where('institucion_id', $inst->id)->firstOrFail();
        $this->assertSame([80, 65], [(int) $c->tamizaje_corte_verde, (int) $c->tamizaje_corte_amarillo]);

        // El amarillo no puede ser igual o mayor que el verde.
        $this->put(route('admin.instituciones.config.guardar', $inst->id), $base + [
            'tamizaje_corte_verde' => 60, 'tamizaje_corte_amarillo' => 70,
        ])->assertSessionHasErrors('tamizaje_corte_amarillo');
        // Fuera de rango.
        $this->put(route('admin.instituciones.config.guardar', $inst->id), $base + [
            'tamizaje_corte_verde' => 101, 'tamizaje_corte_amarillo' => 70,
        ])->assertSessionHasErrors('tamizaje_corte_verde');
        $this->assertSame([80, 65], [(int) $c->fresh()->tamizaje_corte_verde, (int) $c->fresh()->tamizaje_corte_amarillo]);

        // Sin los campos (formularios viejos) no se tocan.
        $this->put(route('admin.instituciones.config.guardar', $inst->id), $base)->assertSessionHas('mensaje');
        $this->assertSame(80, (int) $c->fresh()->tamizaje_corte_verde);
    }

    // ── Fecha de nacimiento ──────────────────────────────────────────────

    private function datosCuenta(array $extra = []): array
    {
        // $extra va primero: con «+» gana el arreglo de la izquierda, y así los datos que se pasan pisan a los de siempre.
        return $extra + ['nombre' => 'Lucila', 'apellido' => 'Gómez', 'pin' => '4321', 'pin_confirmation' => '4321'];
    }

    public function test_crear_cuenta_guarda_la_fecha_de_nacimiento_opcional()
    {
        $anio = (int) date('Y') - 7;

        $this->post(route('docente.cuentas.crear'), $this->datosCuenta(['nac_dia' => 5, 'nac_mes' => 3, 'nac_anio' => $anio]))
            ->assertRedirect(route('docente.cuentas'));
        $this->assertSame("$anio-03-05", Estudiante::where('nombre', 'Lucila')->firstOrFail()->fecha_nacimiento->toDateString());

        $this->post(route('docente.cuentas.crear'), $this->datosCuenta(['nombre' => 'Mateo']))->assertRedirect(route('docente.cuentas'));
        $this->assertNull(Estudiante::where('nombre', 'Mateo')->firstOrFail()->fecha_nacimiento);
    }

    public function test_crear_cuenta_rechaza_fechas_incompletas_o_imposibles()
    {
        $anio = (int) date('Y') - 7;

        $this->post(route('docente.cuentas.crear'), $this->datosCuenta(['nac_dia' => 5, 'nac_anio' => $anio]))
            ->assertSessionHasErrors('nac_dia');
        $this->post(route('docente.cuentas.crear'), $this->datosCuenta(['nac_dia' => 31, 'nac_mes' => 4, 'nac_anio' => $anio]))
            ->assertSessionHasErrors('nac_dia');
        $this->post(route('docente.cuentas.crear'), $this->datosCuenta(['nac_dia' => 5, 'nac_mes' => 3, 'nac_anio' => (int) date('Y') - 30]))
            ->assertSessionHasErrors('nac_dia');

        $this->assertSame(0, Estudiante::where('nombre', 'Lucila')->count(), 'con la fecha mal no se crea la cuenta');
    }

    public function test_editar_cuenta_cambia_quita_o_deja_la_fecha_de_nacimiento()
    {
        $a = $this->alumnoPropio();
        $anio = (int) date('Y') - 8;
        $guardar = ['accion' => 'guardar', 'nombre' => $a->nombre, 'apellido' => $a->apellido];

        $this->put(route('docente.cuentas.editar', $a->id), $guardar + ['nac_dia' => 12, 'nac_mes' => 10, 'nac_anio' => $anio])
            ->assertSessionHas('mensaje');
        $this->assertSame("$anio-10-12", $a->fresh()->fecha_nacimiento->toDateString());

        // Un formulario que no trae la fecha no la borra.
        $this->put(route('docente.cuentas.editar', $a->id), $guardar)->assertSessionHas('mensaje');
        $this->assertSame("$anio-10-12", $a->fresh()->fecha_nacimiento->toDateString());

        // Con los tres campos vacíos se quita.
        $this->put(route('docente.cuentas.editar', $a->id), $guardar + ['nac_dia' => '', 'nac_mes' => '', 'nac_anio' => ''])
            ->assertSessionHas('mensaje');
        $this->assertNull($a->fresh()->fecha_nacimiento);

        // Una fecha mala no cambia nada.
        $this->put(route('docente.cuentas.editar', $a->id), $guardar + ['nac_dia' => 30, 'nac_mes' => 2, 'nac_anio' => $anio])
            ->assertSessionHasErrors('nac_dia');
        $this->assertNull($a->fresh()->fecha_nacimiento);
    }

    public function test_la_pantalla_de_cuentas_ofrece_la_fecha_de_nacimiento()
    {
        $this->get(route('docente.cuentas'))->assertOk()
            ->assertSee('name="nac_dia"', false)
            ->assertSee('name="nac_mes"', false)
            ->assertSee('name="nac_anio"', false)
            ->assertSee('Fecha de nacimiento');
    }

    // ── Saludo de cumpleaños ─────────────────────────────────────────────

    public function test_mati_saluda_en_el_menu_el_dia_del_cumpleanos_en_castellano_y_guarani()
    {
        $e = Estudiante::first();
        $e->update(['fecha_nacimiento' => now()->subYears(7)->toDateString()]);
        $this->actingAs($e, 'estudiante');

        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();
        $this->assertStringContainsString('Feliz cumpleaños, ' . $e->nombre, $html);
        $this->assertStringContainsString('Vy&#039;apavẽ', $html);

        // En el menú se ve cada vez que entra ese día.
        $this->assertStringContainsString('Feliz cumpleaños', $this->get(route('estudiante.juegos'))->getContent());

        // En el menú, Mati festeja animado; con «reducir movimiento» se usa la imagen quieta.
        $this->assertStringContainsString('mati-cumple-anim.webp', $html);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $html);
        $this->assertFileExists(public_path('img/guara/mati-cumple-anim.webp'));
        $this->assertFileExists(public_path('img/guara/mati-cumple.webp'));
    }

    public function test_sin_cumpleanos_hoy_o_sin_fecha_no_hay_saludo()
    {
        $e = Estudiante::first();
        $this->actingAs($e, 'estudiante');
        $this->assertStringNotContainsString('Feliz cumpleaños', $this->get(route('estudiante.juegos'))->getContent());

        $e->update(['fecha_nacimiento' => now()->subYears(7)->addDays(3)->toDateString()]);
        $this->assertStringNotContainsString('Feliz cumpleaños', $this->get(route('estudiante.juegos'))->getContent());
    }

    public function test_dentro_de_un_juego_el_saludo_aparece_una_sola_vez_por_dia()
    {
        $e = Estudiante::first();
        $e->update(['fecha_nacimiento' => now()->subYears(7)->toDateString()]);
        $this->actingAs($e, 'estudiante');

        $html = $this->get(route('estudiante.progreso'))->getContent();
        $this->assertStringContainsString('cumple-aviso', $html);
        $this->assertStringNotContainsString('mati-cumple-anim.webp', $html, 'Dentro de un juego Mati va quieto: no distrae.');
        $this->assertStringContainsString('mati-cumple.webp', $html);

        // Ya se lo saludó hoy: dentro de los juegos no se repite.
        $this->withSession(['cumple_saludado' => now()->toDateString()]);
        $this->assertStringNotContainsString('cumple-aviso', $this->get(route('estudiante.progreso'))->getContent());
    }

    public function test_la_fecha_de_nacimiento_no_viaja_al_informe_de_la_ia()
    {
        $fuente = file_get_contents(app_path('Services/DiagnosticoIAService.php'));

        $this->assertStringNotContainsString('fecha_nacimiento', $fuente);
        $this->assertStringNotContainsString('Cumpleanos', $fuente);
    }
}
