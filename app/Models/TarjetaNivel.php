<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: TarjetaNivel
 * =========================================================================
 * Representa UNA tarjeta posible dentro del pool de un nivel del juego
 * "Encuentra el par". No es la carta física del tablero — es la
 * DEFINICIÓN de un ítem (ej: "lápiz" o el número "5") que el
 * MemoriaService usa para generar el mazo de cada sesión.
 *
 * Referencia: Documento de requerimientos "Encuentra el par" — Sección 10.4
 * =========================================================================
 */
class TarjetaNivel extends Model
{
    protected $table = 'tarjetas_nivel';

    protected $fillable = [
        'nivel_id',
        'tipo_contenido',
        'valor',
        'nombre_es',
        'nombre_gn',
        'emoji',
        'imagen_url',
        'audio_es',
        'audio_gn',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    /** Scope: solo tarjetas activas */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Representación visual: usa emoji si no hay imagen definitiva todavía.
     */
    public function getRepresentacionVisualAttribute(): string
    {
        return $this->imagen_url ?? $this->emoji ?? '❓';
    }
}
