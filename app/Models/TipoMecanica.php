<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: TipoMecanica
 * -------------------------------------------------------------------------
 * Catálogo de mecánicas matemáticas (suma, resta, multiplicación, etc.).
 * Primer nivel de la jerarquía: TipoMecanica → Juego → Nivel → Ejercicio.
 *
 * Referencia: Tabla 8 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 */
class TipoMecanica extends Model
{
    public $timestamps = false;

    protected $table = 'tipos_mecanica';

    protected $fillable = ['nombre', 'tipo', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function juegos(): HasMany
    {
        return $this->hasMany(Juego::class, 'tipo_mecanica_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
