<?php

namespace Tests\Feature\Repositories;

use App\Models\ConfigInstitucion;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Services\MatriculaService;
use Carbon\Carbon;

/**
 * RF-18 / RN-10 (cierre del año y baja del egresado) y RF-17 (grado que ve
 * el estudiante) en MatriculaService.
 */
class MatriculaServiceTest extends RepositorioTestCase
{
    private function servicio(): MatriculaService
    {
        return app(MatriculaService::class);
    }

    private function matriculaDe(string $codigo): Matricula
    {
        $e = Estudiante::where('codigo_acceso', $codigo)->firstOrFail();

        return Matricula::where('estudiante_id', $e->id)->where('activo', true)->firstOrFail();
    }

    /** Los sembradores solo cargan niveles de 1.°: se agrega uno del grado pedido. */
    protected function cargarNivelDelGrado(int $grado): void
    {
        $n = Nivel::orderBy('id')->firstOrFail()->replicate();
        $n->grado = $grado;
        $n->numero_nivel = 1;
        $n->desbloqueado_por_defecto = true;
        $n->save();
    }

    private function configDe(Matricula $m): ConfigInstitucion
    {
        return app(\App\Repositories\InstitucionRepository::class)->configOCrearPorDefecto($m->institucion);
    }

    public function test_cerrar_tercer_grado_programa_la_baja_con_los_meses_por_defecto()
    {
        $m = $this->matriculaDe('SAN-1A-001');
        $m->update(['grado' => 3]);

        $baja = $this->servicio()->cerrarMatricula($m->fresh(), Carbon::parse('2026-11-30'));

        $this->assertSame('2027-03-30', $baja->toDateString());
        $m = $m->fresh();
        $this->assertFalse((bool) $m->activo);
        $this->assertSame('2026-11-30', $m->fecha_fin->toDateString());
        $this->assertSame('2027-03-30', $m->estudiante->fresh()->fecha_baja_programada->toDateString());
    }

    public function test_cerrar_tercer_grado_usa_los_meses_de_la_institucion_y_no_desborda_el_mes()
    {
        $m = $this->matriculaDe('SAN-1A-001');
        $m->update(['grado' => 3]);
        $this->configDe($m)->update(['meses_conservacion_egresados' => 6]);

        $baja = $this->servicio()->cerrarMatricula($m->fresh(), Carbon::parse('2026-08-31'));
        $this->assertSame('2027-02-28', $baja->toDateString());
    }

    public function test_cerrar_primero_o_segundo_no_programa_baja()
    {
        $m = $this->matriculaDe('SAN-1A-001');

        $baja = $this->servicio()->cerrarMatricula($m, Carbon::parse('2026-11-30'));

        $this->assertNull($baja);
        $this->assertNull($m->estudiante->fresh()->fecha_baja_programada);
        $this->assertFalse((bool) $m->fresh()->activo);
    }

    public function test_una_matricula_ya_cerrada_no_se_puede_cerrar_otra_vez()
    {
        $m = $this->matriculaDe('SAN-1A-001');
        $this->servicio()->cerrarMatricula($m);

        $this->expectException(\DomainException::class);
        $this->servicio()->cerrarMatricula($m->fresh());
    }

    public function test_promover_abre_la_matricula_del_grado_siguiente_con_el_mismo_docente()
    {
        $this->cargarNivelDelGrado(2);
        $m = $this->matriculaDe('SAN-1A-001');

        $nueva = $this->servicio()->promover($m, Carbon::parse('2026-12-10'));

        $this->assertSame(2, (int) $nueva->grado);
        $this->assertSame($m->docente_id, $nueva->docente_id);
        $this->assertSame($m->institucion_id, $nueva->institucion_id);
        $this->assertSame($m->estudiante_id, $nueva->estudiante_id);
        $this->assertTrue((bool) $nueva->activo);
        $this->assertSame(2027, (int) $nueva->anio_lectivo);
        $this->assertFalse((bool) $m->fresh()->activo);
        $this->assertNull($m->estudiante->fresh()->fecha_baja_programada);
    }

    public function test_promover_exige_que_haya_niveles_del_grado_siguiente()
    {
        $m = $this->matriculaDe('SAN-1A-001');

        try {
            $this->servicio()->promover($m);
            $this->fail('Debía rechazar el pase sin niveles cargados.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('2.° grado', $e->getMessage());
        }

        $this->assertTrue((bool) $m->fresh()->activo, 'La matrícula no se toca si el pase se rechaza.');
        $this->assertSame(1, Matricula::where('estudiante_id', $m->estudiante_id)->count());
    }

    public function test_tercer_grado_no_se_promueve()
    {
        $this->cargarNivelDelGrado(4);
        $m = $this->matriculaDe('SAN-1A-001');
        $m->update(['grado' => 3]);

        $this->expectException(\DomainException::class);
        $this->servicio()->promover($m->fresh());
    }

    /** Ana pasa de 1.° a 2.°: queda con una matrícula cerrada (1.°) y una activa (2.°). */
    private function anaEnSegundo(): Estudiante
    {
        $this->cargarNivelDelGrado(2);
        $m = $this->matriculaDe('SAN-1A-001');
        $this->servicio()->promover($m, Carbon::parse('2026-12-10'));

        return $m->estudiante->fresh();
    }

    public function test_rf17_sin_permiso_el_estudiante_solo_ve_su_grado_activo()
    {
        $ana = $this->anaEnSegundo();

        $vista = $this->servicio()->gradoParaProgreso($ana, 1);

        $this->assertSame(2, $vista['grado']);
        $this->assertSame(2, $vista['actual']);
        $this->assertSame([], $vista['anteriores']);
    }

    public function test_rf17_con_permiso_el_estudiante_puede_consultar_grados_anteriores()
    {
        $ana = $this->anaEnSegundo();
        $this->configDe($ana->matriculaActiva)->update(['permitir_acceso_grados_ant' => true]);

        $this->assertSame(1, $this->servicio()->gradoParaProgreso($ana, 1)['grado']);
        $this->assertSame([1], $this->servicio()->gradoParaProgreso($ana)['anteriores']);
        $this->assertSame(2, $this->servicio()->gradoParaProgreso($ana)['grado']);
        // Un grado que nunca cursó no se puede pedir
        $this->assertSame(2, $this->servicio()->gradoParaProgreso($ana, 3)['grado']);
    }

    public function test_el_egresado_ve_como_actual_el_ultimo_grado_que_cursó()
    {
        $m = $this->matriculaDe('SAN-1A-001');
        $m->update(['grado' => 3]);
        $this->servicio()->cerrarMatricula($m->fresh());

        $vista = $this->servicio()->gradoParaProgreso($m->estudiante->fresh());

        $this->assertSame(3, $vista['grado']);
        $this->assertNull($vista['activa']);
    }

    public function test_gradosDelHistorial_lista_todos_los_grados_cursados()
    {
        $ana = $this->anaEnSegundo();

        $this->assertSame([1, 2], $this->servicio()->gradosDelHistorial($ana));
    }
}
