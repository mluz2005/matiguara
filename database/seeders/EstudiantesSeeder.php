<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use Illuminate\Database\Seeder;

/**
 * SEEDER: EstudiantesSeeder
 * -------------------------------------------------------------------------
 * Crea estudiantes de prueba con PIN hasheado y UUID anonimizado.
 *
 * CREDENCIALES DE PRUEBA (solo para desarrollo):
 *   código: SAN-1A-001  PIN: 1234
 *   código: SAN-1A-002  PIN: 5678
 *   código: SAN-2B-001  PIN: 1111
 *
 * El id_anonimizado es un UUID v4 generado automáticamente.
 * En producción el docente elige el PIN junto al estudiante.
 * -------------------------------------------------------------------------
 */
class EstudiantesSeeder extends Seeder
{
    public function run(): void
    {
        $estudiantes = [
            ['nombre' => 'Ana',    'apellido' => 'López',    'codigo' => 'SAN-1A-001', 'pin' => '1234'],
            ['nombre' => 'Juan',   'apellido' => 'Martínez', 'codigo' => 'SAN-1A-002', 'pin' => '5678'],
            ['nombre' => 'Sofía',  'apellido' => 'Torres',   'codigo' => 'SAN-1A-003', 'pin' => '9012'],
            ['nombre' => 'Diego',  'apellido' => 'Ramírez',  'codigo' => 'SAN-2B-001', 'pin' => '1111'],
            ['nombre' => 'Lucía',  'apellido' => 'Benítez',  'codigo' => 'SAN-2B-002', 'pin' => '2222'],
        ];

        foreach ($estudiantes as $datos) {
            Estudiante::create([
                'nombre'         => $datos['nombre'],
                'apellido'       => $datos['apellido'],
                'codigo_acceso'  => $datos['codigo'],
                // PIN hasheado con bcrypt (método del modelo — valida 4 dígitos)
                'pin_hash'       => Estudiante::hashearPin($datos['pin']),
                // UUID v4 único: el único ID que sale del sistema hacia la API
                'id_anonimizado' => Estudiante::generarIdAnonimizado(),
                'activo'         => true,
            ]);
            $this->command->info("  ✅ Estudiante: {$datos['codigo']} (PIN: {$datos['pin']})");
        }
    }
}
