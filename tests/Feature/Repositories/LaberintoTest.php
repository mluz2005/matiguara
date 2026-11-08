<?php

namespace Tests\Feature\Repositories;

use App\Models\ContenidoNivel;
use App\Models\DetalleLaberinto;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\MovimientoSesion;
use App\Models\Nivel;
use App\Models\SesionJuego;
use App\Services\LaberintoService;
use App\Support\DesbloqueoNiveles;
use Database\Seeders\CaminoEscuelaSeeder;

/**
 * «El camino a la escuela» — juego de laberinto integrado a MatiGuará.
 * Cubre: banco de ejercicios, flujo iniciar → tablero → respuestas → cerrar,
 * puntos 10/5, errores por apuro, estrellas, superado, medallas, regla 3/2
 * por partida y el límite de tiempo de juego (RN-L10).
 */
class LaberintoTest extends RepositorioTestCase
{
    /** @return array{estudiante:Estudiante,juego:Juego,nivel1:Nivel,nivel2:Nivel,grado:int} */
    private function escenarioLaberinto(): array
    {
        $this->seed(CaminoEscuelaSeeder::class);

        $estudiante = Estudiante::first();
        $grado      = (int) $estudiante->matriculaActiva->grado;
        $juego      = Juego::where('nombre_es', 'El camino a la escuela')->firstOrFail();
        $nivel1     = Nivel::where('juego_id', $juego->id)->where('grado', $grado)->where('numero_nivel', 1)->firstOrFail();
        $nivel2     = Nivel::where('juego_id', $juego->id)->where('grado', $grado)->where('numero_nivel', 2)->firstOrFail();

        return compact('estudiante', 'juego', 'nivel1', 'nivel2', 'grado');
    }

    /** Inicia una partida por HTTP (como el niño) y devuelve la sesión creada. */
    private function iniciar(array $e, ?Nivel $nivel = null): SesionJuego
    {
        $nivel ??= $e['nivel1'];
        $this->actingAs($e['estudiante'], 'estudiante');

        $this->post(route('estudiante.laberinto.iniciar', [$e['juego']->id, $nivel->id]))->assertRedirect();

        return SesionJuego::where('estudiante_id', $e['estudiante']->id)
            ->where('estado', 'en_curso')->orderByDesc('id')->firstOrFail();
    }

    /** Envía una respuesta como lo hace el navegador. */
    private function responder(SesionJuego $s, array $ejercicio, bool $correcta, int $ms = 3000, ?int $intento = null)
    {
        $valor = $correcta ? $ejercicio['respuesta_correcta'] : $ejercicio['respuesta_correcta'] + 1;

        return $this->postJson(route('estudiante.laberinto.movimiento', $s->id), array_filter([
            'contenido_id' => $ejercicio['id'],
            'respuesta'    => (string) $valor,
            'tiempo_ms'    => $ms,
            'intento'      => $intento,
        ], fn ($v) => $v !== null));
    }

    /** Ejercicios que le tocaron a la partida: [obstáculo => [ronda 0, ronda 1]]. */
    private function ejerciciosDe(SesionJuego $s): array
    {
        return array_map(fn ($o) => $o['ejercicios'], app(LaberintoService::class)->obstaculosDe($s));
    }

    /** Juega la partida entera: $conSolucion = cantidad de obstáculos en los que se agotan los 2 intentos. */
    private function jugar(SesionJuego $s, int $conSolucion = 0): void
    {
        foreach ($this->ejerciciosDe($s) as $i => $rondas) {
            $ej = $rondas[0];
            if ($i < $conSolucion) {
                $this->responder($s, $ej, false, 3000, 1)->assertOk();
                $this->responder($s, $ej, false, 3000, 2)->assertOk();
                $ej = $rondas[1];   // el "uno parecido" se resuelve bien
            }
            $this->responder($s, $ej, true, 3000, 1)->assertOk();
        }
    }

    private function cerrar(SesionJuego $s, array $datos = [])
    {
        return $this->postJson(route('estudiante.laberinto.cerrar', $s->id), array_merge([
            'pistas' => 0, 'flecha' => false, 'chipas_recogidas' => 0, 'chipas_total' => 3, 'duracion_s' => 60,
        ], $datos));
    }

    // ── Banco de ejercicios ──────────────────────────────────────────────

    public function test_el_banco_tiene_ejercicios_completos_en_los_24_niveles_y_todos_son_correctos()
    {
        $e = $this->escenarioLaberinto();
        $niveles = Nivel::where('juego_id', $e['juego']->id)->get();
        $this->assertCount(24, $niveles);

        foreach ($niveles as $nivel) {
            $n    = (int) $nivel->total_ejercicios;
            $filas = ContenidoNivel::where('nivel_id', $nivel->id)->orderBy('orden')->get();
            $this->assertGreaterThan(0, $filas->count(), "Nivel {$nivel->id} sin ejercicios");
            $this->assertSame(0, $filas->count() % (2 * $n), "Nivel {$nivel->id}: debe haber 2 ejercicios por obstáculo y variante");
            $this->assertSame($filas->count(), $filas->pluck('orden')->unique()->count());

            foreach ($filas as $f) {
                $a = (int) $f->valor_a; $b = (int) $f->valor_b; $c = (int) $f->valor_c;
                $esperado = match ($f->tipo_operacion) {
                    'suma'            => $a + $b,
                    'resta'           => $a - $b,
                    'cuanto_falta'    => $b - $a,
                    'dos_pasos'       => $f->operador === '+' ? $a + $b - $c : $a - $b + $c,
                    'dos_pasos_falta' => $c - $a - $b,
                };
                $this->assertSame($esperado, (int) $f->respuesta_correcta, "Ejercicio {$f->id} ({$f->tipo_operacion})");
                $this->assertContains((int) $f->respuesta_correcta, array_map('intval', $f->opciones_json));
                $this->assertSame(count($f->opciones_json), count(array_unique($f->opciones_json)), 'Opciones repetidas');
                $this->assertGreaterThanOrEqual(0, $esperado);
            }
        }
    }

    public function test_el_seeder_se_puede_correr_dos_veces_sin_duplicar_nada()
    {
        $this->seed(CaminoEscuelaSeeder::class);
        $niveles    = Nivel::count();
        $ejercicios = ContenidoNivel::count();
        $juegos     = Juego::count();

        $this->seed(CaminoEscuelaSeeder::class);

        $this->assertSame($niveles, Nivel::count());
        $this->assertSame($ejercicios, ContenidoNivel::count());
        $this->assertSame($juegos, Juego::count());
    }

    // ── Inicio y tablero ─────────────────────────────────────────────────

    public function test_iniciar_crea_la_partida_y_el_tablero_trae_semilla_y_ejercicios_de_cada_obstaculo()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);

        $this->assertNotNull(DetalleLaberinto::where('sesion_id', $s->id)->first());

        $r = $this->get(route('estudiante.laberinto.tablero', $s->id))->assertOk();
        $cfg = $r->viewData('config');

        $this->assertSame($s->id, $cfg['sesionId']);
        $this->assertSame((int) $e['nivel1']->total_ejercicios, count($cfg['obstaculos']));
        $this->assertGreaterThan(0, $cfg['semilla']);
        foreach ($cfg['obstaculos'] as $o) {
            $this->assertCount(2, $o['ejercicios'], 'Cada obstáculo tiene el ejercicio y uno parecido de repuesto.');
        }
        $r->assertSee('barraTiempo', false);   // barra de tiempo de juego del layout
    }

    public function test_no_se_puede_jugar_un_nivel_de_otro_grado()
    {
        $e = $this->escenarioLaberinto();
        $otro = Nivel::where('juego_id', $e['juego']->id)->where('grado', '!=', $e['grado'])->firstOrFail();
        $this->actingAs($e['estudiante'], 'estudiante');

        $antes = SesionJuego::count();
        $this->post(route('estudiante.laberinto.iniciar', [$e['juego']->id, $otro->id]))
            ->assertRedirect(route('estudiante.juegos'));
        $this->assertSame($antes, SesionJuego::count());
    }

    public function test_un_nivel_cerrado_no_se_puede_iniciar()
    {
        $e = $this->escenarioLaberinto();
        $this->actingAs($e['estudiante'], 'estudiante');
        $cerrado = Nivel::where('juego_id', $e['juego']->id)->where('grado', $e['grado'])->where('numero_nivel', 5)->firstOrFail();

        $antes = SesionJuego::count();
        $this->post(route('estudiante.laberinto.iniciar', [$e['juego']->id, $cerrado->id]))
            ->assertRedirect(route('estudiante.juegos'));
        $this->assertSame($antes, SesionJuego::count());
    }

    public function test_el_menu_de_juegos_muestra_el_camino_a_la_escuela()
    {
        $e = $this->escenarioLaberinto();
        $this->actingAs($e['estudiante'], 'estudiante');

        $this->get(route('estudiante.juegos'))->assertOk()->assertSee('El camino a la escuela');
    }

    // ── Respuestas, puntos y errores por apuro ───────────────────────────

    public function test_acierto_al_primer_intento_da_10_puntos_y_con_reintento_5()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        [$o1, $o2] = array_slice($this->ejerciciosDe($s), 0, 2);

        $r = $this->responder($s, $o1[0], true, 3000, 1)->assertOk();
        $this->assertTrue($r->json('es_correcto'));
        $this->assertSame(10, $r->json('puntos'));

        $this->assertFalse($this->responder($s, $o2[0], false, 3000, 1)->json('es_correcto'));
        $r = $this->responder($s, $o2[0], true, 3000, 2);
        $this->assertSame(5, $r->json('puntos'));
        $this->assertSame(15, $r->json('puntaje_total'));
    }

    public function test_una_respuesta_demasiado_rapida_no_cuenta_como_intento_ni_castiga()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $ej = $this->ejerciciosDe($s)[0][0];

        $r = $this->responder($s, $ej, false, 300);   // apuro: menos de 1 s
        $this->assertTrue($r->json('atencion'));
        $this->assertSame(0, $r->json('puntos'));

        // se guarda el registro del error de atención…
        $this->assertSame(1, MovimientoSesion::where('sesion_id', $s->id)->where('tipo_error', 'atencion')->count());
        // …pero el acierto siguiente sigue siendo "primer intento": 10 puntos.
        $r = $this->responder($s, $ej, true, 4000, 1);
        $this->assertSame(10, $r->json('puntos'));
        $this->assertSame(1, $r->json('numero_intento'));
    }

    public function test_un_envio_repetido_no_suma_dos_veces()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $ej = $this->ejerciciosDe($s)[0][0];

        $this->responder($s, $ej, true, 3000, 1)->assertOk();
        $r = $this->responder($s, $ej, true, 3000, 1)->assertOk();   // reintento de red del navegador

        $this->assertTrue($r->json('duplicado'));
        $this->assertSame(1, MovimientoSesion::where('sesion_id', $s->id)->where('contenido_id', $ej['id'])->count());
        $this->assertSame(10, (int) $s->fresh()->puntaje_obtenido);
    }

    public function test_no_se_acepta_un_ejercicio_de_otro_nivel_ni_una_sesion_ajena()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $ajeno = ContenidoNivel::where('nivel_id', $e['nivel2']->id)->first();

        $this->postJson(route('estudiante.laberinto.movimiento', $s->id), [
            'contenido_id' => $ajeno->id, 'respuesta' => '1', 'tiempo_ms' => 3000,
        ])->assertStatus(422);

        $otro = Estudiante::where('id', '!=', $e['estudiante']->id)->first();
        if ($otro) {
            $this->actingAs($otro, 'estudiante');
            $this->postJson(route('estudiante.laberinto.movimiento', $s->id), [
                'contenido_id' => $ajeno->id, 'respuesta' => '1', 'tiempo_ms' => 3000,
            ])->assertStatus(403);
        }
    }

    // ── Cierre: estrellas, superado, medallas ────────────────────────────

    public function test_camino_perfecto_3_estrellas_superado_y_medallas()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s);

        $r = $this->cerrar($s, ['chipas_recogidas' => 3, 'chipas_total' => 3])->assertOk();

        $this->assertSame(3, $r->json('estrellas'));
        $this->assertTrue($r->json('superado'));
        $this->assertSame(route('estudiante.sesion.resumen', $s->id), $r->json('redirect_url'));

        $s->refresh();
        $this->assertSame('completada', $s->estado);
        $this->assertTrue((bool) $s->nivel_superado);

        $d = DetalleLaberinto::where('sesion_id', $s->id)->firstOrFail();
        $this->assertTrue($d->llego);
        $this->assertSame(3, $d->estrellas);
        $this->assertSame(0, $d->soluciones_mostradas);

        $nombres = array_column($r->json('medallas'), 'nombre');
        $this->assertContains('Primer camino a la escuela', $nombres);
        $this->assertContains('Camino perfecto', $nombres);
        $this->assertContains('Chipero', $nombres);
    }

    public function test_con_pistas_o_flecha_son_2_estrellas()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s);

        $this->assertSame(2, $this->cerrar($s, ['pistas' => 1])->json('estrellas'));

        $s2 = $this->iniciar($e);
        $this->jugar($s2);
        $this->assertSame(2, $this->cerrar($s2, ['flecha' => true])->json('estrellas'));
    }

    public function test_una_solucion_mostrada_baja_a_1_estrella_pero_el_camino_se_supera()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s, 1);

        $r = $this->cerrar($s)->assertOk();

        $this->assertSame(1, $r->json('estrellas'));
        $this->assertTrue($r->json('superado'), 'Con una sola solución mostrada el camino todavía se supera.');
        $this->assertSame(1, DetalleLaberinto::where('sesion_id', $s->id)->value('soluciones_mostradas'));
    }

    public function test_dos_soluciones_mostradas_no_superan_el_camino()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s, 2);

        $r = $this->cerrar($s)->assertOk();

        $this->assertFalse($r->json('superado'));
        $this->assertSame(1, $r->json('estrellas'));
        $this->assertFalse((bool) $s->fresh()->nivel_superado);
    }

    public function test_cerrar_dos_veces_no_duplica_nada()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s);

        $this->cerrar($s)->assertOk();
        $r = $this->cerrar($s, ['pistas' => 5, 'flecha' => true])->assertOk();

        $this->assertSame(3, $r->json('estrellas'), 'La segunda llamada no cambia el resultado ya guardado.');
        $this->assertSame(1, DetalleLaberinto::where('sesion_id', $s->id)->count());
    }

    public function test_el_resumen_muestra_las_ayudas_y_las_chipas_del_camino()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);
        $this->jugar($s);
        $this->cerrar($s, ['chipas_recogidas' => 2, 'chipas_total' => 3, 'pistas' => 1]);

        $this->get(route('estudiante.sesion.resumen', $s->id))
            ->assertOk()
            ->assertSee('2/3 chipas')
            ->assertSee('1 pista');
    }

    // ── Regla 3/2 por partida ────────────────────────────────────────────

    public function test_tres_caminos_superados_seguidos_suben_de_nivel_y_abren_el_siguiente()
    {
        $e = $this->escenarioLaberinto();

        for ($i = 1; $i <= 3; $i++) {
            $s = $this->iniciar($e);
            $this->jugar($s);
            $r = $this->cerrar($s)->assertOk();

            if ($i < 3) {
                $this->assertNull($r->json('cambio_nivel'));
            }
        }

        $this->assertSame('sube', $r->json('cambio_nivel'));
        $this->assertSame(2, $r->json('nivel_destino'));
        $this->assertTrue(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $e['nivel2']));
    }

    public function test_dos_caminos_no_superados_seguidos_bajan_de_nivel()
    {
        $e = $this->escenarioLaberinto();
        DesbloqueoNiveles::abrir($e['estudiante']->id, $e['nivel2']);

        for ($i = 1; $i <= 2; $i++) {
            $s = $this->iniciar($e, $e['nivel2']);
            $this->jugar($s, 2);
            $r = $this->cerrar($s)->assertOk();
        }

        $this->assertSame('baja', $r->json('cambio_nivel'));
        $this->assertSame(1, $r->json('nivel_destino'));
    }

    // ── Límite de tiempo de juego ────────────────────────────────────────

    public function test_el_limite_de_tiempo_tambien_vale_para_el_laberinto()
    {
        $e = $this->escenarioLaberinto();
        $this->actingAs($e['estudiante'], 'estudiante');

        // 9 minutos de juego terminados hace 1 minuto: toca descansar.
        $inicio = now()->subSeconds(600);
        $this->nuevaSesion(['estudiante' => $e['estudiante'], 'juego' => $e['juego'], 'nivel1' => $e['nivel1']], [
            'fecha_inicio' => $inicio,
            'fecha_fin'    => (clone $inicio)->addSeconds(540),
            'estado'       => 'completada',
        ]);

        $antes = SesionJuego::count();
        $this->post(route('estudiante.laberinto.iniciar', [$e['juego']->id, $e['nivel1']->id]))
            ->assertRedirect(route('estudiante.descanso'));
        $this->assertSame($antes, SesionJuego::count(), 'No se crea partida durante el descanso.');
    }

    public function test_al_llegar_la_hora_de_descansar_el_tablero_manda_al_descanso_y_guarda_la_partida()
    {
        $e = $this->escenarioLaberinto();
        $s = $this->iniciar($e);

        // La partida lleva 9 minutos abierta (límite 8 min).
        $s->update(['fecha_inicio' => now()->subSeconds(540)]);

        $this->get(route('estudiante.laberinto.tablero', $s->id))
            ->assertRedirect(route('estudiante.descanso'));
        $this->assertSame('abandonada', $s->fresh()->estado);
    }
}
