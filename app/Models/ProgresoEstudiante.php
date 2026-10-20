<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: ProgresoEstudiante
 * -------------------------------------------------------------------------
 * Progreso acumulado del estudiante por nivel, juego y grado.
 * Se actualiza después de cada sesión (upsert por clave única).
 *
 * La columna 'grado' permite filtrar por grado activo (RF-17).
 *
 * Referencia: Tabla 13 de 21 — Sección 1.3.9
 * RF relacionados: RF-03, RF-08, RF-17
 * -------------------------------------------------------------------------
 */
class ProgresoEstudiante extends Model
{
    public $timestamps = false;  // Solo tiene updated_at
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $table = 'progreso_estudiante';

    protected $fillable = [
        'estudiante_id', 'juego_id', 'nivel_id', 'grado',
        'mejor_puntaje', 'total_partidas', 'total_aciertos',
        'nivel_desbloqueado', 'ultimo_mensaje_id',
    ];

    protected $casts = [
        'nivel_desbloqueado' => 'boolean',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }

    public function ultimoMensaje(): BelongsTo
    {
        return $this->belongsTo(MensajeRetro::class, 'ultimo_mensaje_id');
    }

    /** Porcentaje de aciertos sobre el total de intentos. */
    public function porcentajeAciertos(): float
    {
        if ($this->total_partidas === 0) return 0.0;
        return round(($this->total_aciertos / $this->total_partidas) * 100, 2);
    }

    /** Scope: solo niveles desbloqueados por el estudiante. */
    public function scopeDesbloqueados($query)
    {
        return $query->where('nivel_desbloqueado', true);
    }

    /** Scope: filtrar por grado activo del estudiante. */
    public function scopeDelGrado($query, int $grado)
    {
        return $query->where('grado', $grado);
    }
}
