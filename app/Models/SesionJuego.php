<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: SesionJuego
 * -------------------------------------------------------------------------
 * Partida individual del estudiante (ver DE-01 Diagrama de Estados).
 * Estados posibles: en_curso → completada | abandonada
 *
 * Referencia: Tabla 14 de 21 — Sección 1.3.9
 * RF relacionados: RF-07 (registro anonimizado)
 * -------------------------------------------------------------------------
 */
class SesionJuego extends Model
{
    public $timestamps = false;

    protected $table = 'sesiones_juego';

    protected $fillable = [
        'estudiante_id', 'juego_id', 'nivel_id',
        'fecha_inicio', 'fecha_fin',
        'puntaje_obtenido', 'aciertos', 'errores',
        'nivel_superado', 'estado', 'device_tipo',
    ];

    protected $casts = [
        'nivel_superado' => 'boolean',
        'fecha_inicio'   => 'datetime',
        'fecha_fin'      => 'datetime',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoSesion::class, 'sesion_id');
    }

    /** Duración de la sesión en segundos. NULL si aún está en curso. */
    public function duracionSegundos(): ?int
    {
        if (!$this->fecha_fin) return null;
        return $this->fecha_inicio->diffInSeconds($this->fecha_fin);
    }

    /** ¿La sesión está actualmente en curso? */
    public function estaEnCurso(): bool
    {
        return $this->estado === 'en_curso';
    }

    public function scopeEnCurso($query)
    {
        return $query->where('estado', 'en_curso');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }
}
