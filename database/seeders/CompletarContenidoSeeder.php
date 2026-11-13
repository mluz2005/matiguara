<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * SEEDER: CompletarContenidoSeeder — completa los datos que las migraciones
 * no pudieron cargar en una instalación nueva
 * =========================================================================
 * PROBLEMA: varias migraciones de DATOS (ampliar Suma de Frutas, los 10
 * niveles de Flash Matemático, la medalla Flash Master, los emojis de las
 * medallas) buscan filas que sembraron los seeders. En una instalación nueva
 * `php artisan migrate` corre ANTES de sembrar, no encuentra los juegos ni el
 * catálogo y no hace nada. Resultado en producción:
 *   - Flash Matemático aparece "Sin niveles" para 2.° y 3.° (y con 3 en 1.°).
 *   - Suma de Frutas tiene 3 niveles en 1.° en lugar de 6.
 *   - Falta la medalla "Flash Master" y los emojis/descripciones de medallas.
 *
 * QUÉ HACE: vuelve a ejecutar esas migraciones de datos, en orden, ya con
 * los juegos y el catálogo cargados. Todas son idempotentes (no duplican
 * niveles, ejercicios ni medallas) y no tocan el progreso de los alumnos.
 *
 * CUÁNDO USARLO (instalación nueva, después de sembrar juegos y logros):
 *   php artisan db:seed --class=CompletarContenidoSeeder --force
 * Es seguro ejecutarlo más de una vez.
 * =========================================================================
 */
class CompletarContenidoSeeder extends Seeder
{
    /** Migraciones de datos, en el orden en que deben correr. */
    private const MIGRACIONES = [
        '2026_10_11_000001_ampliar_suma_de_frutas_con_restas.php',
        '2026_10_11_000003_ampliar_flash_matematico_a_10_niveles.php',
        '2026_10_12_000004_alargar_tiempos_flash_matematico.php',
        '2026_10_12_000006_crear_medalla_flash_master.php',
        '2026_10_12_000007_agregar_emoji_a_catalogo_logros.php',
    ];

    /** Archivos ya leídos (cada archivo devuelve un objeto de migración y se lee una sola vez). */
    private static array $cargadas = [];

    public function run(): void
    {
        foreach (self::MIGRACIONES as $archivo) {
            $ruta = database_path('migrations/' . $archivo);
            $migracion = self::$cargadas[$ruta] ??= require $ruta;
            $migracion->up();
        }

        if (isset($this->command)) {
            $this->command->info('  ✅ Suma de Frutas (1.°), Flash Matemático (10 niveles x 3 grados) y medallas completados.');
        }
    }
}
