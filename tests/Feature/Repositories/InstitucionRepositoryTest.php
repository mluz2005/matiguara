<?php

namespace Tests\Feature\Repositories;

use App\Models\ConfigInstitucion;
use App\Models\Institucion;
use App\Repositories\InstitucionRepository;

class InstitucionRepositoryTest extends RepositorioTestCase
{
    public function test_contar_y_listar_activas()
    {
        $repo   = app(InstitucionRepository::class);
        $activas = Institucion::where('activo', true)->count();

        $this->assertSame($activas, $repo->contarActivas());
        $this->assertCount($activas, $repo->activas());

        Institucion::orderBy('id')->first()->update(['activo' => false]);
        $this->assertSame($activas - 1, $repo->contarActivas());
        $this->assertCount($activas - 1, $repo->activas());
    }

    public function test_paginadas_con_config_ordena_por_nombre_y_carga_relaciones()
    {
        $repo = app(InstitucionRepository::class);

        $pagina = $repo->paginadasConConfig(1);

        $this->assertSame(Institucion::count(), $pagina->total());
        $this->assertCount(1, $pagina->items());
        $this->assertSame(Institucion::orderBy('nombre')->value('id'), $pagina->items()[0]->id);
        $this->assertTrue($pagina->items()[0]->relationLoaded('config'));
        $this->assertTrue($pagina->items()[0]->relationLoaded('asignaciones'));
    }

    public function test_buscar_crear_y_actualizar()
    {
        $repo = app(InstitucionRepository::class);

        $nueva = $repo->crear(['nombre' => 'Escuela Nueva', 'codigo' => 'EEB-NUE-001', 'ciudad' => 'Encarnación', 'tipo' => 'EEB', 'activo' => true]);
        $this->assertSame($nueva->id, $repo->buscar($nueva->id)->id);
        $this->assertSame($nueva->id, $repo->buscarConConfig($nueva->id)->id);

        $repo->actualizar($nueva, ['nombre' => 'Escuela Renombrada', 'activo' => false]);
        $this->assertSame('Escuela Renombrada', $nueva->fresh()->nombre);
        $this->assertFalse((bool) $nueva->fresh()->activo);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $repo->buscar(999999);
    }

    public function test_config_por_defecto_se_crea_una_vez_y_se_actualiza()
    {
        $repo  = app(InstitucionRepository::class);
        $nueva = $repo->crear(['nombre' => 'Sin Config', 'codigo' => 'EEB-SIN-001', 'ciudad' => 'X', 'tipo' => 'EEB', 'activo' => true]);

        $this->assertNull($nueva->config);
        $config = $repo->configOCrearPorDefecto($nueva);
        $this->assertSame(3, (int) $config->dias_reinicio_racha_default);
        $this->assertSame(4, (int) $config->meses_conservacion_egresados);
        $this->assertFalse((bool) $config->permitir_acceso_grados_ant);

        // Con la config ya cargada no se duplica
        $nueva = $repo->buscarConConfig($nueva->id);
        $this->assertSame($config->id, $repo->configOCrearPorDefecto($nueva)->id);
        $this->assertSame(1, ConfigInstitucion::where('institucion_id', $nueva->id)->count());

        $repo->actualizarConfig($nueva->id, ['dias_reinicio_racha_default' => 7, 'permitir_acceso_grados_ant' => true, 'meses_conservacion_egresados' => 9]);
        $c = ConfigInstitucion::where('institucion_id', $nueva->id)->first();
        $this->assertSame(7, (int) $c->dias_reinicio_racha_default);
        $this->assertTrue((bool) $c->permitir_acceso_grados_ant);
        $this->assertSame(9, (int) $c->meses_conservacion_egresados);
    }
}
