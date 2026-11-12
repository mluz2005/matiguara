<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Juego;
use Database\Seeders\CaminoEscuelaSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Menú de juegos v3: el niño entra y ve SOLO una tarjeta por juego;
 * los niveles viven dentro de una vista que se abre al tocar la tarjeta.
 * Los sonidos arrancan activados desde la pantalla de ingreso.
 */
class MenuJuegosTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseDeLaravel;
    }

    protected function refreshDatabase()
    {
        $base = (string) config('database.connections.' . config('database.default') . '.database');
        if (!str_ends_with($base, '_test')) {
            throw new RuntimeException("Prueba abortada: la base '{$base}' no termina en '_test'.");
        }
        $this->refreshDatabaseDeLaravel();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(CaminoEscuelaSeeder::class);
    }

    public function test_el_menu_muestra_una_tarjeta_por_juego_y_una_vista_oculta_con_niveles()
    {
        $this->actingAs(Estudiante::first(), 'estudiante');
        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();

        $juegos = Juego::count();
        $this->assertGreaterThan(0, $juegos);
        $this->assertSame($juegos, substr_count($html, 'data-abrir="juego-'));
        $this->assertSame($juegos, preg_match_all('/<section id="juego-\d+" class="jvista" data-vista hidden/', $html));
        $this->assertStringContainsString('¿A qué querés jugar hoy?', $html);
        $this->assertStringContainsString('data-volver', $html);
        $this->assertStringContainsString('Volver', $html);
    }

    public function test_cada_vista_conserva_los_formularios_de_inicio_de_nivel()
    {
        $e = Estudiante::first();
        $this->actingAs($e, 'estudiante');
        $html = $this->get(route('estudiante.juegos'))->assertOk()->getContent();

        $laberinto = Juego::where('nombre_es', 'El camino a la escuela')->firstOrFail();
        $this->assertStringContainsString('/laberinto/', $html);
        $this->assertStringContainsString('El camino a la escuela', $html);
        $this->assertStringContainsString('id="juego-' . $laberinto->id . '"', $html);
    }

    public function test_la_pantalla_de_ingreso_activa_los_sonidos_por_defecto()
    {
        $this->get(route('estudiante.login'))
            ->assertOk()
            ->assertSee("localStorage.setItem('matiguara_musica', '1')", false)
            ->assertSee("localStorage.setItem('matiguara_audio', '1')", false);
    }
}
