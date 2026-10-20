<?php

namespace Database\Seeders;

use App\Models\TipoMecanica;
use Illuminate\Database\Seeder;

/**
 * SEEDER: TiposMecanicaSeeder
 * Catálogo inicial de mecánicas matemáticas.
 * Corresponde al primer ciclo EEB (1°, 2°, 3° grado).
 */
class TiposMecanicaSeeder extends Seeder
{
    public function run(): void
    {
        $mecanicas = [
            ['nombre' => 'Suma simple',            'tipo' => 'suma',           'descripcion' => 'Suma de números de 1 a 20. Grado 1.'],
            ['nombre' => 'Suma con llevada',        'tipo' => 'suma',           'descripcion' => 'Suma de números de 2 cifras. Grado 2-3.'],
            ['nombre' => 'Resta simple',            'tipo' => 'resta',          'descripcion' => 'Resta de números de 1 a 20. Grado 1-2.'],
            ['nombre' => 'Resta con llevada',       'tipo' => 'resta',          'descripcion' => 'Resta de números de 2 cifras. Grado 2-3.'],
            ['nombre' => 'Multiplicación básica',   'tipo' => 'multiplicacion', 'descripcion' => 'Tablas del 1 al 5. Grado 3.'],
            ['nombre' => 'Operaciones mixtas',      'tipo' => 'mixto',          'descripcion' => 'Combina suma y resta. Grado 2-3.'],
        ];

        foreach ($mecanicas as $m) {
            TipoMecanica::create(array_merge($m, ['activo' => true]));
        }
        $this->command->info('  ✅ Tipos de mecánica: ' . count($mecanicas));
    }
}
