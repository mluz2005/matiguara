<?php

namespace Tests\Feature\Repositories;

use App\Models\MensajeRetro;
use App\Repositories\MensajeRetroRepository;

class MensajeRetroRepositoryTest extends RepositorioTestCase
{
    public function test_aleatorio_devuelve_un_mensaje_activo_del_tipo_y_contexto()
    {
        $repo = app(MensajeRetroRepository::class);

        $m = $repo->aleatorio('acierto', 'general');

        $this->assertNotNull($m);
        $this->assertSame('acierto', $m->tipo);
        $this->assertSame('general', $m->contexto);
        $this->assertTrue((bool) $m->activo);
    }

    public function test_aleatorio_no_repite_el_excluido_y_respeta_los_inactivos()
    {
        $repo = app(MensajeRetroRepository::class);
        $ids  = MensajeRetro::where('tipo', 'acierto')->where('contexto', 'general')->pluck('id')->all();
        $this->assertGreaterThanOrEqual(2, count($ids));

        // Deja activos solo dos: excluyendo uno, siempre sale el otro
        MensajeRetro::whereIn('id', $ids)->update(['activo' => false]);
        MensajeRetro::whereIn('id', [$ids[0], $ids[1]])->update(['activo' => true]);

        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($ids[1], $repo->aleatorio('acierto', 'general', $ids[0])->id);
        }

        MensajeRetro::whereIn('id', $ids)->update(['activo' => false]);
        $this->assertNull($repo->aleatorio('acierto', 'general'));
        $this->assertNull($repo->aleatorio('tipo_inexistente', 'general'));
    }
}
