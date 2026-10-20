<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: CatalogoLogro
 * -------------------------------------------------------------------------
 * Catálogo maestro de logros disponibles. Define las condiciones de obtención.
 * LogroService evalúa estas condiciones después de cada sesión (RF-03).
 *
 * Referencia: Tabla 16 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 */
class CatalogoLogro extends Model
{
    public $timestamps = false;

    protected $table = 'catalogo_logros';

    protected $fillable = [
        'nombre_es', 'nombre_gn', 'emoji', 'descripcion_es', 'tipo',
        'condicion_tipo', 'condicion_valor', 'puntos_logro',
        'rareza', 'icono_url', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function logrosObtenidos(): HasMany
    {
        return $this->hasMany(LogroObtenido::class, 'logro_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
