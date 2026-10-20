<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN (cambio de estructura, pequeño y seguro): emoji de las medallas — 2026-10-12
 * ============================================================================
 * Las pantallas de medallas buscan un emoji para cada medalla ($logro->emoji),
 * pero la tabla `catalogo_logros` no tenía esa columna, así que todas se veían
 * con el mismo 🏅.
 *
 * Qué hace:
 *   1) Agrega la columna `emoji` (texto corto, opcional) a `catalogo_logros`.
 *      Acepta NULL: nada de lo existente se ve afectado; una medalla sin emoji
 *      sigue mostrando 🏅 como hasta ahora.
 *   2) Asigna un emoji a las 10 medallas actuales (solo a las que no tengan uno).
 *
 * Es segura de ejecutar más de una vez. down() quita la columna.
 *
 * Para una medalla nueva, basta con completar `emoji` en su fila del catálogo.
 * ============================================================================
 */
return new class extends Migration
{
    /** nombre_es => emoji */
    private array $emojis = [
        'Primera Racha'       => '🔥',
        'Imparable'           => '🚀',
        'Maestro del Cálculo' => '🔢',
        'Primer Paso'         => '👣',
        'Constante'           => '🏃',
        'Nivel Conquistado'   => '⭐',
        'Explorador'          => '🗺️',
        'Semana Completa'     => '📅',
        'Leyenda'             => '👑',
        'Flash Master'        => '🧠',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('catalogo_logros')) return;

        if (!Schema::hasColumn('catalogo_logros', 'emoji')) {
            Schema::table('catalogo_logros', function (Blueprint $table) {
                $table->string('emoji', 16)->nullable()->after('nombre_gn');
            });
        }

        foreach ($this->emojis as $nombre => $emoji) {
            DB::table('catalogo_logros')
                ->where('nombre_es', $nombre)
                ->where(function ($q) {
                    $q->whereNull('emoji')->orWhere('emoji', '');
                })
                ->update(['emoji' => $emoji]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('catalogo_logros', 'emoji')) {
            Schema::table('catalogo_logros', function (Blueprint $table) {
                $table->dropColumn('emoji');
            });
        }
    }
};
