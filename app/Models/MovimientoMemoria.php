<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: MovimientoMemoria
 * =========================================================================
 * Registra un intento de par (2 cartas volteadas) en una sesión del
 * juego "Encuentra el par". Análogo a MovimientoSesion pero para
 * el juego de memoria, con su propia FK hacia tarjetas_nivel.
 *
 * Referencia: Documento de requerimientos — Sección 10.5
 * =========================================================================
 */
class MovimientoMemoria extends Model
{
    public $timestamps = false;

    protected $table = 'movimientos_memoria';

    protected $fillable = [
        'sesion_id',
        'tarjeta_a_id',
        'tarjeta_b_id',
        'es_acierto',
        'tiempo_respuesta_ms',
        'timestamp_mov',
    ];

    protected $casts = [
        'es_acierto'     => 'boolean',
        'timestamp_mov'  => 'datetime',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }

    public function tarjetaA(): BelongsTo
    {
        return $this->belongsTo(TarjetaNivel::class, 'tarjeta_a_id');
    }

    public function tarjetaB(): BelongsTo
    {
        return $this->belongsTo(TarjetaNivel::class, 'tarjeta_b_id');
    }
}
