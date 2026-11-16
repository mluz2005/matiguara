<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: TamizajeEstado — el último semáforo calculado de un niño en «Mati y los números».
 * (Tabla tamizaje_estados, una fila por niño.) No es un diagnóstico: es una señal para la docente.
 */
class TamizajeEstado extends Model
{
    protected $table = 'tamizaje_estados';

    protected $fillable = [
        'estudiante_id', 'semaforo', 'ciclos_completos', 'ciclos_considerados', 'resultado_json', 'calculado_en',
    ];

    protected $casts = [
        'resultado_json' => 'array',
        'calculado_en'   => 'datetime',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
