<?php

namespace Tests\Feature\Repositories;

use App\Models\Juego;
use App\Models\TarjetaNivel;
use App\Support\TarjetasGuarani;
use Database\Seeders\ContenidoGrados23Seeder;
use Database\Seeders\MemoriaJuegoSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Guaraní revisado de «Encuentra el par»: lo que dejan los seeders y lo que corrige
 * la migración 2026_11_19_000004 en una base que ya tenía datos.
 */
class GuaraniEncuentraElParTest extends RepositorioTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_19_000004_aplicar_guarani_revisado_encuentra_el_par.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MemoriaJuegoSeeder::class);
        $this->seed(ContenidoGrados23Seeder::class); // copia las tarjetas a 2.° y 3.er grado
    }

    private function juego(): Juego
    {
        return Juego::where('nombre_es', 'Encuentra el par')->firstOrFail();
    }

    private function tarjetas()
    {
        return TarjetaNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $this->juego()->id))->get();
    }

    public function test_el_seeder_deja_el_guarani_revisado()
    {
        $this->assertSame('Ejuhu iñirũ ojoguáva', $this->juego()->nombre_gn);
        $this->assertSame("Embojere mokõi kuatia'atã ha ejuhu umi ojoguáva opa mboyve aravo.", $this->juego()->instrucciones_gn);

        $this->assertGreaterThan(0, $this->tarjetas()->count());
        foreach ($this->tarjetas() as $t) {
            $this->assertSame(
                TarjetasGuarani::nombre($t->tipo_contenido, (string) $t->valor),
                $t->nombre_gn,
                "{$t->tipo_contenido} {$t->valor}"
            );
        }
    }

    public function test_la_migracion_corrige_el_guarani_viejo_en_los_tres_grados_y_es_repetible()
    {
        DB::table('juegos')->where('id', $this->juego()->id)->update(['nombre_gn' => 'viejo', 'instrucciones_gn' => 'viejo']);
        DB::table('tarjetas_nivel')->update(['nombre_gn' => 'viejo']);

        (require base_path(self::MIGRACION))->up();
        (require base_path(self::MIGRACION))->up();

        $this->assertSame('Ejuhu iñirũ ojoguáva', $this->juego()->fresh()->nombre_gn);

        $grados = [];
        foreach ($this->tarjetas() as $t) {
            $this->assertSame(TarjetasGuarani::nombre($t->tipo_contenido, (string) $t->valor), $t->nombre_gn, "{$t->tipo_contenido} {$t->valor}");
            $grados[$t->nivel->grado] = true;
        }
        $this->assertSame([1, 2, 3], array_keys($grados));
    }

    public function test_la_migracion_no_toca_a_los_otros_juegos()
    {
        $otro = Juego::where('nombre_es', '!=', 'Encuentra el par')->firstOrFail();
        $antes = [$otro->nombre_gn, $otro->instrucciones_gn];

        (require base_path(self::MIGRACION))->up();

        $otro->refresh();
        $this->assertSame($antes, [$otro->nombre_gn, $otro->instrucciones_gn]);
    }
}
