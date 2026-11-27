<?php

namespace Database\Seeders;

use App\Models\Juego;
use App\Models\Nivel;
use App\Models\TipoMecanica;
use App\Support\Numeros\GeneradorItems;
use App\Support\Numeros\TextosNumeros;
use Illuminate\Database\Seeder;

/**
 * SEEDER: MatiNumerosSeeder — «Mati y los números / Mati ha papapykuéra»
 * =========================================================================
 * Quinto juego de MatiGuarã. NO es un juego de puntos: es una herramienta de
 * tamizaje que avisa a la docente que preste más atención a un niño. No emite
 * diagnóstico alguno.
 *
 *   php artisan db:seed --class=MatiNumerosSeeder --force
 *
 * Es IDEMPOTENTE: se puede ejecutar más de una vez sin duplicar nada.
 *   · tipo de mecánica 'numeros' + el juego
 *   · 3 niveles por grado (1.º, 2.º y 3.º) = los tres «viajes» de un ciclo.
 *     No llevan banco de ejercicios en la base: las preguntas las genera
 *     App\Support\Numeros\GeneradorItems.
 *
 * Guaraní revisado 2026-11-19 (App\Support\Numeros\TextosNumeros). Si el juego ya existía,
 * lo actualiza la migración 2026_11_19_000006.
 * =========================================================================
 */
class MatiNumerosSeeder extends Seeder
{
    public const NOMBRE_ES = 'Mati y los números';

    public function run(): void
    {
        $mecanica = TipoMecanica::firstOrCreate(
            ['tipo' => 'numeros', 'nombre' => 'Mati y los números (tamizaje)'],
            ['descripcion' => 'Preguntas cortas sobre números y cantidades que avisan a la docente si un niño necesita más atención.', 'activo' => true]
        );

        $juego = Juego::firstOrCreate(
            ['nombre_es' => self::NOMBRE_ES],
            [
                'tipo_mecanica_id'    => $mecanica->id,
                'nombre_gn'           => TextosNumeros::NOMBRE_GN,
                'instrucciones_es'    => 'Viajá con Mati por la selva. En cada parada hay números y cantidades: tocá la respuesta que te parezca. ¡No hay puntos ni premios, solo jugar!',
                'instrucciones_gn'    => TextosNumeros::INSTRUCCIONES_GN,
                'activo'              => true,
                'dias_reinicio_racha' => null,
            ]
        );

        foreach ([1, 2, 3] as $grado) {
            foreach ([1, 2, 3] as $viaje) {
                Nivel::updateOrCreate(
                    ['juego_id' => $juego->id, 'grado' => $grado, 'numero_nivel' => $viaje],
                    [
                        'dificultad'               => 2,
                        'min_aciertos_pasar'       => 99,    // no se «supera»: es un tamizaje
                        'total_ejercicios'         => GeneradorItems::totalDelViaje($viaje),
                        'desbloqueado_por_defecto' => $viaje === 1,
                        'tiempo_mostrar_ms'        => null,
                        'tiempo_limite_seg'        => null,
                    ]
                );
            }
        }

        if ($this->command) {
            $this->command->info('  ✅ Mati y los números: juego y 9 niveles (3 viajes por grado)');
        }
    }
}
