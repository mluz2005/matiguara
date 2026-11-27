<?php

namespace Tests\Feature\Repositories;

use App\Models\CatalogoLogro;
use App\Models\ContenidoNivel;
use App\Models\Juego;
use App\Support\TextosCamino;
use Database\Seeders\CaminoEscuelaSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Guaraní revisado de «El camino a la escuela»: lo que deja el seeder y lo que corrige
 * la migración 2026_11_19_000005 en una base que ya tenía datos.
 */
class GuaraniCaminoTest extends RepositorioTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_19_000005_aplicar_guarani_revisado_camino_a_la_escuela.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CaminoEscuelaSeeder::class);
    }

    private function juego(): Juego
    {
        return Juego::where('nombre_es', 'El camino a la escuela')->firstOrFail();
    }

    private function problemas()
    {
        return ContenidoNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $this->juego()->id));
    }

    public function test_el_seeder_deja_el_guarani_revisado()
    {
        $this->assertSame(TextosCamino::INSTRUCCIONES_GN, $this->juego()->instrucciones_gn);
        $this->assertSame(738, $this->problemas()->count());

        foreach ($this->problemas()->get() as $p) {
            $this->assertSame(TextosCamino::enunciado($p->enunciado_es), $p->enunciado_gn, "Problema {$p->id}");
        }

        foreach (TextosCamino::MEDALLAS as $es => [$nombre, $descripcion]) {
            $logro = CatalogoLogro::where('nombre_es', $es)->firstOrFail();
            $this->assertSame($nombre, $logro->nombre_gn, $es);
            $this->assertSame($descripcion, $logro->descripcion_gn, $es);
        }
    }

    public function test_la_migracion_corrige_el_guarani_viejo_y_es_repetible()
    {
        DB::table('juegos')->where('id', $this->juego()->id)->update(['instrucciones_gn' => 'viejo']);
        $this->problemas()->update(['enunciado_gn' => 'viejo']);
        DB::table('catalogo_logros')->whereIn('nombre_es', array_keys(TextosCamino::MEDALLAS))
            ->update(['nombre_gn' => 'viejo', 'descripcion_gn' => null]);

        (require base_path(self::MIGRACION))->up();
        (require base_path(self::MIGRACION))->up();

        $this->assertSame(TextosCamino::INSTRUCCIONES_GN, $this->juego()->fresh()->instrucciones_gn);

        foreach ($this->problemas()->get() as $p) {
            $this->assertSame(TextosCamino::enunciado($p->enunciado_es), $p->enunciado_gn, "Problema {$p->id}");
        }
        foreach (TextosCamino::MEDALLAS as $es => [$nombre, $descripcion]) {
            $logro = CatalogoLogro::where('nombre_es', $es)->firstOrFail();
            $this->assertSame($nombre, $logro->nombre_gn, $es);
            $this->assertSame($descripcion, $logro->descripcion_gn, $es);
        }
    }

    public function test_un_enunciado_desconocido_queda_como_estaba()
    {
        $problema = $this->problemas()->firstOrFail();
        DB::table('contenido_nivel')->where('id', $problema->id)
            ->update(['enunciado_es' => 'Un problema cualquiera.', 'enunciado_gn' => 'tal cual']);

        (require base_path(self::MIGRACION))->up();

        $this->assertSame('tal cual', $problema->fresh()->enunciado_gn);
    }

    public function test_la_migracion_no_toca_a_los_otros_juegos()
    {
        $otro = Juego::where('nombre_es', 'Suma de Frutas')->firstOrFail();
        $antes = [$otro->nombre_gn, $otro->instrucciones_gn];

        (require base_path(self::MIGRACION))->up();

        $otro->refresh();
        $this->assertSame($antes, [$otro->nombre_gn, $otro->instrucciones_gn]);
    }
}
