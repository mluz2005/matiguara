<?php

namespace Database\Seeders;

use App\Models\CatalogoLogro;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\TipoMecanica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SEEDER: CaminoEscuelaSeeder — «El camino a la escuela / Tape mbo'ehaópe»
 * =========================================================================
 * Cuarto juego de MatiGuarã: un laberinto en el que el niño sale de su casa y
 * llega a la escuela resolviendo sumas y restas que le abren el paso.
 *
 *   php artisan db:seed --class=CaminoEscuelaSeeder
 *
 * Es IDEMPOTENTE: se puede ejecutar más de una vez sin duplicar nada.
 *   · tipo de mecánica 'laberinto' + el juego
 *   · 8 niveles × 3 grados (1.° hasta 20, 2.° hasta 100, 3.° hasta 1000)
 *   · el banco de ejercicios de cada nivel (database/seeders/data/laberinto_ejercicios.json)
 *     — solo se carga si el nivel todavía no tiene ejercicios, para no tocar los
 *     que ya tienen respuestas registradas
 *   · 4 logros nuevos
 *
 * BANCO DE EJERCICIOS: cada nivel guarda 3 variantes × (obstáculos × 2) ejercicios.
 * `orden` = variante·(2·obstáculos) + obstáculo·2 + ronda + 1, donde ronda 0 es el
 * ejercicio del obstáculo y ronda 1 el "parecido" que vuelve si el niño necesitó ver
 * la solución. LaberintoService decodifica este orden. Cada partida usa una variante
 * por obstáculo, según su número de sesión, así los laberintos no se repiten igual.
 *
 * ⚠️ El guaraní (nombres, enunciados, mensajes) es BORRADOR y debe ser validado por
 * una persona hablante antes de usarse con niños.
 * =========================================================================
 */
class CaminoEscuelaSeeder extends Seeder
{
    /** Obstáculos por nivel: 4 (niveles 1-2), 5 (3-5), 6 (6-8). */
    public static function obstaculosDeNivel(int $numero): int
    {
        return $numero <= 2 ? 4 : ($numero <= 5 ? 5 : 6);
    }

    public function run(): void
    {
        $mecanica = TipoMecanica::firstOrCreate(
            ['tipo' => 'laberinto', 'nombre' => 'Laberinto de problemas'],
            ['descripcion' => 'Sumas y restas en problemas cortos que abren el paso en un laberinto.', 'activo' => true]
        );

        $juego = Juego::firstOrCreate(
            ['nombre_es' => 'El camino a la escuela'],
            [
                'tipo_mecanica_id'    => $mecanica->id,
                'nombre_gn'           => "Tape mbo'ehaópe",
                'instrucciones_es'    => 'Llevá a tu personaje de la casa a la escuela. Cuando un obstáculo te cierre el paso, resolvé el problema para abrirlo. ¡Juntá chipas en los callejones!',
                'instrucciones_gn'    => "Eraha nde personaje óga-gui mbo'ehaópe. Oĩ ramo mba'e ojokóva, eipuru papapy ojepe'a hag̃ua.",
                'activo'              => true,
                'dias_reinicio_racha' => null,
            ]
        );

        $dificultades = [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 3, 6 => 4, 7 => 4, 8 => 5];
        $niveles = [];   // "grado|numero" => Nivel

        foreach ([1, 2, 3] as $grado) {
            foreach (range(1, 8) as $numero) {
                $niveles["$grado|$numero"] = Nivel::updateOrCreate(
                    ['juego_id' => $juego->id, 'grado' => $grado, 'numero_nivel' => $numero],
                    [
                        'dificultad'               => $dificultades[$numero],
                        // Se supera por la regla del juego (máx. 1 obstáculo con solución mostrada),
                        // que decide LaberintoService; 99 evita que SesionService lo marque por cantidad de aciertos.
                        'min_aciertos_pasar'       => 99,
                        'total_ejercicios'         => self::obstaculosDeNivel($numero),   // = cantidad de obstáculos
                        'desbloqueado_por_defecto' => $numero === 1,
                        'tiempo_mostrar_ms'        => null,
                        'tiempo_limite_seg'        => null,
                    ]
                );
            }
        }

        $this->cargarEjercicios($niveles);
        $this->cargarLogros();

        if ($this->command) {
            $this->command->info('  ✅ El camino a la escuela: 24 niveles y su banco de ejercicios');
        }
    }

    private function cargarEjercicios(array $niveles): void
    {
        $ruta = __DIR__ . '/data/laberinto_ejercicios.json';
        $filas = json_decode((string) file_get_contents($ruta), true);
        if (!is_array($filas)) {
            throw new \RuntimeException("No se pudo leer el banco de ejercicios: $ruta");
        }

        $porNivel = [];
        foreach ($filas as $f) {
            $porNivel[$f['grado'] . '|' . $f['nivel']][] = $f;
        }

        foreach ($niveles as $clave => $nivel) {
            if (DB::table('contenido_nivel')->where('nivel_id', $nivel->id)->exists()) {
                continue;   // ya tiene ejercicios: no se toca
            }

            $lote = [];
            foreach ($porNivel[$clave] ?? [] as $f) {
                $lote[] = [
                    'nivel_id'           => $nivel->id,
                    'tipo_operacion'     => $f['tipo_operacion'],
                    'enunciado_es'       => $f['enunciado_es'],
                    'enunciado_gn'       => $f['enunciado_gn'],
                    'valor_a'            => $f['valor_a'],
                    'operador'           => $f['operador'],
                    'valor_b'            => $f['valor_b'],
                    'operador_2'         => $f['operador_2'],
                    'valor_c'            => $f['valor_c'],
                    'respuesta_correcta' => (string) $f['respuesta_correcta'],
                    'opciones_json'      => json_encode($f['opciones_json']),
                    'orden'              => $f['orden'],
                ];
            }
            foreach (array_chunk($lote, 100) as $trozo) {
                DB::table('contenido_nivel')->insert($trozo);
            }
        }
    }

    private function cargarLogros(): void
    {
        $logros = [
            [
                'nombre_es' => 'Primer camino a la escuela', 'nombre_gn' => "Peteĩha tape mbo'ehaópe",   // gn: borrador
                'emoji' => '🏫', 'tipo' => 'especial', 'condicion_tipo' => 'laberinto_llegada', 'condicion_valor' => 1,
                'rareza' => 'comun', 'puntos_logro' => 20,
                'descripcion_es' => 'Llegá a la escuela por primera vez en El camino a la escuela.',
            ],
            [
                'nombre_es' => 'Camino perfecto', 'nombre_gn' => 'Tape iporãva',                           // gn: borrador
                'emoji' => '🌟', 'tipo' => 'especial', 'condicion_tipo' => 'laberinto_perfecto', 'condicion_valor' => 1,
                'rareza' => 'raro', 'puntos_logro' => 50,
                'descripcion_es' => 'Llegá a la escuela con las 3 estrellas: sin pistas, sin flecha y sin ver soluciones.',
            ],
            [
                'nombre_es' => 'Chipero', 'nombre_gn' => 'Chipero',                                         // gn: borrador
                'emoji' => '🥯', 'tipo' => 'especial', 'condicion_tipo' => 'laberinto_chipas', 'condicion_valor' => 1,
                'rareza' => 'raro', 'puntos_logro' => 50,
                'descripcion_es' => 'Juntá todas las chipas de los callejones en un camino.',
            ],
            [
                'nombre_es' => 'Maestro del camino', 'nombre_gn' => "Tape mbo'ehára",                       // gn: borrador
                'emoji' => '🏆', 'tipo' => 'nivel', 'condicion_tipo' => 'laberinto_ultimo_nivel', 'condicion_valor' => 1,
                'rareza' => 'epico', 'puntos_logro' => 150,
                'descripcion_es' => 'Superá el último nivel de El camino a la escuela.',
            ],
        ];

        foreach ($logros as $l) {
            CatalogoLogro::updateOrCreate(['nombre_es' => $l['nombre_es']], $l + ['activo' => true]);
        }
    }
}
