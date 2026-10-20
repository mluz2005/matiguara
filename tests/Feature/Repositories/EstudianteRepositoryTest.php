<?php

namespace Tests\Feature\Repositories;

use App\Models\ProgresoEstudiante;
use App\Models\Racha;
use App\Repositories\EstudianteRepository;
use App\Repositories\SesionRepository;

class EstudianteRepositoryTest extends RepositorioTestCase
{
    private function sesion(array $e, int $puntaje, int $aciertos)
    {
        return app(SesionRepository::class)->crear([
            'estudiante_id'    => $e['estudiante']->id,
            'juego_id'         => $e['juego']->id,
            'nivel_id'         => $e['nivel1']->id,
            'fecha_inicio'     => now(),
            'puntaje_obtenido' => $puntaje,
            'aciertos'         => $aciertos,
            'errores'          => 0,
            'estado'           => 'en_curso',
        ]);
    }

    public function test_asegurar_progreso_crea_una_sola_vez_y_no_suma_partidas()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);
        $args = [$e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, $e['nivel1']->grado];

        $p1 = $repo->asegurarProgreso(...$args);
        $p2 = $repo->asegurarProgreso(...$args);

        $this->assertSame($p1->id, $p2->id);
        $this->assertSame(0, (int) $p2->fresh()->total_partidas);
        $this->assertSame(1, ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)
            ->where('nivel_id', $e['nivel1']->id)->count());
    }

    public function test_desbloquear_nivel_crea_o_actualiza_sin_duplicar()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $repo->asegurarProgreso($e['estudiante']->id, $e['juego']->id, $e['nivel2']->id, $e['nivel2']->grado);
        $repo->desbloquearNivel($e['estudiante']->id, $e['juego']->id, $e['nivel2']);
        $repo->desbloquearNivel($e['estudiante']->id, $e['juego']->id, $e['nivel2']);

        $filas = ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)
            ->where('nivel_id', $e['nivel2']->id)->get();
        $this->assertCount(1, $filas);
        $this->assertTrue((bool) $filas->first()->nivel_desbloqueado);
    }

    public function test_registrar_partida_crea_acumula_y_conserva_el_mejor_puntaje()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $repo->registrarPartida($this->sesion($e, 20, 3), $e['nivel1'], false);
        $p = $repo->progreso($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, $e['nivel1']->grado);
        $this->assertSame(1, (int) $p->total_partidas);
        $this->assertSame(3, (int) $p->total_aciertos);
        $this->assertSame(20, (int) $p->mejor_puntaje);
        $this->assertFalse((bool) $p->nivel_desbloqueado);

        $repo->registrarPartida($this->sesion($e, 10, 2), $e['nivel1'], false);
        $p = $p->fresh();
        $this->assertSame(2, (int) $p->total_partidas);
        $this->assertSame(5, (int) $p->total_aciertos);
        $this->assertSame(20, (int) $p->mejor_puntaje, 'Un puntaje menor no reemplaza al mejor.');

        $repo->registrarPartida($this->sesion($e, 45, 5), $e['nivel1'], true);
        $p = $p->fresh();
        $this->assertSame(3, (int) $p->total_partidas);
        $this->assertSame(45, (int) $p->mejor_puntaje);
        $this->assertTrue((bool) $p->nivel_desbloqueado);
    }

    public function test_contar_niveles_desbloqueados()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $antes = $repo->contarNivelesDesbloqueados($e['estudiante']);
        $repo->desbloquearNivel($e['estudiante']->id, $e['juego']->id, $e['nivel1']);
        $repo->desbloquearNivel($e['estudiante']->id, $e['juego']->id, $e['nivel2']);

        $this->assertSame($antes + 2, $repo->contarNivelesDesbloqueados($e['estudiante']->fresh()));
    }

    public function test_rachas_se_crean_en_cero_se_guardan_y_se_consulta_el_maximo_de_dias()
    {
        $e    = $this->escenario();
        $repo = app(EstudianteRepository::class);

        $this->assertSame(0, $repo->rachaActualMaximaEnDias($e['estudiante']));

        $racha = $repo->obtenerOCrearRacha($e['estudiante']->id, $e['juego']->id);
        $this->assertSame(0, (int) $racha->racha_actual_aciertos);
        $this->assertSame($racha->id, $repo->obtenerOCrearRacha($e['estudiante']->id, $e['juego']->id)->id);

        $racha->racha_actual_dias = 4;
        $racha->racha_actual_aciertos = 2;
        $repo->guardarRacha($racha);

        $this->assertSame(4, (int) Racha::find($racha->id)->racha_actual_dias);
        $this->assertSame(4, $repo->rachaActualMaximaEnDias($e['estudiante']->fresh()));
    }

    public function test_matriculas_devuelve_el_historial_ordenado_con_su_institucion()
    {
        $repo = app(EstudianteRepository::class);
        $ana  = \App\Models\Estudiante::where('codigo_acceso', 'SAN-1A-001')->firstOrFail();
        $primera = \App\Models\Matricula::where('estudiante_id', $ana->id)->firstOrFail();
        $primera->update(['activo' => false, 'anio_lectivo' => 2025]);
        $segunda = \App\Models\Matricula::create([
            'estudiante_id' => $ana->id, 'institucion_id' => $primera->institucion_id, 'docente_id' => $primera->docente_id,
            'grado' => 2, 'anio_lectivo' => 2026, 'activo' => true, 'fecha_inicio' => '2026-03-01',
        ]);

        $m = $repo->matriculas($ana->id);

        $this->assertSame([$primera->id, $segunda->id], $m->pluck('id')->all());
        $this->assertTrue($m->first()->relationLoaded('institucion'));
        $this->assertSame([], $repo->matriculas(999999)->all());
    }
}
