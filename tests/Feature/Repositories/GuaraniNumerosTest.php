<?php

namespace Tests\Feature\Repositories;

use App\Models\Juego;
use App\Support\Numeros\TextosNumeros;
use Database\Seeders\MatiNumerosSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Guaraní revisado de «Mati y los números»: lo que deja el seeder y lo que corrige
 * la migración 2026_11_19_000006 en una base que ya tenía datos.
 */
class GuaraniNumerosTest extends RepositorioTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_19_000006_aplicar_guarani_revisado_mati_y_los_numeros.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MatiNumerosSeeder::class);
    }

    public function test_el_seeder_deja_el_guarani_revisado()
    {
        $j = Juego::where('nombre_es', 'Mati y los números')->firstOrFail();
        $this->assertSame('Mati ha papapykuéra', $j->nombre_gn);
        $this->assertSame(TextosNumeros::INSTRUCCIONES_GN, $j->instrucciones_gn);
    }

    public function test_la_migracion_corrige_el_borrador_y_es_repetible()
    {
        DB::table('juegos')->where('nombre_es', 'Mati y los números')
            ->update(['nombre_gn' => 'Mati ha papapy', 'instrucciones_gn' => 'viejo']);

        (require base_path(self::MIGRACION))->up();
        (require base_path(self::MIGRACION))->up();

        $j = Juego::where('nombre_es', 'Mati y los números')->firstOrFail();
        $this->assertSame(TextosNumeros::NOMBRE_GN, $j->nombre_gn);
        $this->assertSame(TextosNumeros::INSTRUCCIONES_GN, $j->instrucciones_gn);
        $this->assertSame(1, Juego::where('nombre_es', 'Mati y los números')->count());
    }

    public function test_la_migracion_no_toca_otros_juegos()
    {
        $otro = Juego::where('nombre_es', '!=', 'Mati y los números')->first();
        if (!$otro) {
            $this->markTestSkipped('No hay otro juego cargado.');
        }
        $antes = $otro->instrucciones_gn;
        (require base_path(self::MIGRACION))->up();
        $this->assertSame($antes, $otro->fresh()->instrucciones_gn);
    }
}
