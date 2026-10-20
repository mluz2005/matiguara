<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: MensajeRetro
 * -------------------------------------------------------------------------
 * Mensaje de retroalimentación bilingüe del catálogo.
 * El RetroalimentacionService selecciona aleatoriamente para no repetir.
 *
 * Referencia: Tabla 12 de 21 — Sección 1.3.9
 * RF relacionados: RF-05
 * RN relacionadas: RN-06
 * -------------------------------------------------------------------------
 */
class MensajeRetro extends Model
{
    public $timestamps = false;

    protected $table = 'mensajes_retro';

    protected $fillable = [
        'tipo', 'contexto', 'texto_es', 'texto_gn', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    /**
     * Obtiene un mensaje aleatorio para el tipo y contexto dados,
     * excluyendo el último mensaje mostrado (sistema anti-repetición).
     *
     * @param  string   $tipo     Tipo del mensaje (acierto, error, etc.)
     * @param  string   $contexto Contexto del mensaje (general, reintento, etc.)
     * @param  int|null $excluirId ID del último mensaje mostrado (para no repetir)
     * @return self|null
     */
    public static function obtenerAleatorio(
        string $tipo,
        string $contexto = 'general',
        ?int $excluirId = null
    ): ?self {
        $query = self::where('tipo', $tipo)
                     ->where('contexto', $contexto)
                     ->where('activo', true);

        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->inRandomOrder()->first();
    }
}
