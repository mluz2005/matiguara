<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: DetalleLaberinto — datos propios de una partida de «El camino a la escuela».
 * (Tabla detalle_laberinto, 1:1 con sesiones_juego.)
 */
class DetalleLaberinto extends Model
{
    public $timestamps = false;

    protected $table = 'detalle_laberinto';

    protected $fillable = [
        'sesion_id', 'semilla', 'obstaculos_total', 'estrellas', 'pistas_usadas',
        'uso_flecha', 'soluciones_mostradas', 'chipas_recogidas', 'chipas_total',
        'duracion_s', 'llego',
    ];

    protected $casts = [
        'uso_flecha' => 'boolean',
        'llego'      => 'boolean',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }
}
