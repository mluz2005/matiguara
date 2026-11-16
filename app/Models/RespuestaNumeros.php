<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: RespuestaNumeros — una pregunta respondida en «Mati y los números».
 * (Tabla respuestas_numeros.)
 */
class RespuestaNumeros extends Model
{
    public $timestamps = false;

    protected $table = 'respuestas_numeros';

    protected $fillable = [
        'sesion_id', 'parte', 'orden', 'item_clave', 'area', 'nivel', 'fija', 'cero',
        'respuesta', 'es_correcto', 'tipo_error', 'tiempo_ms', 'azar', 'error_pct', 'respondido_en',
    ];

    protected $casts = [
        'fija'          => 'boolean',
        'cero'          => 'boolean',
        'es_correcto'   => 'boolean',
        'azar'          => 'boolean',
        'respondido_en' => 'datetime',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }
}
