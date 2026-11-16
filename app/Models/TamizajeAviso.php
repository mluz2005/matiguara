<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: TamizajeAviso — un aviso de la campanita de la docente («prestá más atención a…»).
 * (Tabla tamizaje_avisos.) Se crea solo cuando el semáforo de un niño EMPEORA a amarillo o rojo.
 */
class TamizajeAviso extends Model
{
    protected $table = 'tamizaje_avisos';

    protected $fillable = ['estudiante_id', 'docente_id', 'semaforo', 'ciclos', 'leido_en'];

    protected $casts = [
        'leido_en' => 'datetime',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function scopeSinLeer($query)
    {
        return $query->whereNull('leido_en');
    }
}
