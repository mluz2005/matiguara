<?php

namespace Tests\Feature\Repositories;

use App\Models\ContenidoNivel;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Nivel;
use Database\Seeders\CompletarContenidoSeeder;
use Illuminate\Support\Facades\DB;

/**
 * En una instalación nueva las migraciones de datos corren antes de que existan
 * los juegos, así que no hacen nada. CompletarContenidoSeeder las vuelve a
 * ejecutar: Flash Matemático con 10 niveles por grado, Suma de Frutas con sus
 * 6 niveles de 1.° y la medalla Flash Master.
 */
class CompletarContenidoSeederTest extends RepositorioTestCase
{
    private function juego(string $nombre): Juego
    {
        return Juego::where('nombre_es', $nombre)->firstOrFail();
    }

    private function niveles(string $juego, int $grado)
    {
        return Nivel::where('juego_id', $this->juego($juego)->id)->where('grado', $grado)->orderBy('numero_nivel')->get();
    }

    public function test_antes_del_sembrador_flash_solo_tiene_tres_niveles_de_primero()
    {
        $this->assertCount(3, $this->niveles('Flash Matemático', 1));
        $this->assertCount(0, $this->niveles('Flash Matemático', 2));
        $this->assertCount(0, $this->niveles('Flash Matemático', 3));
    }

    public function test_flash_queda_con_10_niveles_en_cada_grado_y_el_primero_abierto()
    {
        $this->seed(CompletarContenidoSeeder::class);

        foreach ([1, 2, 3] as $grado) {
            $niveles = $this->niveles('Flash Matemático', $grado);
            $this->assertCount(10, $niveles, "Grado {$grado}: 10 niveles.");
            $this->assertEquals(range(1, 10), $niveles->pluck('numero_nivel')->all());
            $this->assertTrue((bool) $niveles->first()->desbloqueado_por_defecto);
            $this->assertFalse((bool) $niveles->last()->desbloqueado_por_defecto);

            foreach ($niveles as $n) {
                $this->assertGreaterThanOrEqual(5, ContenidoNivel::where('nivel_id', $n->id)->count(), "Grado {$grado}, nivel {$n->numero_nivel}.");
                $this->assertNotNull($n->tiempo_mostrar_ms);
            }
        }
    }

    public function test_suma_de_frutas_de_primero_queda_con_6_niveles()
    {
        $this->seed(CompletarContenidoSeeder::class);

        $this->assertEquals(range(1, 6), $this->niveles('Suma de Frutas', 1)->pluck('numero_nivel')->all());
    }

    public function test_aparece_la_medalla_flash_master()
    {
        $this->seed(CompletarContenidoSeeder::class);

        $this->assertTrue(DB::table('catalogo_logros')->where('nombre_es', 'Flash Master')->exists());
    }

    public function test_el_sembrador_es_idempotente()
    {
        $this->seed(CompletarContenidoSeeder::class);
        $niveles    = Nivel::count();
        $ejercicios = ContenidoNivel::count();
        $medallas   = DB::table('catalogo_logros')->count();

        $this->seed(CompletarContenidoSeeder::class);

        $this->assertSame($niveles, Nivel::count());
        $this->assertSame($ejercicios, ContenidoNivel::count());
        $this->assertSame($medallas, DB::table('catalogo_logros')->count());
    }

    public function test_el_menu_del_estudiante_de_segundo_muestra_los_niveles_de_flash()
    {
        $this->seed(CompletarContenidoSeeder::class);

        $estudiante = Estudiante::first();
        $estudiante->matriculaActiva->forceFill(['grado' => 2])->save();
        $this->actingAs($estudiante, 'estudiante');

        $html  = $this->get(route('estudiante.juegos'))->assertOk()->getContent();
        $flash = $this->juego('Flash Matemático');
        $this->assertSame(10, preg_match_all('#/juego/' . $flash->id . '/nivel/\d+/iniciar#', $html));
    }
}
