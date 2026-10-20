<?php

namespace Tests\Feature\Repositories;

use App\Models\ContenidoNivel;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Nivel;
use App\Repositories\SesionRepository;
use App\Models\TarjetaNivel;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MemoriaJuegoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Base de las pruebas de la capa app/Repositories.
 *
 * SEGURIDAD: RefreshDatabase ejecuta migrate:fresh, que BORRA todas las tablas
 * de la base configurada. Por eso estas pruebas solo corren contra una base
 * cuyo nombre termina en "_test" (phpunit.xml usa matiguara_test). Si la base
 * configurada es otra, la prueba se aborta antes de tocar nada.
 */
abstract class RepositorioTestCase extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseDeLaravel;
    }

    protected function refreshDatabase()
    {
        $base = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($base, '_test')) {
            throw new RuntimeException(
                "Prueba abortada: la base configurada es '{$base}'. Las pruebas de repositorios " .
                "solo corren contra una base cuyo nombre termina en '_test' (p. ej. matiguara_test)."
            );
        }

        $this->refreshDatabaseDeLaravel();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Escenario base con los datos sembrados: un estudiante y un juego con
     * dos niveles consecutivos del mismo grado y con ejercicios cargados.
     *
     * @return array{estudiante:Estudiante,juego:Juego,nivel1:Nivel,nivel2:Nivel,ejercicios:\Illuminate\Support\Collection}
     */
    protected function escenario(): array
    {
        $estudiante = Estudiante::first();

        $nivel1 = Nivel::has('ejercicios')
            ->whereHas('juego', fn ($q) => $q->where('nombre_es', 'Suma de Frutas'))
            ->orderBy('grado')->orderBy('numero_nivel')
            ->firstOrFail();
        $nivel2 = $nivel1->nivelSiguiente();
        $this->assertNotNull($nivel2, 'El escenario necesita dos niveles consecutivos.');

        $ejercicios = ContenidoNivel::where('nivel_id', $nivel1->id)->orderBy('orden')->get();
        $this->assertGreaterThanOrEqual(3, $ejercicios->count());

        return [
            'estudiante' => $estudiante,
            'juego'      => $nivel1->juego,
            'nivel1'     => $nivel1,
            'nivel2'     => $nivel2,
            'ejercicios' => $ejercicios,
        ];
    }

    /** Crea una sesión 'en_curso' del escenario (se pueden sobrescribir campos). */
    protected function nuevaSesion(array $e, array $extra = [])
    {
        return app(SesionRepository::class)->crear(array_merge([
            'estudiante_id' => $e['estudiante']->id,
            'juego_id'      => $e['juego']->id,
            'nivel_id'      => $e['nivel1']->id,
            'fecha_inicio'  => now(),
            'estado'        => 'en_curso',
        ], $extra));
    }

    /**
     * Escenario del juego "Encuentra el par". DatabaseSeeder NO incluye
     * MemoriaJuegoSeeder, por eso se siembra aquí.
     *
     * @return array{estudiante:Estudiante,juego:Juego,nivel1:Nivel,nivel2:Nivel,tarjetas:\Illuminate\Support\Collection}
     */
    protected function escenarioMemoria(): array
    {
        $this->seed(MemoriaJuegoSeeder::class);

        $juego  = Juego::where('nombre_es', 'Encuentra el par')->firstOrFail();
        $nivel1 = Nivel::where('juego_id', $juego->id)->orderBy('numero_nivel')->firstOrFail();
        $nivel2 = $nivel1->nivelSiguiente();
        $this->assertNotNull($nivel2);

        $tarjetas = TarjetaNivel::where('nivel_id', $nivel1->id)->where('activo', true)->get();
        $this->assertGreaterThanOrEqual(2, $tarjetas->count());

        return [
            'estudiante' => Estudiante::first(),
            'juego'      => $juego,
            'nivel1'     => $nivel1,
            'nivel2'     => $nivel2,
            'tarjetas'   => $tarjetas,
        ];
    }
}
