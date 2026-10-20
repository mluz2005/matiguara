<?php

namespace Database\Seeders;

use App\Models\CatalogoLogro;
use Illuminate\Database\Seeder;

/**
 * SEEDER: CatalogoLogrosSeeder
 * Catálogo inicial de logros/insignias del sistema (RF-03).
 */
class CatalogoLogrosSeeder extends Seeder
{
    public function run(): void
    {
        $logros = [
            // ── RACHAS DE ACIERTOS ────────────────────────────────────
            ['nombre_es' => 'Primera Racha',    'nombre_gn' => 'Peteĩha Racha',
             'tipo' => 'racha', 'condicion_tipo' => 'aciertos_consecutivos', 'condicion_valor' => 3,
             'rareza' => 'comun',     'puntos_logro' => 10],

          ['nombre_es' => 'Imparable',         'nombre_gn' => 'Oky\'o-\'ymante',
             'tipo' => 'racha', 'condicion_tipo' => 'aciertos_consecutivos', 'condicion_valor' => 10,
             'rareza' => 'raro',      'puntos_logro' => 50],

            ['nombre_es' => 'Maestro del Cálculo','nombre_gn' => 'Aranduka Ñemonde',
             'tipo' => 'racha', 'condicion_tipo' => 'aciertos_consecutivos', 'condicion_valor' => 20,
             'rareza' => 'epico',     'puntos_logro' => 150],

            // ── PARTIDAS JUGADAS ──────────────────────────────────────
            ['nombre_es' => 'Primer Paso',       'nombre_gn' => 'Peteĩha Guata',
             'tipo' => 'partidas', 'condicion_tipo' => 'partidas_jugadas', 'condicion_valor' => 1,
             'rareza' => 'comun',     'puntos_logro' => 5],

            ['nombre_es' => 'Constante',          'nombre_gn' => 'Okyje\'ỹha',
             'tipo' => 'partidas', 'condicion_tipo' => 'partidas_jugadas', 'condicion_valor' => 10,
             'rareza' => 'raro',      'puntos_logro' => 30],

            // ── NIVELES SUPERADOS ─────────────────────────────────────
            ['nombre_es' => 'Nivel Conquistado',  'nombre_gn' => 'Nivel Ojapóva',
             'tipo' => 'nivel', 'condicion_tipo' => 'nivel_superado', 'condicion_valor' => 1,
             'rareza' => 'comun',     'puntos_logro' => 20],

            ['nombre_es' => 'Explorador',          'nombre_gn' => 'Oheka-haguã',
             'tipo' => 'nivel', 'condicion_tipo' => 'nivel_superado', 'condicion_valor' => 5,
             'rareza' => 'raro',      'puntos_logro' => 75],

            // ── DÍAS CONSECUTIVOS ─────────────────────────────────────
            ['nombre_es' => 'Semana Completa',    'nombre_gn' => 'Arapokõindy Opáva',
             'tipo' => 'racha', 'condicion_tipo' => 'dias_consecutivos', 'condicion_valor' => 7,
             'rareza' => 'epico',     'puntos_logro' => 100],

            ['nombre_es' => 'Leyenda',             'nombre_gn' => 'Ñemombe\'u',
             'tipo' => 'especial', 'condicion_tipo' => 'dias_consecutivos', 'condicion_valor' => 30,
             'rareza' => 'legendario','puntos_logro' => 500],
        ];

        // Emoji y descripción de cada medalla (2026-10-12). Se agregan acá para que
        // una instalación nueva (migrate:fresh --seed) también los tenga.
        // (La medalla "Flash Master" la crea la migración 2026_10_12_000006.)
        $extra = [
            'Primera Racha'       => ['🔥', 'Acertá 3 ejercicios seguidos.'],
            'Imparable'           => ['🚀', 'Acertá 10 ejercicios seguidos.'],
            'Maestro del Cálculo' => ['🔢', 'Acertá 20 ejercicios seguidos.'],
            'Primer Paso'         => ['👣', 'Jugá tu primera partida.'],
            'Constante'           => ['🏃', 'Jugá 10 partidas.'],
            'Nivel Conquistado'   => ['⭐', 'Superá tu primer nivel.'],
            'Explorador'          => ['🗺️', 'Superá 5 niveles distintos.'],
            'Semana Completa'     => ['📅', 'Jugá 7 días seguidos.'],
            'Leyenda'             => ['👑', 'Jugá 30 días seguidos.'],
        ];

        foreach ($logros as $l) {
            [$emoji, $descripcion] = $extra[$l['nombre_es']] ?? [null, null];
            CatalogoLogro::updateOrCreate(
                ['nombre_es' => $l['nombre_es']],
                array_merge($l, [
                    'emoji'          => $emoji,
                    'descripcion_es' => $descripcion,
                    'icono_url'      => null,
                    'activo'         => true,
                ])
            );
        }
        $this->command->info('  ✅ Logros en catálogo: ' . count($logros));
    }
}
