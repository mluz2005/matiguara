<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Models\Matricula;
use App\Models\SesionJuego;
use App\Models\Usuario;
use App\Services\TiempoJuegoService;

/**
 * Límite de tiempo de juego por sesión y pausa entre sesiones (RN-L10, RN-L11).
 * Vale para TODOS los juegos: se prueba con Suma de Frutas y con Encuentra el par.
 */
class TiempoJuegoTest extends RepositorioTestCase
{
    private function docenteDe($estudiante): Docente
    {
        return Docente::findOrFail(
            Matricula::where('estudiante_id', $estudiante->id)->where('activo', true)->value('docente_id')
        );
    }

    /** Crea una partida terminada que empezó hace $haceSeg y duró $durSeg. */
    private function partidaTerminada(array $e, int $haceSeg, int $durSeg, array $extra = []): SesionJuego
    {
        $inicio = now()->subSeconds($haceSeg);

        return $this->nuevaSesion($e, array_merge([
            'fecha_inicio' => $inicio,
            'fecha_fin'    => (clone $inicio)->addSeconds($durSeg),
            'estado'       => 'completada',
        ], $extra));
    }

    // ── Cálculo puro (sin base de datos) ─────────────────────────────────

    public function test_calculo_suma_el_tiempo_activo_y_bloquea_al_llegar_al_limite()
    {
        $ahora = 100000;

        $r = TiempoJuegoService::calcular([['inicio' => $ahora - 240, 'fin' => $ahora - 60]], $ahora, 480, 1800);
        $this->assertSame(180, $r['jugado_seg']);
        $this->assertSame(300, $r['restante_seg']);
        $this->assertFalse($r['bloqueado']);

        $r = TiempoJuegoService::calcular([
            ['inicio' => $ahora - 900, 'fin' => $ahora - 600],
            ['inicio' => $ahora - 500, 'fin' => $ahora - 60],
        ], $ahora, 480, 1800);
        $this->assertTrue($r['bloqueado']);
        $this->assertSame(1800 - 60, $r['vuelve_en_seg']);
    }

    public function test_calculo_la_pausa_cumplida_reinicia_el_ciclo_y_sin_limite_no_bloquea()
    {
        $ahora = 100000;

        $r = TiempoJuegoService::calcular([['inicio' => $ahora - 4000, 'fin' => $ahora - 3000]], $ahora, 480, 1800);
        $this->assertFalse($r['bloqueado']);
        $this->assertSame(0, $r['jugado_seg']);

        $r = TiempoJuegoService::calcular([['inicio' => $ahora - 5000, 'fin' => $ahora - 10]], $ahora, 0, 1800);
        $this->assertFalse($r['bloqueado'], 'Límite 0 = sin límite.');
    }

    public function test_calculo_una_partida_huerfana_no_bloquea_para_siempre()
    {
        $ahora = 100000;
        $r = TiempoJuegoService::calcular([['inicio' => $ahora - 5000, 'fin' => null]], $ahora, 480, 1800);
        $this->assertFalse($r['bloqueado']);
    }

    // ── Valores por defecto y panel de la docente ────────────────────────

    public function test_valores_por_defecto_8_minutos_de_juego_y_30_de_pausa()
    {
        $e   = $this->escenario();
        $cfg = app(TiempoJuegoService::class)->configuracion($e['estudiante']);

        $this->assertSame(8 * 60, $cfg['limite_seg']);
        $this->assertSame(30 * 60, $cfg['pausa_seg']);
    }

    public function test_la_docente_cambia_el_limite_y_la_pausa_desde_su_panel()
    {
        $e = $this->escenario();
        $docente = $this->docenteDe($e['estudiante']);
        $this->actingAs(Usuario::findOrFail($docente->usuario_id));

        $this->post(route('docente.juegos.tiempo'), [
            'limite_sesion_min' => 5, 'pausa_entre_sesiones_min' => 20,
        ])->assertSessionHas('mensaje');

        $docente->refresh();
        $this->assertSame(5, $docente->limite_sesion_min);
        $this->assertSame(20, $docente->pausa_entre_sesiones_min);

        $cfg = app(TiempoJuegoService::class)->configuracion($e['estudiante']->fresh());
        $this->assertSame(300, $cfg['limite_seg']);
        $this->assertSame(1200, $cfg['pausa_seg']);

        $this->get(route('docente.juegos'))->assertOk()->assertSee('Tiempo de juego');
    }

    public function test_el_panel_rechaza_valores_fuera_de_rango()
    {
        $e = $this->escenario();
        $docente = $this->docenteDe($e['estudiante']);
        $this->actingAs(Usuario::findOrFail($docente->usuario_id));

        $this->post(route('docente.juegos.tiempo'), ['limite_sesion_min' => 120, 'pausa_entre_sesiones_min' => 30])
            ->assertSessionHasErrors('limite_sesion_min');
        $this->post(route('docente.juegos.tiempo'), ['limite_sesion_min' => 8, 'pausa_entre_sesiones_min' => -1])
            ->assertSessionHasErrors('pausa_entre_sesiones_min');

        $this->assertSame(8, $docente->fresh()->limite_sesion_min, 'No se modificó nada.');
    }

    // ── Bloqueo en los juegos ────────────────────────────────────────────

    public function test_al_llegar_al_limite_suma_de_frutas_manda_al_descanso_y_cierra_la_partida()
    {
        $e = $this->escenario();
        $this->actingAs($e['estudiante'], 'estudiante');

        // 9 minutos de juego terminados hace 1 minuto + una partida abierta.
        $this->partidaTerminada($e, 600, 540);
        $abierta = $this->nuevaSesion($e, ['fecha_inicio' => now()->subSeconds(30)]);

        $this->get(route('estudiante.sesion.ejercicio', $abierta->id))
            ->assertRedirect(route('estudiante.descanso'));

        $this->assertSame('abandonada', $abierta->fresh()->estado, 'La partida abierta queda guardada como abandonada.');

        $antes = SesionJuego::count();
        $this->post(route('estudiante.sesion.iniciar', [$e['juego']->id, $e['nivel1']->id]))
            ->assertRedirect(route('estudiante.descanso'));
        $this->assertSame($antes, SesionJuego::count(), 'No se crea ninguna partida nueva durante el descanso.');
    }

    public function test_encuentra_el_par_tambien_respeta_el_limite()
    {
        $e = $this->escenarioMemoria();
        $this->actingAs($e['estudiante'], 'estudiante');
        $this->partidaTerminada($e, 600, 540);

        $antes = SesionJuego::count();
        $this->post(route('estudiante.memoria.iniciar', [$e['juego']->id, $e['nivel1']->id]))
            ->assertRedirect(route('estudiante.descanso'));
        $this->assertSame($antes, SesionJuego::count());
    }

    public function test_la_pantalla_de_descanso_y_la_vuelta_al_juego_al_cumplir_la_pausa()
    {
        $e = $this->escenario();
        $this->actingAs($e['estudiante'], 'estudiante');

        // Jugó 9 min y terminó hace 5 min: en descanso.
        $this->partidaTerminada($e, 840, 540);
        $this->get(route('estudiante.descanso'))->assertOk()->assertSee('Hora de descansar');

        // Pausa de 0 minutos = sin pausa: ya no está bloqueado.
        $this->docenteDe($e['estudiante'])->update(['pausa_entre_sesiones_min' => 0]);

        // En el uso real cada petición vuelve a leer al alumno y a su docente; aquí el objeto
        // autenticado conservaría la relación ya cargada (con la pausa vieja), así que se renueva.
        $this->actingAs(\App\Models\Estudiante::findOrFail($e['estudiante']->id), 'estudiante');
        $this->get(route('estudiante.descanso'))->assertRedirect(route('estudiante.juegos'));
    }

    public function test_si_no_llego_al_limite_juega_normal_y_ve_la_barra_de_tiempo()
    {
        $e = $this->escenario();
        $this->actingAs($e['estudiante'], 'estudiante');

        $this->post(route('estudiante.sesion.iniciar', [$e['juego']->id, $e['nivel1']->id]))
            ->assertRedirect();
        $sesion = SesionJuego::where('estado', 'en_curso')->firstOrFail();

        $this->get(route('estudiante.sesion.ejercicio', $sesion->id))
            ->assertOk()
            ->assertSee('barraTiempo', false);
    }

    public function test_responder_el_ejercicio_en_pantalla_nunca_se_corta()
    {
        $e = $this->escenario();
        $this->actingAs($e['estudiante'], 'estudiante');

        $sesion = $this->nuevaSesion($e, ['fecha_inicio' => now()->subSeconds(30)]);
        $ejercicio = $e['ejercicios']->first();

        // Llegó al límite mientras miraba el ejercicio: la respuesta igual se registra.
        $this->partidaTerminada($e, 700, 600);
        $this->post(route('estudiante.sesion.responder', $sesion->id), [
            'contenido_id' => $ejercicio->id,
            'respuesta'    => (string) $ejercicio->respuesta_correcta,
            'tiempo_ms'    => 3000,
        ]);

        $this->assertSame(1, $sesion->movimientos()->count());
    }
}
