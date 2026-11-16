<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: DetalleNumeros — un «viaje» de «Mati y los números».
 * (Tabla detalle_numeros, 1:1 con sesiones_juego.)
 */
class DetalleNumeros extends Model
{
    protected $table = 'detalle_numeros';

    protected $fillable = [
        'sesion_id', 'viaje', 'ciclo', 'semilla', 'grado', 'partes_json',
        'completa', 'confiable', 'pct_azar', 'tiempo_base_ms', 'nivel_final',
        'duracion_s', 'respondidas', 'total_preguntas', 'valida',
        'motivo_no_valida', 'marcada_no_valida_en',
    ];

    protected $casts = [
        'completa'  => 'boolean',
        'confiable' => 'boolean',
        'valida'    => 'boolean',
        'partes_json' => 'array',
        'marcada_no_valida_en' => 'datetime',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaNumeros::class, 'sesion_id', 'sesion_id');
    }
}
