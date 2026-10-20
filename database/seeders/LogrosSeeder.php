<?php

namespace Database\Seeders;

use App\Models\CatalogoLogro;
use Illuminate\Database\Seeder;

/**
 * SEEDER: LogrosSeeder — Catálogo de logros y medallas
 * =========================================================================
 * Crea el catálogo completo de logros del sistema.
 * Cada logro tiene:
 *   - código único para identificarlo en el código
 *   - nombre en castellano y guaraní (RN-06)
 *   - descripción pedagógica
 *   - rareza: comun | raro | epico (afecta el diseño visual de la medalla)
 *   - emoji: icono visual para niños de 6-9 años
 *
 * Referencia: CU-07 (Otorgar logro automático), RF-03 (gamificación)
 * =========================================================================
 */
class LogrosSeeder extends Seeder
{
    public function run(): void
    {
        $logros = [
            // ── Logros de inicio ──────────────────────────────────────────
            [
                'codigo'      => 'primer_paso',
                'nombre_es'   => 'Primer Paso',
                'nombre_gn'   => 'Peteĩ Mba\'apopy',
                'descripcion' => 'Completaste tu primera sesión de juego. ¡Bienvenido a MatiGuará!',
                'rareza'      => 'comun',
                'emoji'       => '👣',
                'activo'      => true,
            ],
            [
                'codigo'      => 'nivel_superado',
                'nombre_es'   => 'Nivel Superado',
                'nombre_gn'   => 'Grado Ojehupyty',
                'descripcion' => 'Superaste un nivel por primera vez. ¡Seguís creciendo!',
                'rareza'      => 'comun',
                'emoji'       => '⭐',
                'activo'      => true,
            ],

            // ── Logros de habilidad ───────────────────────────────────────
            [
                'codigo'      => 'perfecto',
                'nombre_es'   => '¡Perfecto!',
                'nombre_gn'   => '¡Iporãve!',
                'descripcion' => 'Respondiste todos los ejercicios de una sesión sin ningún error.',
                'rareza'      => 'raro',
                'emoji'       => '💯',
                'activo'      => true,
            ],
            [
                'codigo'      => 'veloz',
                'nombre_es'   => 'Relámpago',
                'nombre_gn'   => 'Tujambyry',
                'descripcion' => 'Respondiste correctamente un ejercicio en menos de 3 segundos.',
                'rareza'      => 'raro',
                'emoji'       => '⚡',
                'activo'      => true,
            ],
            [
                'codigo'      => 'flash_master',
                'nombre_es'   => 'Flash Master',
                'nombre_gn'   => 'Flash Ojehupyty',
                'descripcion' => 'Superaste el nivel 3 de Flash Matemático. ¡Tu memoria es increíble!',
                'rareza'      => 'epico',
                'emoji'       => '🧠',
                'activo'      => true,
            ],

            // ── Logros de constancia ──────────────────────────────────────
            [
                'codigo'      => 'primera_racha',
                'nombre_es'   => 'Primera Racha',
                'nombre_gn'   => 'Peteĩ Ára Mboyve',
                'descripcion' => 'Jugaste 3 días seguidos. ¡La práctica constante es el secreto!',
                'rareza'      => 'comun',
                'emoji'       => '🔥',
                'activo'      => true,
            ],
            [
                'codigo'      => 'perseverante',
                'nombre_es'   => 'Perseverante',
                'nombre_gn'   => 'Ojehupyty Opäite',
                'descripcion' => 'Completaste 10 sesiones de juego. ¡Sos un campeón de la práctica!',
                'rareza'      => 'raro',
                'emoji'       => '🏃',
                'activo'      => true,
            ],
            [
                'codigo'      => 'racha_semana',
                'nombre_es'   => 'Semana Completa',
                'nombre_gn'   => 'Arapokõindy Ojehupyty',
                'descripcion' => 'Jugaste 7 días seguidos. ¡Una semana entera sin parar!',
                'rareza'      => 'epico',
                'emoji'       => '📅',
                'activo'      => true,
            ],

            // ── Logros de completitud ─────────────────────────────────────
            [
                'codigo'      => 'maestro_sumas',
                'nombre_es'   => 'Maestro de Sumas',
                'nombre_gn'   => 'Papapy Ñemoñe\'ẽ Ojehupyty',
                'descripcion' => 'Superaste todos los niveles de Suma de Frutas. ¡Sos un experto!',
                'rareza'      => 'epico',
                'emoji'       => '🏆',
                'activo'      => true,
            ],
        ];

        foreach ($logros as $logro) {
            CatalogoLogro::updateOrCreate(
                ['codigo' => $logro['codigo']],
                $logro
            );
        }

        $this->command->info('  ✅ ' . count($logros) . ' logros creados en el catálogo');
    }
}
