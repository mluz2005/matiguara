<?php

namespace Database\Seeders;

use App\Models\ConfigInstitucion;
use App\Models\Institucion;
use Illuminate\Database\Seeder;

/**
 * SEEDER: InstitucionesSeeder
 * -------------------------------------------------------------------------
 * Crea instituciones de prueba para el entorno de desarrollo.
 * Por cada institución creada, se genera automáticamente su
 * config_institucion con valores por defecto (patrón singleton RF-16).
 * -------------------------------------------------------------------------
 */
class InstitucionesSeeder extends Seeder
{
    public function run(): void
    {
        $instituciones = [
            [
                'nombre'  => 'Escuela Básica N° 1234 San Roque González',
                'codigo'  => 'EEB-ENC-001',
                'ciudad'  => 'Encarnación',
                'tipo'    => 'EEB',
                'activo'  => true,
            ],
            [
                'nombre'  => 'Escuela Básica N° 567 República del Paraguay',
                'codigo'  => 'EEB-ENC-002',
                'ciudad'  => 'Encarnación',
                'tipo'    => 'EEB',
                'activo'  => true,
            ],
        ];

        foreach ($instituciones as $datos) {
            // Crear la institución
            $inst = Institucion::create($datos);

            // Crear su config_institucion con valores por defecto (RF-16)
            // dias_reinicio_racha_default=3, permitir_acceso_grados_ant=false,
            // meses_conservacion_egresados=4
            ConfigInstitucion::crearPorDefecto($inst->id);

            $this->command->info("  ✅ Institución creada: {$inst->nombre}");
        }
    }
}
