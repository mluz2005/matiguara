<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Usuario;
use App\Repositories\SesionRepository;
use App\Services\GraficosService;

/**
 * Gráficos del panel docente: datos que prepara GraficosService y pantallas que los muestran
 * (panel principal, progreso del alumno y solicitud de diagnóstico IA).
 */
class GraficosTest extends RepositorioTestCase
{
    private Usuario $usuario;
    private Docente $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente = Docente::where('usuario_id', $this->usuario->id)->firstOrFail();

        // Las matrículas sembradas son de 2026; el panel filtra por el año en curso.
        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        \App\Models\AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);

        $this->actingAs($this->usuario);
    }

    private function alumnos()
    {
        return Estudiante::whereIn('id', Matricula::where('docente_id', $this->docente->id)->pluck('estudiante_id'))
            ->orderBy('id')->get();
    }

    private function alumnoAjeno(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', '!=', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    /** Sesión completada con ese resultado, iniciada hace $dias días. */
    private function sesion(Estudiante $est, int $aciertos, int $errores, int $dias = 0)
    {
        $e = $this->escenario();
        return $this->nuevaSesion(['estudiante' => $est] + $e, [
            'estado'       => 'completada',
            'aciertos'     => $aciertos,
            'errores'      => $errores,
            'fecha_inicio' => now()->subDays($dias),
        ]);
    }

    private function movimientoConError($sesion, string $tipo)
    {
        $e = $this->escenario();
        $ej = $e['ejercicios']->first();
        app(SesionRepository::class)->registrarMovimiento([
            'sesion_id'           => $sesion->id,
            'contenido_id'        => $ej->id,
            'numero_intento'      => 1,
            'respuesta_dada'      => '99',
            'es_correcto'         => false,
            'tiempo_respuesta_ms' => 2000,
            'tipo_error'          => $tipo,
            'timestamp_mov'       => now(),
        ]);
    }

    // ── Un alumno ────────────────────────────────────────────────────────

    public function test_del_alumno_devuelve_la_evolucion_en_orden_cronologico_y_los_errores_por_tipo()
    {
        $a = $this->alumnos()->first();
        $s1 = $this->sesion($a, 2, 2, 3);   // 50 %
        $this->sesion($a, 3, 1, 2);         // 75 %
        $this->sesion($a, 4, 0, 1);         // 100 %
        $this->movimientoConError($s1, 'calculo');
        $this->movimientoConError($s1, 'calculo');
        $this->movimientoConError($s1, 'atencion');

        $g = app(GraficosService::class)->delAlumno($a->id);

        $this->assertSame(3, $g['sesiones']);
        $this->assertSame([50, 75, 100], array_column($g['puntos'], 'valor'));
        $this->assertSame(2, $g['errores']['calculo']['cantidad']);
        $this->assertSame(1, $g['errores']['atencion']['cantidad']);
        $this->assertSame(0, $g['errores']['lectura']['cantidad']);
        $this->assertSame(3, $g['total_errores']);
    }

    public function test_del_alumno_sin_sesiones_no_falla()
    {
        $g = app(GraficosService::class)->delAlumno($this->alumnos()->first()->id);

        $this->assertSame(0, $g['sesiones']);
        $this->assertSame([], $g['puntos']);
        $this->assertSame(0, $g['total_errores']);
    }

    public function test_solo_se_grafican_las_ultimas_sesiones()
    {
        $a = $this->alumnos()->first();
        for ($i = 0; $i < GraficosService::SESIONES_ALUMNO + 3; $i++) {
            $this->sesion($a, 3, 1, 40 - $i);
        }

        $g = app(GraficosService::class)->delAlumno($a->id);

        $this->assertSame(GraficosService::SESIONES_ALUMNO, $g['sesiones']);
    }

    public function test_pantalla_de_progreso_muestra_el_grafico_del_alumno()
    {
        $a = $this->alumnos()->first();
        $this->sesion($a, 2, 2, 2);
        $this->sesion($a, 4, 0, 1);

        $r = $this->get(route('docente.estudiante.progreso', $a->id));

        $r->assertOk();
        $r->assertSee('Cómo viene jugando');
        $r->assertSee('<svg', false);
        $r->assertSee('Errores por tipo');
        $this->assertSame(2, $r->viewData('grafico')['sesiones']);
    }

    public function test_pantalla_de_diagnostico_muestra_el_grafico_del_alumno()
    {
        $a = $this->alumnos()->first();
        $this->sesion($a, 3, 1, 1);

        $r = $this->get(route('docente.estudiante.diagnostico', $a->id));

        $r->assertOk();
        $r->assertSee('Cómo viene jugando');
        $this->assertSame(1, $r->viewData('grafico')['sesiones']);
    }

    public function test_el_grafico_de_un_alumno_ajeno_no_se_muestra()
    {
        $this->get(route('docente.estudiante.progreso', $this->alumnoAjeno()->id))
            ->assertRedirect(route('docente.estudiantes'));
        $this->get(route('docente.estudiante.diagnostico', $this->alumnoAjeno()->id))
            ->assertRedirect(route('docente.estudiantes'));
    }

    public function test_historial_y_detalle_de_diagnosticos_de_un_alumno_ajeno_estan_protegidos()
    {
        $ajeno = $this->alumnoAjeno();

        $this->get(route('docente.estudiante.diagnosticos', $ajeno->id))
            ->assertRedirect(route('docente.estudiantes'));
        $this->get(route('docente.estudiante.ver-diagnostico', [$ajeno->id, 1]))
            ->assertRedirect(route('docente.estudiantes'));
    }

    public function test_historial_de_diagnosticos_de_un_alumno_propio_muestra_el_grafico()
    {
        $a = $this->alumnos()->first();
        $this->sesion($a, 3, 1, 1);

        $r = $this->get(route('docente.estudiante.diagnosticos', $a->id));

        $r->assertOk();
        $r->assertSee('Cómo viene jugando');
    }

    // ── El grupo ─────────────────────────────────────────────────────────

    public function test_del_grupo_calcula_aciertos_por_alumno_y_actividad_de_hoy()
    {
        [$a, $b] = [$this->alumnos()[0], $this->alumnos()[1]];
        $this->sesion($a, 3, 1, 0);   // 75 %
        $this->sesion($a, 1, 3, 0);   // 25 %  → alumno A: 4 de 8 = 50 %

        $g = app(GraficosService::class)->delGrupo($this->docente->id);

        $this->assertSame(3, $g['total']);
        $fila = collect($g['aciertos'])->firstWhere('sesiones', 2);
        $this->assertSame(50, $fila['valor']);
        $this->assertCount(GraficosService::DIAS_ACTIVIDAD, $g['actividad']);
        $hoy = collect($g['actividad'])->last();
        $this->assertSame(now()->format('Y-m-d'), $hoy['dia']);
        $this->assertSame(1, $hoy['cantidad']);
        // Los demás todavía no empezaron
        $this->assertCount(2, $g['apoyo']['sin_empezar']);
        $this->assertNull(collect($g['aciertos'])->firstWhere('sesiones', 0)['valor']);
    }

    public function test_apoyo_por_pocos_aciertos_solo_con_sesiones_suficientes()
    {
        [$a, $b] = [$this->alumnos()[0], $this->alumnos()[1]];
        // A: 3 sesiones recientes con 25 % → aviso
        foreach ([2, 1, 0] as $d) { $this->sesion($a, 1, 3, $d); }
        // B: solo 2 sesiones con 0 % → todavía no alcanza para opinar
        foreach ([1, 0] as $d) { $this->sesion($b, 0, 4, $d); }

        $g = app(GraficosService::class)->delGrupo($this->docente->id);

        $this->assertCount(1, $g['apoyo']['pocos_aciertos']);
        $this->assertStringStartsWith($a->nombre, $g['apoyo']['pocos_aciertos'][0]);
    }

    public function test_apoyo_por_dejar_de_jugar()
    {
        $a = $this->alumnos()[0];
        $this->sesion($a, 4, 0, GraficosService::DIAS_SIN_JUGAR + 3);   // buenos resultados, pero hace días
        $b = $this->alumnos()[1];
        $this->sesion($b, 4, 0, 2);                                      // reciente

        $g = app(GraficosService::class)->delGrupo($this->docente->id);

        $this->assertCount(1, $g['apoyo']['sin_jugar']);
        $this->assertStringStartsWith($a->nombre, $g['apoyo']['sin_jugar'][0]);
        $this->assertSame([], $g['apoyo']['pocos_aciertos']);
    }

    public function test_niveles_del_grupo_cuentan_el_nivel_mas_alto_y_los_que_no_empezaron()
    {
        $e = $this->escenario();
        $a = $this->alumnos()[0];
        // Juega el nivel 1 y desbloquea el 2 de Suma de Frutas
        $repo = app(\App\Repositories\EstudianteRepository::class);
        $grado = (int) Matricula::where('estudiante_id', $a->id)->value('grado');
        $this->assertSame($grado, (int) $e['nivel1']->grado, 'El escenario debe coincidir con el grado del alumno.');
        $repo->desbloquearNivel($a->id, $e['juego']->id, $e['nivel1']);
        $repo->desbloquearNivel($a->id, $e['juego']->id, $e['nivel2']);

        $g = app(GraficosService::class)->delGrupo($this->docente->id);

        $suma = collect($g['niveles'])->firstWhere('juego', $e['juego']->nombre_es);
        $this->assertNotNull($suma);
        $this->assertSame(1, $suma['por_nivel'][$e['nivel2']->numero_nivel]);
        $this->assertSame(0, $suma['por_nivel'][$e['nivel1']->numero_nivel]);
        $this->assertSame(2, $suma['sin_empezar']);
    }

    public function test_el_grupo_no_incluye_alumnos_de_otro_docente()
    {
        $ajeno = $this->alumnoAjeno();
        $this->sesion($ajeno, 4, 0, 0);

        $g = app(GraficosService::class)->delGrupo($this->docente->id);

        $this->assertSame(0, collect($g['actividad'])->sum('cantidad'));
        $this->assertSame(3, $g['total']);
    }

    public function test_panel_muestra_los_graficos_del_grupo()
    {
        $a = $this->alumnos()->first();
        $this->sesion($a, 3, 1, 0);
        // Con un nivel alcanzado, el gráfico de niveles tiene al menos una barra que dibujar.
        $e = $this->escenario();
        app(\App\Repositories\EstudianteRepository::class)->desbloquearNivel($a->id, $e['juego']->id, $e['nivel1']);

        $r = $this->get(route('docente.panel'));

        $r->assertOk();
        $r->assertSee('Aciertos por alumno');
        $r->assertSee('Quiénes jugaron cada día');
        $r->assertSee('Podrían necesitar apoyo');
        $r->assertSee('En qué nivel está el grupo');
        $r->assertSee('<svg', false);
        // Las barras usan su propio color: no debe filtrarse el de otras partes del panel.
        $r->assertSee('fill="#1B3361"', false);
        $r->assertSee('fill="#0E6B7D"', false);
    }

    public function test_panel_sin_asignacion_no_falla()
    {
        $this->assertSame([], app(GraficosService::class)->delGrupo(null));
    }
}
