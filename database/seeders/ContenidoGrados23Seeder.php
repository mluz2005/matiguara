<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SEEDER: ContenidoGrados23Seeder — contenido de 2.° y 3.er grado
 * =========================================================================
 * Completa la cobertura de los tres grados para los juegos que antes solo
 * tenían contenido de 1.° grado (hasta ahora Flash Matemático era el único
 * con los tres grados):
 *
 *  1. SUMA DE FRUTAS — 8 niveles por grado, 12 ejercicios por nivel
 *       2.° grado: números hasta 100   3.er grado: números hasta 1000
 *       Escalera: sumas sin llevada → sumas con llevada → restas sin
 *       préstamo → restas con préstamo → mezcla.
 *       (Con más de 10 elementos por grupo el juego no dibuja frutas y
 *       muestra solo los números; ver App\Support\FrutasJuego.)
 *
 *  2. ENCUENTRA EL PAR — los 10 niveles de 1.° se copian a 2.° y 3.er grado
 *       (mismos niveles y mismo pool de tarjetas) con menos tiempo límite:
 *       90 % del tiempo en 2.° y 80 % en 3.°, redondeado a múltiplos de 5 s.
 *
 * ES IDEMPOTENTE: se puede ejecutar varias veces (a mano, desde la migración
 * 2026_10_21_000001 o junto con otros sembradores) sin duplicar niveles ni
 * ejercicios, y respeta lo que ya exista. Si un juego no está cargado, lo
 * omite sin error.
 *
 * Instalación nueva:
 *   php artisan migrate --seed
 *   php artisan db:seed --class=MemoriaJuegoSeeder          (si se usa ese juego)
 *   php artisan db:seed --class=ContenidoGrados23Seeder     (este, al final)
 *
 * GUARANÍ: los enunciados en guaraní son el texto de base con cifras (igual
 * que en el resto del juego) y deben ser validados por una persona hablante.
 * =========================================================================
 */
class ContenidoGrados23Seeder extends Seeder
{
    public const GRADOS = [2, 3];

    /** Porcentaje del tiempo límite de 1.° que conserva Encuentra el par en cada grado. */
    private const FACTOR_TIEMPO_MEMORIA = [2 => 0.9, 3 => 0.8];

    public function run(): void
    {
        [$n1, $e1] = $this->sumaDeFrutas();
        [$n2, $t2] = $this->encuentraElPar();

        $this->aviso("  ✅ Suma de Frutas (2.° y 3.°): {$n1} niveles y {$e1} ejercicios nuevos");
        $this->aviso("  ✅ Encuentra el par (2.° y 3.°): {$n2} niveles y {$t2} tarjetas nuevas");
    }

    /** @return array{0:int,1:int} [niveles creados, ejercicios creados] */
    public function sumaDeFrutas(): array
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Suma de Frutas')->value('id');
        if (!$juegoId) {
            return [0, 0];
        }

        $datos = require __DIR__ . '/data/suma_frutas_grados_2_3.php';
        $niveles = 0;
        $ejercicios = 0;

        foreach ($datos as $grado => $nivelesDelGrado) {
            foreach ($nivelesDelGrado as $numero => $def) {
                $nivelId = DB::table('niveles')
                    ->where('juego_id', $juegoId)->where('grado', $grado)->where('numero_nivel', $numero)
                    ->value('id');

                if (!$nivelId) {
                    $nivelId = DB::table('niveles')->insertGetId([
                        'juego_id'                 => $juegoId,
                        'grado'                    => $grado,
                        'numero_nivel'             => $numero,
                        'dificultad'               => $def['dificultad'],
                        'min_aciertos_pasar'       => 3,
                        'total_ejercicios'         => 5,
                        'desbloqueado_por_defecto' => $numero === 1,
                        'tiempo_mostrar_ms'        => null,
                    ]);
                    $niveles++;
                }

                $existentes = [];
                $orden = 0;
                foreach (DB::table('contenido_nivel')->where('nivel_id', $nivelId)->get() as $f) {
                    $existentes[$f->valor_a . $f->operador . $f->valor_b] = true;
                    $orden = max($orden, (int) $f->orden);
                }

                foreach ($def['ejercicios'] as [$a, $op, $b, $respuesta, $opciones]) {
                    if (isset($existentes[$a . $op . $b])) {
                        continue;
                    }
                    $palabra  = $op === '+' ? 'más' : 'menos';
                    $esSuma   = $op === '+';
                    $complejo = $esSuma ? $this->hayLlevada($a, $b) : $this->hayPrestamo($a, $b);
                    $tipo     = ($esSuma ? 'suma_' : 'resta_') . ($complejo ? 'con_llevada' : 'simple');

                    DB::table('contenido_nivel')->insert([
                        'nivel_id'           => $nivelId,
                        'tipo_operacion'     => $tipo,
                        'enunciado_es'       => "¿Cuánto es {$a} {$palabra} {$b}?",
                        'enunciado_gn'       => "¿Mboy'e pa {$a} {$palabra} {$b}?",
                        'valor_a'            => $a,
                        'operador'           => $op,
                        'valor_b'            => $b,
                        'respuesta_correcta' => (string) $respuesta,
                        'opciones_json'      => json_encode($opciones),
                        'orden'              => min(255, ++$orden),
                        'audio_es'           => null,
                        'audio_gn'           => null,
                    ]);
                    $ejercicios++;
                }
            }
        }

        return [$niveles, $ejercicios];
    }

    /** @return array{0:int,1:int} [niveles creados, tarjetas creadas] */
    public function encuentraElPar(): array
    {
        $juegoId = DB::table('juegos')->where('nombre_es', 'Encuentra el par')->value('id');
        if (!$juegoId) {
            return [0, 0];
        }

        $base = DB::table('niveles')->where('juego_id', $juegoId)->where('grado', 1)
            ->orderBy('numero_nivel')->get();

        $niveles = 0;
        $tarjetas = 0;

        foreach (self::GRADOS as $grado) {
            foreach ($base as $origen) {
                $existe = DB::table('niveles')
                    ->where('juego_id', $juegoId)->where('grado', $grado)
                    ->where('numero_nivel', $origen->numero_nivel)->exists();
                if ($existe) {
                    continue;
                }

                DB::transaction(function () use ($origen, $juegoId, $grado, &$niveles, &$tarjetas) {
                    $tiempo = $origen->tiempo_limite_seg === null ? null
                        : (int) (round($origen->tiempo_limite_seg * self::FACTOR_TIEMPO_MEMORIA[$grado] / 5) * 5);

                    $nuevoId = DB::table('niveles')->insertGetId([
                        'juego_id'                 => $juegoId,
                        'grado'                    => $grado,
                        'numero_nivel'             => $origen->numero_nivel,
                        'dificultad'               => $origen->dificultad,
                        'min_aciertos_pasar'       => $origen->min_aciertos_pasar,
                        'total_ejercicios'         => $origen->total_ejercicios,
                        'desbloqueado_por_defecto' => $origen->desbloqueado_por_defecto,
                        'tiempo_mostrar_ms'        => $origen->tiempo_mostrar_ms,
                        'tiempo_limite_seg'        => $tiempo,
                    ]);
                    $niveles++;

                    foreach (DB::table('tarjetas_nivel')->where('nivel_id', $origen->id)->orderBy('id')->get() as $t) {
                        $fila = (array) $t;
                        unset($fila['id']);
                        $fila['nivel_id']   = $nuevoId;
                        $fila['created_at'] = now();
                        $fila['updated_at'] = now();
                        DB::table('tarjetas_nivel')->insert($fila);
                        $tarjetas++;
                    }
                });
            }
        }

        return [$niveles, $tarjetas];
    }

    /** ¿Alguna columna (unidades, decenas, centenas…) de a + b suma 10 o más? */
    public static function hayLlevada(int $a, int $b): bool
    {
        while ($a > 0 || $b > 0) {
            if (($a % 10) + ($b % 10) >= 10) {
                return true;
            }
            $a = intdiv($a, 10);
            $b = intdiv($b, 10);
        }
        return false;
    }

    /** ¿Alguna columna de a - b necesita «pedir prestado»? */
    public static function hayPrestamo(int $a, int $b): bool
    {
        while ($a > 0 || $b > 0) {
            if (($a % 10) < ($b % 10)) {
                return true;
            }
            $a = intdiv($a, 10);
            $b = intdiv($b, 10);
        }
        return false;
    }

    private function aviso(string $texto): void
    {
        if ($this->command !== null) {
            $this->command->info($texto);
        }
    }
}
