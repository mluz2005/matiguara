<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: TamizajeFicha — observaciones opcionales de la docente sobre un niño
 * (casillas + nota corta). (Tabla tamizaje_fichas, una fila por niño.)
 */
class TamizajeFicha extends Model
{
    protected $table = 'tamizaje_fichas';

    protected $fillable = ['estudiante_id', 'docente_id', 'observaciones', 'nota'];

    protected $casts = [
        'observaciones' => 'array',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
