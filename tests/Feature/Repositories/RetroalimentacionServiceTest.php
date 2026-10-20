<?php

namespace Tests\Feature\Repositories;

use App\Models\MensajeRetro;
use App\Repositories\EstudianteRepository;
use App\Services\RetroalimentacionService;

class RetroalimentacionServiceTest extends RepositorioTestCase
{
    public function test_obtener_mensaje_respeta_el_contexto_pedido()
    {
        $srv = app(RetroalimentacionService::class);

        $primer = $srv->obtenerMensaje('acierto', true);
        $this->assertSame('acierto', $primer->tipo);
        $this->assertSame('primer_intento', $primer->contexto);

        $reintento = $srv->obtenerMensaje('error', false);
        $this->assertSame('error', $reintento->tipo);
        $this->assertSame('reintento', $reintento->contexto);

        $racha = $srv->obtenerMensaje('racha', true, true);
        $this->assertSame('maximo_racha', $racha->contexto);
    }

    public function test_si_no_hay_mensaje_del_contexto_cae_a_general()
    {
        $srv = app(RetroalimentacionService::class);
        MensajeRetro::where('tipo', 'acierto')->where('contexto', 'primer_intento')->update(['activo' => false]);

        $m = $srv->obtenerMensaje('acierto', true);

        $this->assertNotNull($m);
        $this->assertSame('general', $m->contexto);
    }

    public function test_si_la_exclusion_deja_sin_opciones_se_repite_el_unico_disponible()
    {
        $srv = app(RetroalimentacionService::class);
        MensajeRetro::where('tipo', 'nivel_superado')->update(['activo' => false]);
        $unico = MensajeRetro::where('tipo', 'nivel_superado')->where('contexto', 'general')->first();
        $unico->update(['activo' => true]);

        $m = $srv->obtenerMensaje('nivel_superado', true, false, $unico->id);

        $this->assertSame($unico->id, $m->id);
    }

    public function test_obtener_y_registrar_guarda_el_ultimo_mensaje_y_no_lo_repite()
    {
        $e   = $this->escenario();
        $srv = app(RetroalimentacionService::class);

        $progreso = $srv->obtenerProgreso($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, $e['nivel1']->grado);
        $this->assertNull($progreso->ultimo_mensaje_id);

        $m1 = $srv->obtenerYRegistrar('error', $progreso, true);
        $this->assertSame($m1->id, $progreso->fresh()->ultimo_mensaje_id);

        // Siguiente mensaje: nunca el mismo que el último mostrado (si hay alternativa)
        $hayAlternativa = MensajeRetro::where('tipo', 'error')->where('contexto', 'primer_intento')
            ->where('activo', true)->where('id', '!=', $m1->id)->exists();
        if ($hayAlternativa) {
            for ($i = 0; $i < 8; $i++) {
                $prog = $progreso->fresh();
                $m2   = $srv->obtenerYRegistrar('error', $prog, true);
                $this->assertNotSame($prog->ultimo_mensaje_id, $m2->id);
            }
        }
    }

    public function test_obtener_progreso_crea_en_cero_y_es_idempotente()
    {
        $e   = $this->escenario();
        $srv = app(RetroalimentacionService::class);

        $p1 = $srv->obtenerProgreso($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, $e['nivel1']->grado);
        $p2 = $srv->obtenerProgreso($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, $e['nivel1']->grado);

        $this->assertSame($p1->id, $p2->id);
        $this->assertSame(0, (int) $p1->total_partidas);
        $this->assertFalse((bool) $p1->nivel_desbloqueado);
    }
}
