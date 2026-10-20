<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: MovimientoSesion
 * -------------------------------------------------------------------------
 * Respuesta individual del estudiante. Granularidad máxima del sistema.
 * Es el insumo principal del diagnóstico IA (datos anonimizados).
 *
 * Referencia: Tabla 15 de 21 — Sección 1.3.9
 * RF relacionados: RF-07, RF-10
 * RN relacionadas: RN-01
 * -------------------------------------------------------------------------
 */
class MovimientoSesion extends Model
{
    public $timestamps = false;

    protected $table = 'movimientos_sesion';

    protected $fillable = [
        'sesion_id', 'contenido_id', 'numero_intento',
        'respuesta_dada', 'es_correcto',
        'tiempo_respuesta_ms', 'tipo_error', 'timestamp_mov',
    ];

    protected $casts = [
        'es_correcto'     => 'boolean',
        'timestamp_mov'   => 'datetime',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionJuego::class, 'sesion_id');
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(ContenidoNivel::class, 'contenido_id');
    }

    /** ¿Fue el primer intento de este ejercicio en esta sesión? */
    public function esPrimerIntento(): bool
    {
        return $this->numero_intento === 1;
    }
}
