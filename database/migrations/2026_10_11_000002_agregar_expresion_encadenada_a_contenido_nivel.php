<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (cambio de estructura, pequeño y seguro): ejercicios de 3 números
 * ============================================================================
 * Flash Matemático tendrá niveles con operaciones COMBINADAS de tres números,
 * por ejemplo  7 + 2 − 4.  Hasta ahora cada ejercicio guardaba solo dos
 * números y un signo (valor_a, operador, valor_b).
 *
 * Se agregan dos columnas OPCIONALES a `contenido_nivel`:
 *     operador_2  → el segundo signo      (+ o -)
 *     valor_c     → el tercer número
 *
 * Por qué es seguro:
 *   - Ambas aceptan NULL: los ejercicios que ya existen quedan igual
 *     (con NULL en las dos) y siguen funcionando sin ningún cambio.
 *   - No se modifica ni se borra ninguna columna ni ningún dato existente.
 *   - Si las columnas ya existen, no hace nada (se puede ejecutar de nuevo).
 *   - down() las quita.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('contenido_nivel', 'operador_2')) {
            Schema::table('contenido_nivel', function (Blueprint $table) {
                $table->string('operador_2', 3)->nullable()->after('valor_b');
            });
        }

        if (!Schema::hasColumn('contenido_nivel', 'valor_c')) {
            Schema::table('contenido_nivel', function (Blueprint $table) {
                $table->unsignedSmallInteger('valor_c')->nullable()->after('operador_2');
            });
        }
    }

    public function down(): void
    {
        foreach (['valor_c', 'operador_2'] as $columna) {
            if (Schema::hasColumn('contenido_nivel', $columna)) {
                Schema::table('contenido_nivel', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
