<?php

namespace Tests\Feature\Repositories;

use App\Models\DetalleNumeros;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\ProgresoEstudiante;
use App\Models\RespuestaNumeros;
use App\Models\SesionJuego;
use App\Repositories\DocenteRepository;
use App\Repositories\SesionRepository;
use App\Services\NumerosService;
use App\Support\DesbloqueoNiveles;
use App\Support\Numeros\GeneradorItems;
use Database\Seeders\MatiNumerosSeeder;

/**
 * «Mati y los números» — juego de tamizaje.
 * Cubre: seeder, menú (tres viajes como tres niveles), flujo iniciar → tablero → partes →
 * respuestas → cierre, regla adaptativa, toques al azar, viaje incompleto, un viaje por día y
 * que NO entre en puntos, progreso, resúmenes ni diagnóstico (RF-02).
 */
class MatiNumerosTest extends RepositorioTestCase
{
    /** @return array{estudiante:Estudiante,juego:Juego,niveles:array<int,Nivel>,grado:int} */
    private function escenarioNumeros(): array
    {
        $this->seed(MatiNumerosSeeder::class);

        $estudiante = Estudiante::first();
        $grado      = (int) $estudiante->matriculaActiva->grado;
        $juego      = Juego::where('nombre_es', MatiNumerosSeeder::NOMBRE_ES)->firstOrFail();
        $niveles    = [];
        foreach ([1, 2, 3] as $v) {
            $niveles[$v] = Nivel::where('juego_id', $juego->id)->where('grado', $grado)->where('numero_nivel', $v)->firstOrFail();
        }

        return compact('estudiante', 'juego', 'niveles', 'grado');
    }

    /** Inicia el viaje por HTTP (como el niño) y devuelve la sesión creada. */
    private function iniciar(array $e, int $viaje, bool $igual = false): SesionJuego
    {
        $this->actingAs($e['estudiante'], 'estudiante');
        $this->post(route('estudiante.numeros.iniciar', [$e['juego']->id, $e['niveles'][$viaje]->id]), $igual ? ['igual' => 1] : [])
            ->assertRedirect();

        return SesionJuego::where('estudiante_id', $e['estudiante']->id)
            ->where('estado', 'en_curso')->orderByDesc('id')->firstOrFail();
    }

    private function parteJson(SesionJuego $s, int $parte): array
    {
        return $this->getJson(route('estudiante.numeros.parte', [$s->id, $parte]))->assertOk()->json();
    }

    /** Respuesta correcta de una pregunta, leída del detalle guardado (el navegador no la recibe). */
    private function correctaDe(SesionJuego $s, int $parte, int $orden): string
    {
        $it = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json[(string) $parte]['items'][$orden];
        return $it['tipo'] === 'linea' ? (string) $it['datos']['objetivo'] : (string) $it['correcta'];
    }

    private function responder(SesionJuego $s, int $parte, int $orden, string $resp, int $ms = 2500)
    {
        return $this->postJson(route('estudiante.numeros.responder', $s->id), [
            'parte' => $parte, 'orden' => $orden, 'respuesta' => $resp, 'tiempo_ms' => $ms,
        ]);
    }

    /**
     * Juega el viaje entero. $aciertos(parte, orden, fija) decide si esa pregunta se contesta bien.
     * @return int cantidad de preguntas respondidas
     */
    private function jugarViaje(SesionJuego $s, int $viaje, ?callable $aciertos = null, int $ms = 2500): int
    {
        $aciertos ??= fn () => true;
        $n = 0;
        foreach (GeneradorItems::partesDelViaje($viaje) as $parte) {
            $datos = $this->parteJson($s, $parte);
            $guardadas = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json[(string) $parte]['items'];
            foreach ($datos['items'] as $it) {
                $orden = $it['orden'];
                $bien  = $aciertos($parte, $orden, (bool) $guardadas[$orden]['fija']);
                $resp  = $this->correctaDe($s, $parte, $orden);
                if (!$bien) {
                    $resp = $this->respuestaIncorrecta($guardadas[$orden]);
                }
                $this->responder($s, $parte, $orden, $resp, $ms)->assertOk();
                $n++;
            }
        }
        return $n;
    }

    private function respuestaIncorrecta(array $it): string
    {
        switch ($it['tipo']) {
            case 'toque':
                return 'toque';
            case 'linea':
                $o = $it['datos']['objetivo']; $max = $it['datos']['max'];
                return (string) ($o + $max * 0.5 > $max ? $o - $max * 0.5 : $o + $max * 0.5);
            case 'mas_puntos':
            case 'mayor':
                return $it['correcta'] === 'izq' ? 'der' : 'izq';
            case 'signo':
                return $it['correcta'] === '+' ? '-' : '+';
            case 'puntos_rapidos':
                return (string) (((int) $it['correcta'] + 2) % ($it['datos']['max'] + 1));
            default:
                foreach ($it['opciones'] as $o) {
                    $v = (string) (is_array($o) ? $o['valor'] : $o);
                    if ($v !== (string) $it['correcta']) {
                        return $v;
                    }
                }
        }
        return '0';
    }

    private function cerrar(SesionJuego $s, int $seg = 240)
    {
        return $this->postJson(route('estudiante.numeros.cerrar', $s->id), ['duracion_s' => $seg]);
    }

    // ── Seeder y menú ────────────────────────────────────────────────────

    public function test_el_seeder_crea_el_juego_y_nueve_niveles_y_se_puede_correr_dos_veces()
    {
        $e = $this->escenarioNumeros();
        $this->assertSame(9, Nivel::where('juego_id', $e['juego']->id)->count());
        $this->assertSame('numeros', $e['juego']->tipoMecanica->tipo);
        $this->assertTrue($e['juego']->esTamizaje());

        $niveles = Nivel::count();
        $juegos  = Juego::count();
        $this->seed(MatiNumerosSeeder::class);
        $this->assertSame($niveles, Nivel::count());
        $this->assertSame($juegos, Juego::count());
    }

    public function test_el_menu_muestra_el_juego_con_los_tres_viajes_y_solo_el_primero_abierto()
    {
        $e = $this->escenarioNumeros();
        $this->actingAs($e['estudiante'], 'estudiante');

        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();

        $this->assertStringContainsString('Mati y los números', $html);
        $this->assertStringContainsString('nm-viajes', $html);
        $this->assertStringContainsString('Ciclo 1', $html);
        $this->assertStringContainsString(route('estudiante.numeros.iniciar', [$e['juego']->id, $e['niveles'][1]->id]), $html);
        $this->assertSame(1, preg_match_all('/class=""\s+title="Viaje \d/', $html), 'Solo el viaje que toca se puede tocar');
    }

    public function test_el_docente_puede_ocultar_el_juego_para_su_grado()
    {
        $e = $this->escenarioNumeros();
        $m = $e['estudiante']->matriculaActiva;
        app(DocenteRepository::class)->guardarHabilitacion($m->docente_id, $e['juego']->id, $m->grado, false);

        $this->actingAs($e['estudiante'], 'estudiante');
        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Mati y los números', $html);
    }

    // ── Inicio, tablero y partes ─────────────────────────────────────────

    public function test_iniciar_crea_el_viaje_y_el_tablero_se_abre()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $d = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail();
        $this->assertSame(1, (int) $d->viaje);
        $this->assertSame(1, (int) $d->ciclo);
        $this->assertSame($e['grado'], (int) $d->grado);
        $this->assertSame(26, (int) $d->total_preguntas);

        $html = $this->get(route('estudiante.numeros.tablero', $s->id))->assertOk()->getContent();
        $this->assertStringContainsString('NM_CFG', $html);
        $this->assertStringContainsString('numeros.js', $html);
        // El navegador nunca recibe respuestas correctas.
        $this->assertStringNotContainsString('"correcta"', $html);
    }

    public function test_la_parte_no_trae_la_respuesta_correcta_salvo_en_la_demostracion_y_es_estable()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $p2 = $this->parteJson($s, 2);
        $this->assertSame(6, count($p2['items']));
        foreach ($p2['items'] as $it) {
            $this->assertArrayNotHasKey('correcta', $it);
            $this->assertArrayNotHasKey('tipos', $it);
        }
        $this->assertArrayHasKey('correcta', $p2['demo']);

        $this->assertSame($p2['items'], $this->parteJson($s, 2)['items'], 'Al actualizar la página se ve la misma parte');
    }

    public function test_una_parte_que_no_es_del_viaje_se_rechaza()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $this->getJson(route('estudiante.numeros.parte', [$s->id, 4]))->assertStatus(422);
    }

    public function test_otro_alumno_no_puede_usar_la_sesion()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 1);

        $otro = Estudiante::where('id', '!=', $e['estudiante']->id)->first();
        $this->assertNotNull($otro);
        $this->actingAs($otro, 'estudiante');

        $this->getJson(route('estudiante.numeros.parte', [$s->id, 1]))->assertStatus(403);
        $this->responder($s, 1, 0, 'toque')->assertStatus(403);
    }

    // ── Respuestas ───────────────────────────────────────────────────────

    public function test_la_respuesta_del_juego_es_igual_para_aciertos_y_errores_y_se_guarda_todo()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 2);

        $bien = $this->responder($s, 2, 0, $this->correctaDe($s, 2, 0))->assertOk()->json();
        $it1  = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json['2']['items'][1];
        $mal  = $this->responder($s, 2, 1, $this->respuestaIncorrecta($it1))->assertOk()->json();

        $this->assertSame($bien, $mal, 'No se debe revelar si estuvo bien o mal');
        $this->assertSame(['ok' => true, 'duplicada' => false], $bien);

        $f = RespuestaNumeros::where('sesion_id', $s->id)->orderBy('orden')->get();
        $this->assertCount(2, $f);
        $this->assertTrue($f[0]->es_correcto);
        $this->assertFalse($f[1]->es_correcto);
        $this->assertNotNull($f[1]->tipo_error);
        $this->assertSame(2, (int) $f[0]->parte);
        $this->assertSame('numeros_cantidades', $f[0]->area);
        $this->assertSame(2, (int) $f[0]->nivel);
        $this->assertNotNull($f[0]->fija);
        $this->assertSame(2500, (int) $f[0]->tiempo_ms);
    }

    public function test_un_solo_intento_por_pregunta_el_segundo_envio_no_se_guarda()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 2);

        $this->responder($s, 2, 0, $this->correctaDe($s, 2, 0))->assertOk();
        $r = $this->responder($s, 2, 0, $this->correctaDe($s, 2, 0))->assertOk()->json();

        $this->assertTrue($r['duplicada']);
        $this->assertSame(1, RespuestaNumeros::where('sesion_id', $s->id)->count());
    }

    public function test_respuestas_invalidas_se_rechazan()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 2);

        $this->responder($s, 2, 0, 'hola')->assertStatus(422);              // no es una opción
        $this->responder($s, 2, 99, '3')->assertStatus(422);                // la pregunta no existe
        $this->postJson(route('estudiante.numeros.responder', $s->id), ['parte' => 2])->assertStatus(422);
        $this->assertSame(0, RespuestaNumeros::where('sesion_id', $s->id)->count());
    }

    public function test_una_respuesta_equivocada_en_una_pregunta_con_cero_se_marca_como_error_con_el_cero()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 3);

        $items = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json['3']['items'];
        $orden = collect($items)->search(fn ($i) => $i['cero']);
        $this->assertNotFalse($orden, 'La parte 3 debe tener una pregunta fija con cero');

        $this->responder($s, 3, $orden, $this->respuestaIncorrecta($items[$orden]), 3000)->assertOk();

        $this->assertSame('cero', RespuestaNumeros::where('sesion_id', $s->id)->firstOrFail()->tipo_error);
    }

    public function test_una_respuesta_en_menos_de_350_ms_se_marca_como_posible_toque_al_azar()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->parteJson($s, 2);

        $this->responder($s, 2, 0, $this->correctaDe($s, 2, 0), 200)->assertOk();

        $this->assertTrue((bool) RespuestaNumeros::where('sesion_id', $s->id)->firstOrFail()->azar);
    }

    // ── Adaptación ───────────────────────────────────────────────────────

    public function test_tres_aciertos_seguidos_en_preguntas_adaptativas_suben_el_nivel_de_la_parte_siguiente()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $this->parteJson($s, 1);
        $p2 = $this->parteJson($s, 2);
        $this->assertSame(2, $p2['nivel'], 'Se empieza en el nivel esperado');

        $guardadas = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json['2']['items'];
        foreach ($p2['items'] as $it) {
            $this->responder($s, 2, $it['orden'], $guardadas[$it['orden']]['fija']
                ? $this->respuestaIncorrecta($guardadas[$it['orden']])      // las fijas no cuentan para adaptar
                : $this->correctaDe($s, 2, $it['orden']))->assertOk();
        }

        $this->assertSame(3, $this->parteJson($s, 3)['nivel']);
    }

    public function test_dos_errores_seguidos_en_preguntas_adaptativas_bajan_el_nivel_de_la_parte_siguiente()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $p2 = $this->parteJson($s, 2);
        $guardadas = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->partes_json['2']['items'];
        foreach ($p2['items'] as $it) {
            $g = $guardadas[$it['orden']];
            $this->responder($s, 2, $it['orden'], $g['fija'] ? $this->correctaDe($s, 2, $it['orden']) : $this->respuestaIncorrecta($g))->assertOk();
        }

        $this->assertSame(1, $this->parteJson($s, 3)['nivel']);
    }

    // ── Cierre ───────────────────────────────────────────────────────────

    public function test_un_viaje_completo_queda_completo_confiable_y_sin_puntos_ni_progreso()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $n = $this->jugarViaje($s, 1);
        $this->assertSame(26, $n);

        $r = $this->cerrar($s)->assertOk()->json();
        $this->assertTrue($r['completa']);
        $this->assertSame(1, $r['viaje']);
        $this->assertArrayHasKey('figurita', $r);
        $this->assertArrayNotHasKey('puntos', $r);
        $this->assertArrayNotHasKey('estrellas', $r);

        $s->refresh();
        $this->assertSame('completada', $s->estado);
        $this->assertSame(0, (int) $s->puntaje_obtenido);
        $this->assertSame(0, (int) $s->aciertos);
        $this->assertFalse((bool) $s->nivel_superado);

        $d = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail();
        $this->assertTrue($d->completa);
        $this->assertTrue($d->confiable);
        $this->assertSame(26, (int) $d->respondidas);
        $this->assertSame(240, (int) $d->duracion_s);
        $this->assertNotNull($d->tiempo_base_ms);
        $this->assertNotNull($d->nivel_final);

        $this->assertSame(0, ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)->where('juego_id', $e['juego']->id)->count(),
            'El tamizaje no entra en el progreso');
    }

    public function test_cerrar_dos_veces_no_cambia_nada()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);

        $this->cerrar($s)->assertOk();
        $r = $this->cerrar($s, 999)->assertOk()->json();

        $this->assertTrue($r['completa']);
        $this->assertSame(240, (int) DetalleNumeros::where('sesion_id', $s->id)->firstOrFail()->duracion_s);
    }

    public function test_un_viaje_cortado_queda_incompleto_y_no_cuenta_para_el_ciclo()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $this->parteJson($s, 1);
        foreach (range(0, 3) as $orden) {
            $this->responder($s, 1, $orden, 'toque', 400)->assertOk();
        }

        $r = $this->cerrar($s, 40)->assertOk()->json();
        $this->assertFalse($r['completa']);

        $this->assertSame('abandonada', $s->fresh()->estado);
        $d = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail();
        $this->assertFalse($d->completa);
        $this->assertSame(4, (int) $d->respondidas);

        $estado = app(NumerosService::class)->estadoDe($e['estudiante']->id, $e['juego']->id);
        $this->assertSame(0, $estado['completos']);
        $this->assertSame(1, $estado['viaje'], 'Vuelve a tocarle el mismo viaje');
    }

    public function test_mas_de_35_por_ciento_de_toques_al_azar_deja_el_viaje_como_no_confiable()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $this->jugarViaje($s, 1, null, 200);   // todas en 200 ms (el calentamiento no cuenta)
        $this->cerrar($s)->assertOk();

        $d = DetalleNumeros::where('sesion_id', $s->id)->firstOrFail();
        $this->assertTrue($d->completa);
        $this->assertSame(100, (int) $d->pct_azar);
        $this->assertFalse($d->confiable);
    }

    // ── Viajes y ciclos ──────────────────────────────────────────────────

    public function test_los_viajes_se_abren_en_orden_y_tres_viajes_forman_un_ciclo()
    {
        $e = $this->escenarioNumeros();
        $svc = app(NumerosService::class);
        $this->actingAs($e['estudiante'], 'estudiante');

        // El viaje 2 no se puede saltear.
        $this->post(route('estudiante.numeros.iniciar', [$e['juego']->id, $e['niveles'][2]->id]))
            ->assertRedirect(route('estudiante.juegos'));
        $this->assertSame(0, SesionJuego::where('juego_id', $e['juego']->id)->count());

        foreach ([1, 2, 3] as $viaje) {
            $s = $this->iniciar($e, $viaje, true);
            $this->jugarViaje($s, $viaje);
            $this->cerrar($s)->assertOk();
        }

        $estado = $svc->estadoDe($e['estudiante']->id, $e['juego']->id);
        $this->assertSame(3, $estado['completos']);
        $this->assertSame(2, $estado['ciclo']);
        $this->assertSame(1, $estado['viaje'], 'Empieza un nuevo ciclo');
        $this->assertSame(0, $estado['hechos_en_ciclo']);

        $detalles = DetalleNumeros::orderBy('id')->get();
        $this->assertSame([1, 2, 3], $detalles->pluck('viaje')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([1, 1, 1], $detalles->pluck('ciclo')->map(fn ($v) => (int) $v)->all());
    }

    public function test_el_ciclo_siguiente_usa_otras_preguntas_fijas()
    {
        $e = $this->escenarioNumeros();
        foreach ([1, 2, 3] as $viaje) {
            $s = $this->iniciar($e, $viaje, true);
            $this->jugarViaje($s, $viaje);
            $this->cerrar($s)->assertOk();
        }
        $s2 = $this->iniciar($e, 1, true);
        $this->assertSame(2, (int) DetalleNumeros::where('sesion_id', $s2->id)->firstOrFail()->ciclo);

        $viejas = array_column(GeneradorItems::fijas($e['grado'], 5, 1), 'clave');
        $nuevas = array_column(GeneradorItems::fijas($e['grado'], 5, 2), 'clave');
        $this->assertNotSame($viejas, $nuevas);
    }

    public function test_se_sugiere_un_viaje_por_dia_mati_invita_a_seguir_otro_dia()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);
        $this->cerrar($s)->assertOk();

        $this->actingAs($e['estudiante'], 'estudiante');
        $this->post(route('estudiante.numeros.iniciar', [$e['juego']->id, $e['niveles'][2]->id]))
            ->assertRedirect(route('estudiante.numeros.hoy', [$e['juego']->id, $e['niveles'][2]->id]));

        $html = $this->get(route('estudiante.numeros.hoy', [$e['juego']->id, $e['niveles'][2]->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Mañana seguimos', $html);
        $this->assertStringContainsString('Jugar otros juegos', $html);

        // Si el niño insiste, el viaje arranca igual.
        $this->post(route('estudiante.numeros.iniciar', [$e['juego']->id, $e['niveles'][2]->id]), ['igual' => 1])
            ->assertRedirect();
        $this->assertSame(2, (int) DetalleNumeros::orderByDesc('id')->first()->viaje);
    }

    public function test_el_menu_marca_los_viajes_hechos_del_ciclo()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);
        $this->cerrar($s)->assertOk();

        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();
        $this->assertStringContainsString('✅', $html);
        $this->assertStringContainsString('Hoy toca: <b>Las cantidades</b>', $html);
    }

    // ── Aislamiento: no entra en progreso, resúmenes ni diagnóstico ───────

    public function test_las_sesiones_del_tamizaje_no_cuentan_en_los_resumenes_de_rendimiento()
    {
        $e = $this->escenarioNumeros();
        $repo = app(SesionRepository::class);
        $antes = $repo->contarCompletadas($e['estudiante']->id);
        $globalAntes = $repo->contarCompletadasGlobal();

        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);
        $this->cerrar($s)->assertOk();

        $this->assertSame($antes, $repo->contarCompletadas($e['estudiante']->id));
        $this->assertSame($globalAntes, $repo->contarCompletadasGlobal());
        $this->assertSame($antes, $repo->resumenCompletadas($e['estudiante']->id)['sesiones']);
        $this->assertFalse($repo->completadasDe($e['estudiante']->id)->pluck('juego_id')->contains($e['juego']->id));
        $this->assertSame(0, $repo->completadasDe($e['estudiante']->id, $e['juego']->id)->count());
        $this->assertNotSame($e['juego']->id, $repo->ultimaCompletada($e['estudiante']->id)?->juego_id);
    }

    public function test_el_tamizaje_no_aparece_en_los_niveles_y_candados_del_docente()
    {
        $e = $this->escenarioNumeros();

        $nombres = collect(DesbloqueoNiveles::resumenPorJuego($e['estudiante']->id, $e['grado']))
            ->map(fn ($f) => $f['juego']->nombre_es);

        $this->assertFalse($nombres->contains(MatiNumerosSeeder::NOMBRE_ES));
        $this->assertTrue($nombres->isNotEmpty());
    }

    public function test_el_viaje_si_cuenta_para_el_limite_de_tiempo_de_juego()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);

        $filas = app(SesionRepository::class)->sesionesParaTiempo($e['estudiante']->id, now()->subDay());
        $this->assertTrue($filas->pluck('id')->contains($s->id));
    }

    public function test_al_borrar_la_sesion_se_borran_sus_respuestas()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);
        $this->assertGreaterThan(0, RespuestaNumeros::where('sesion_id', $s->id)->count());

        $s->delete();

        $this->assertSame(0, RespuestaNumeros::where('sesion_id', $s->id)->count());
        $this->assertSame(0, DetalleNumeros::where('sesion_id', $s->id)->count());
    }

    public function test_el_comando_exporta_las_respuestas_en_csv_sin_datos_personales()
    {
        $e = $this->escenarioNumeros();
        $s = $this->iniciar($e, 1);
        $this->jugarViaje($s, 1);
        $this->cerrar($s)->assertOk();

        $ruta = tempnam(sys_get_temp_dir(), 'num') . '.csv';
        $this->artisan('numeros:exportar', ['--ruta' => $ruta, '--solo-completos' => true])->assertExitCode(0);

        $lineas = array_values(array_filter(explode("\n", trim((string) file_get_contents($ruta)))));
        $this->assertCount(1 + 26, $lineas, 'Encabezado + una fila por pregunta');
        $csv = implode("\n", $lineas);
        $this->assertStringContainsString($e['estudiante']->id_anonimizado, $csv);
        $this->assertStringNotContainsString((string) $e['estudiante']->codigo_acceso, $csv);
        @unlink($ruta);
    }
}
