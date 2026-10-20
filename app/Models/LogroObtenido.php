<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: LogroObtenido
 * -------------------------------------------------------------------------
 * Logro efectivamente obtenido por un estudiante.
 * La restricción UNIQUE(est, logro, juego) implementa RN-09.
 *
 * Referencia: Tabla 17 de 21 — Sección 1.3.9
 * RN relacionadas: RN-09
 * -------------------------------------------------------------------------
 */
class LogroObtenido extends Model
{
    public $timestamps = false;

    protected $table = 'logros_obtenidos';

    protected $fillable = [
        'estudiante_id', 'logro_id', 'sesion_id',
        'juego_id', 'valor_registrado', 'fecha_obtenido',
    ];

    protected $casts = ['fecha_obtenido' => 'datetime'];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function logro(): BelongsTo
    {
        return $this->belongsTo(CatalogoLogro::class, 'logro_id');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }
}
