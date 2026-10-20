<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Matricula;
use App\Repositories\DocenteRepository;

class DocenteRepositoryTest extends RepositorioTestCase
{
    private function docente1(): Docente
    {
        return Docente::orderBy('id')->firstOrFail();
    }

    public function test_por_usuario_devuelve_el_perfil_de_docente_o_null()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();

        $this->assertSame($doc->id, $repo->porUsuario($doc->usuario_id)->id);
        $this->assertNull($repo->porUsuario(999999));
    }

    public function test_matriculas_activas_del_anio_filtra_por_docente_anio_y_activo()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();

        $anio = (int) Matricula::where('docente_id', $doc->id)->value('anio_lectivo');
        $todas = $repo->matriculasActivasDelAnio($doc->id, $anio);
        $this->assertCount(3, $todas);
        $this->assertTrue($todas->first()->relationLoaded('estudiante'));

        $this->assertCount(0, $repo->matriculasActivasDelAnio($doc->id, $anio + 1));

        Matricula::where('docente_id', $doc->id)->orderBy('id')->first()->update(['activo' => false]);
        $this->assertCount(2, $repo->matriculasActivasDelAnio($doc->id, $anio));

        $conFicha = $repo->matriculasActivasDelAnio($doc->id, $anio, ['estudiante', 'institucion']);
        $this->assertTrue($conFicha->first()->relationLoaded('institucion'));
    }

    public function test_matriculas_del_año_incluye_las_ya_abiertas_para_el_año_siguiente_pero_no_las_de_años_pasados()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();
        $anio = (int) Matricula::where('docente_id', $doc->id)->value('anio_lectivo');

        $ids = Matricula::where('docente_id', $doc->id)->orderBy('id')->pluck('id');
        Matricula::find($ids[0])->update(['anio_lectivo' => $anio + 1]);   // pasó de grado: matrícula del año que viene
        Matricula::find($ids[1])->update(['anio_lectivo' => $anio - 1]);   // matrícula vieja sin cerrar

        $vistas = $repo->matriculasActivasDelAnio($doc->id, $anio)->pluck('id')->all();

        $this->assertContains($ids[0], $vistas);
        $this->assertNotContains($ids[1], $vistas);
    }

    public function test_cerrar_matricula_la_deja_inactiva_con_fecha_de_fin()
    {
        $repo = app(DocenteRepository::class);
        $m = Matricula::where('docente_id', $this->docente1()->id)->firstOrFail();

        $repo->cerrarMatricula($m, \Carbon\Carbon::parse('2026-11-30'));

        $m = $m->fresh();
        $this->assertFalse((bool) $m->activo);
        $this->assertSame('2026-11-30', $m->fecha_fin->toDateString());
    }

    public function test_es_estudiante_del_docente_y_matricula_activa_del_alumno()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();
        $mio  = Matricula::where('docente_id', $doc->id)->firstOrFail()->estudiante_id;
        $ajeno = Matricula::where('docente_id', '!=', $doc->id)->firstOrFail()->estudiante_id;

        $this->assertTrue($repo->esEstudianteDelDocente($mio, $doc->id));
        $this->assertFalse($repo->esEstudianteDelDocente($ajeno, $doc->id));
        $this->assertFalse($repo->esEstudianteDelDocente($mio, null));

        $this->assertNotNull($repo->matriculaActivaDelAlumno($mio, $doc->id));
        $this->assertNull($repo->matriculaActivaDelAlumno($ajeno, $doc->id));

        Matricula::where('estudiante_id', $mio)->update(['activo' => false]);
        $this->assertFalse($repo->esEstudianteDelDocente($mio, $doc->id));
        $this->assertNull($repo->matriculaActivaDelAlumno($mio, $doc->id));
    }

    public function test_ultima_matricula_activa_con_ficha_toma_la_mas_reciente_y_carga_relaciones()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();
        $vieja = Matricula::where('docente_id', $doc->id)->firstOrFail();

        $nueva = $repo->crearMatricula([
            'estudiante_id'  => $vieja->estudiante_id,
            'institucion_id' => $vieja->institucion_id,
            'docente_id'     => $doc->id,
            'grado'          => $vieja->grado,
            'anio_lectivo'   => $vieja->anio_lectivo + 1,
            'activo'         => true,
            'fecha_inicio'   => now()->toDateString(),
        ]);

        $m = $repo->ultimaMatriculaActivaConFicha($vieja->estudiante_id, $doc->id);

        $this->assertSame($nueva->id, $m->id);
        $this->assertTrue($m->relationLoaded('estudiante'));
        $this->assertTrue($m->relationLoaded('institucion'));
        $this->assertNull($repo->ultimaMatriculaActivaConFicha(999999, $doc->id));
    }

    public function test_ids_estudiantes_activos()
    {
        $repo = app(DocenteRepository::class);
        $doc  = $this->docente1();

        $this->assertCount(3, $repo->idsEstudiantesActivos($doc->id));

        Matricula::where('docente_id', $doc->id)->orderBy('id')->first()->update(['activo' => false]);
        $this->assertCount(2, $repo->idsEstudiantesActivos($doc->id));
    }

    public function test_habilitacion_de_juegos_se_guarda_y_se_actualiza_sin_duplicar()
    {
        $repo  = app(DocenteRepository::class);
        $doc   = $this->docente1();
        $juego = Juego::first();

        $this->assertCount(0, $repo->habilitacionDeJuegos($doc->id, 1));

        $repo->guardarHabilitacion($doc->id, $juego->id, 1, false);
        $this->assertFalse((bool) $repo->habilitacionDeJuegos($doc->id, 1)[$juego->id]);

        $repo->guardarHabilitacion($doc->id, $juego->id, 1, true);
        $mapa = $repo->habilitacionDeJuegos($doc->id, 1);
        $this->assertCount(1, $mapa);
        $this->assertTrue((bool) $mapa[$juego->id]);

        $this->assertCount(0, $repo->habilitacionDeJuegos($doc->id, 2), 'Otro grado no se mezcla.');
    }
}
