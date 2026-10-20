<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * SEEDER PRINCIPAL: DatabaseSeeder
 * -------------------------------------------------------------------------
 * Orquesta todos los seeders en el orden correcto (respeta dependencias FK).
 *
 * Ejecución:
 *   php artisan db:seed               ← ejecuta este seeder
 *   php artisan migrate:fresh --seed  ← migra desde cero + seed
 *
 * IMPORTANTE: Los seeders de PRODUCCIÓN no deben cargarse con --seed.
 * Solo usar en desarrollo y testing (entorno local).
 * -------------------------------------------------------------------------
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Grupo 1: Catálogos (sin FK externas) ──────────────────
            TiposMecanicaSeeder::class,   // tipos_mecanica
            MensajesRetroSeeder::class,   // mensajes_retro (catálogo bilingüe)
            CatalogoLogrosSeeder::class,  // catalogo_logros

            // ── Grupo 2: Institucional ─────────────────────────────────
            InstitucionesSeeder::class,   // instituciones + config_institucion
            UsuariosSeeder::class,        // usuarios (admin, docentes, padres)
            EstudiantesSeeder::class,     // estudiantes (con PIN hasheado y UUID)
            RelacionesSeeder::class,      // docentes, padres, asignaciones, matrículas

            // ── Grupo 3: Contenido educativo ───────────────────────────
            JuegosSeeder::class,          // juegos + niveles + contenido_nivel

            // ── Grupo 4: Datos de prueba de progreso ───────────────────
            // (opcional — solo si se quiere demo con datos pre-cargados)
            // DemoProgresoSeeder::class,
        ]);
    }
}
