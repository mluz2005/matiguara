<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (solo DATOS): medalla "Flash Master" y descripciones — 2026-10-12
 * ============================================================================
 * 1) Crea la medalla "Flash Master" en `catalogo_logros`. Se otorga al SUPERAR
 *    el último nivel (el 10) de Flash Matemático, en cualquier grado.
 *    (Antes existía solo en un archivo de semillas que no se ejecuta, así que
 *    ningún niño podía ganarla; y decía "nivel 3".)
 *    La regla "último nivel de Flash" la evalúa LogroService con
 *    condicion_tipo = 'flash_ultimo_nivel' (condicion_valor no se usa).
 *
 * 2) Completa `descripcion_es` de las 9 medallas del catálogo, que estaba
 *    vacía, para que "Próximas medallas" le diga al niño cómo ganarlas.
 *    Solo completa las que están vacías: no pisa descripciones ya escritas.
 *
 * No cambia la estructura de la base de datos. Se puede ejecutar más de una
 * vez sin duplicar nada. down() quita la medalla Flash Master (y a quienes la
 * hubieran ganado).
 * ============================================================================
 */
return new class extends Migration
{
    private const FLASH = 'Flash Master';

    /** nombre_es => descripción (solo para las que estén vacías) */
    private array $descripciones = [
        'Primera Racha'       => 'Acertá 3 ejercicios seguidos.',
        'Imparable'           => 'Acertá 10 ejercicios seguidos.',
        'Maestro del Cálculo' => 'Acertá 20 ejercicios seguidos.',
        'Primer Paso'         => 'Jugá tu primera partida.',
        'Constante'           => 'Jugá 10 partidas.',
        'Nivel Conquistado'   => 'Superá tu primer nivel.',
        'Explorador'          => 'Superá 5 niveles distintos.',
        'Semana Completa'     => 'Jugá 7 días seguidos.',
        'Leyenda'             => 'Jugá 30 días seguidos.',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        if (!DB::table('catalogo_logros')->where('nombre_es', self::FLASH)->exists()) {
            DB::table('catalogo_logros')->insert([
                'nombre_es'      => self::FLASH,
                'nombre_gn'      => 'Flash Ojehupyty', // texto de las semillas originales: validar con una persona hablante
                'descripcion_es' => 'Superá el último nivel de Flash Matemático. ¡Tu memoria es increíble!',
                'tipo'           => 'nivel',
                'condicion_tipo' => 'flash_ultimo_nivel',
                'condicion_valor'=> 1,
                'puntos_logro'   => 150,
                'rareza'         => 'epico',
                'icono_url'      => null,
                'activo'         => true,
            ]);
        }

        foreach ($this->descripciones as $nombre => $texto) {
            DB::table('catalogo_logros')
                ->where('nombre_es', $nombre)
                ->where(function ($q) {
                    $q->whereNull('descripcion_es')->orWhere('descripcion_es', '');
                })
                ->update(['descripcion_es' => $texto]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        $id = DB::table('catalogo_logros')->where('nombre_es', self::FLASH)->value('id');
        if ($id) {
            try {
                DB::table('logros_obtenidos')->where('logro_id', $id)->delete();
                DB::table('catalogo_logros')->where('id', $id)->delete();
            } catch (\Throwable $e) {
                // Si no se puede borrar, se desactiva para que no se otorgue más.
                DB::table('catalogo_logros')->where('id', $id)->update(['activo' => false]);
            }
        }
        // Las descripciones completadas no se revierten (no hacen daño).
    }
};
