<?php

namespace Tests\Feature\Repositories;

use App\Models\ContenidoNivel;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\TarjetaNivel;
use App\Repositories\JuegoRepository;
use Database\Seeders\ContenidoGrados23Seeder;
use Database\Seeders\MemoriaJuegoSeeder;

/**
 * Contenido de 2.° y 3.er grado: Suma de Frutas (8 niveles por grado) y
 * Encuentra el par (10 niveles copiados de 1.°). Los sembradores base solo
 * cargan 1.° grado; ContenidoGrados23Seeder completa los otros dos.
 */
class ContenidoGrados23Test extends RepositorioTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MemoriaJuegoSeeder::class);
        $this->seed(ContenidoGrados23Seeder::class);
    }

    private function frutas(): Juego
    {
        return Juego::where('nombre_es', 'Suma de Frutas')->firstOrFail();
    }

    private function niveles(Juego $juego, int $grado)
    {
        return Nivel::where('juego_id', $juego->id)->where('grado', $grado)->orderBy('numero_nivel')->get();
    }

    public function test_suma_de_frutas_tiene_8_niveles_de_12_ejercicios_en_2_y_3()
    {
        foreach ([2, 3] as $grado) {
            $niveles = $this->niveles($this->frutas(), $grado);
            $this->assertCount(8, $niveles, "Grado {$grado}: 8 niveles.");
            $this->assertEquals(range(1, 8), $niveles->pluck('numero_nivel')->all());

            foreach ($niveles as $n) {
                $this->assertSame(12, ContenidoNivel::where('nivel_id', $n->id)->count(), "Grado {$grado}, nivel {$n->numero_nivel}.");
                $this->assertSame(3, (int) $n->min_aciertos_pasar);
                $this->assertSame(5, (int) $n->total_ejercicios);
            }
        }
    }

    public function test_solo_el_nivel_1_de_cada_grado_nace_abierto()
    {
        foreach ([2, 3] as $grado) {
            $abiertos = $this->niveles($this->frutas(), $grado)->where('desbloqueado_por_defecto', true);
            $this->assertCount(1, $abiertos);
            $this->assertSame(1, (int) $abiertos->first()->numero_nivel);
        }
    }

    public function test_las_cuentas_son_correctas_y_respetan_el_tope_de_cada_grado()
    {
        $tope = [2 => 100, 3 => 1000];

        foreach ([2, 3] as $grado) {
            foreach ($this->niveles($this->frutas(), $grado) as $n) {
                foreach (ContenidoNivel::where('nivel_id', $n->id)->get() as $e) {
                    $esperado = $e->operador === '+' ? (int) $e->valor_a + (int) $e->valor_b : (int) $e->valor_a - (int) $e->valor_b;
                    $this->assertSame((string) $esperado, $e->respuesta_correcta, "{$e->valor_a} {$e->operador} {$e->valor_b}");
                    $this->assertGreaterThanOrEqual(0, $esperado, 'Nunca hay resultados negativos.');
                    $this->assertLessThanOrEqual($tope[$grado], max((int) $e->valor_a, (int) $e->valor_b, $esperado));

                    $opciones = array_map('intval', (array) $e->opciones_json);
                    $this->assertCount(4, $opciones);
                    $this->assertCount(4, array_unique($opciones), 'Sin opciones repetidas.');
                    $this->assertContains($esperado, $opciones, 'La respuesta correcta está entre las opciones.');
                    $this->assertGreaterThanOrEqual(0, min($opciones));
                }
            }
        }
    }

    public function test_las_sumas_con_llevada_y_las_restas_con_prestamo_cumplen_su_condicion()
    {
        // grado => [niveles de suma con llevada, suma sin llevada, resta con préstamo, resta sin préstamo]
        $plan = [
            2 => ['llevada' => [4], 'sin_llevada' => [1, 2, 3], 'prestamo' => [7], 'sin_prestamo' => [5, 6]],
            3 => ['llevada' => [1, 4], 'sin_llevada' => [2, 3], 'prestamo' => [7], 'sin_prestamo' => [5, 6]],
        ];

        foreach ($plan as $grado => $niveles) {
            foreach ($this->niveles($this->frutas(), $grado) as $n) {
                foreach (ContenidoNivel::where('nivel_id', $n->id)->get() as $e) {
                    $k = $n->numero_nivel;
                    $msg = "Grado {$grado}, nivel {$k}: {$e->valor_a} {$e->operador} {$e->valor_b}";
                    if (in_array($k, $niveles['llevada'], true)) {
                        $this->assertTrue(ContenidoGrados23Seeder::hayLlevada($e->valor_a, $e->valor_b), $msg);
                    } elseif (in_array($k, $niveles['sin_llevada'], true) && $e->operador === '+') {
                        $this->assertFalse(ContenidoGrados23Seeder::hayLlevada($e->valor_a, $e->valor_b), $msg);
                    } elseif (in_array($k, $niveles['prestamo'], true)) {
                        $this->assertTrue(ContenidoGrados23Seeder::hayPrestamo($e->valor_a, $e->valor_b), $msg);
                    } elseif (in_array($k, $niveles['sin_prestamo'], true)) {
                        $this->assertFalse(ContenidoGrados23Seeder::hayPrestamo($e->valor_a, $e->valor_b), $msg);
                    } elseif ($k === 8) {
                        $con = $e->operador === '+'
                            ? ContenidoGrados23Seeder::hayLlevada($e->valor_a, $e->valor_b)
                            : ContenidoGrados23Seeder::hayPrestamo($e->valor_a, $e->valor_b);
                        $this->assertTrue($con, $msg . ' (la mezcla es siempre con llevada o préstamo)');
                    }
                }
            }
        }
    }

    public function test_encuentra_el_par_se_copia_a_2_y_3_con_menos_tiempo()
    {
        $juego = Juego::where('nombre_es', 'Encuentra el par')->firstOrFail();
        $base  = $this->niveles($juego, 1);
        $this->assertCount(10, $base);

        foreach ([2 => 0.9, 3 => 0.8] as $grado => $factor) {
            $copia = $this->niveles($juego, $grado);
            $this->assertCount(10, $copia, "Grado {$grado}: 10 niveles.");

            foreach ($base as $i => $origen) {
                $nuevo = $copia[$i];
                $this->assertSame((int) $origen->numero_nivel, (int) $nuevo->numero_nivel);
                $this->assertSame((int) $origen->total_ejercicios, (int) $nuevo->total_ejercicios);
                $this->assertSame((bool) $origen->desbloqueado_por_defecto, (bool) $nuevo->desbloqueado_por_defecto);
                $this->assertLessThan((int) $origen->tiempo_limite_seg, (int) $nuevo->tiempo_limite_seg);
                $this->assertSame(0, (int) $nuevo->tiempo_limite_seg % 5, 'Tiempo en múltiplos de 5 s.');
                $this->assertEqualsWithDelta($origen->tiempo_limite_seg * $factor, $nuevo->tiempo_limite_seg, 2.5);

                $this->assertSame(
                    TarjetaNivel::where('nivel_id', $origen->id)->count(),
                    TarjetaNivel::where('nivel_id', $nuevo->id)->count(),
                    "Grado {$grado}, nivel {$nuevo->numero_nivel}: mismo pool de tarjetas."
                );
                $this->assertGreaterThanOrEqual((int) $nuevo->total_ejercicios, TarjetaNivel::where('nivel_id', $nuevo->id)->count());
            }
        }
    }

    public function test_el_sembrador_es_idempotente()
    {
        $antes = [Nivel::count(), ContenidoNivel::count(), TarjetaNivel::count()];

        $this->seed(ContenidoGrados23Seeder::class);
        $this->seed(ContenidoGrados23Seeder::class);

        $this->assertSame($antes, [Nivel::count(), ContenidoNivel::count(), TarjetaNivel::count()]);
    }

    public function test_los_grados_2_y_3_ya_tienen_contenido_y_el_4_no()
    {
        $repo = app(JuegoRepository::class);

        $this->assertTrue($repo->hayNivelesDelGrado(2));
        $this->assertTrue($repo->hayNivelesDelGrado(3));
        $this->assertFalse($repo->hayNivelesDelGrado(4));

        foreach ([2, 3] as $grado) {
            $nombres = $repo->activosConNivelesDelGrado($grado)
                ->filter(fn ($j) => $j->niveles->isNotEmpty())->pluck('nombre_es')->all();
            $this->assertContains('Suma de Frutas', $nombres);
            $this->assertContains('Encuentra el par', $nombres);
        }
    }

    public function test_sin_los_juegos_cargados_el_sembrador_no_hace_nada()
    {
        Nivel::whereIn('grado', [2, 3])->delete();
        Juego::query()->update(['nombre_es' => 'Otro juego']);

        (new ContenidoGrados23Seeder())->run();

        $this->assertSame(0, Nivel::whereIn('grado', [2, 3])->count());
    }
}
