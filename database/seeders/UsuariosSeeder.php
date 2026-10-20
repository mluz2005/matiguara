<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\Padre;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SEEDER: UsuariosSeeder
 * -------------------------------------------------------------------------
 * Crea usuarios de prueba: 1 admin, 2 docentes, 1 padre.
 * Contraseñas hasheadas con bcrypt (Hash::make).
 *
 * CREDENCIALES DE PRUEBA (solo para desarrollo):
 *   admin@unae.edu.py    / Admin1234!
 *   docente1@unae.edu.py / Docente123!
 *   docente2@unae.edu.py / Docente123!
 *   padre@correo.com     / Padre1234!
 *
 * IMPORTANTE: Cambiar todas las contraseñas antes de ir a producción.
 * -------------------------------------------------------------------------
 */
class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administradora ────────────────────────────────────────────
        Usuario::create([
            'nombre'        => 'María',
            'apellido'      => 'García',
            'email'         => 'admin@unae.edu.py',
            'password_hash' => Hash::make('Admin1234!'),
            'perfil'        => 'admin',
            'activo'        => true,
        ]);
        $this->command->info('  ✅ Admin: admin@unae.edu.py');

        // ── Docente 1 ─────────────────────────────────────────────────
        $usuDocente1 = Usuario::create([
            'nombre'        => 'Laura',
            'apellido'      => 'Vega',
            'email'         => 'docente1@unae.edu.py',
            'password_hash' => Hash::make('Docente123!'),
            'perfil'        => 'docente',
            'activo'        => true,
        ]);
        Docente::create(['usuario_id' => $usuDocente1->id, 'activo' => true]);
        $this->command->info('  ✅ Docente: docente1@unae.edu.py');

        // ── Docente 2 ─────────────────────────────────────────────────
        $usuDocente2 = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Rodríguez',
            'email'         => 'docente2@unae.edu.py',
            'password_hash' => Hash::make('Docente123!'),
            'perfil'        => 'docente',
            'activo'        => true,
        ]);
        Docente::create(['usuario_id' => $usuDocente2->id, 'activo' => true]);
        $this->command->info('  ✅ Docente: docente2@unae.edu.py');

        // ── Padre de prueba (sin estudiante aún, se vincula en RelacionesSeeder)
        Usuario::create([
            'nombre'        => 'Roberto',
            'apellido'      => 'Pérez',
            'email'         => 'padre@correo.com',
            'password_hash' => Hash::make('Padre1234!'),
            'perfil'        => 'padre',
            'activo'        => true,
        ]);
        $this->command->info('  ✅ Padre: padre@correo.com');
    }
}
