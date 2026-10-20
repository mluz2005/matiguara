<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: ConfigDocenteJuego
 * -------------------------------------------------------------------------
 * Configuración de habilitación de juegos por docente y grado (CU-08).
 * Índice único: (docente_id, juego_id, grado).
 * Si no existe registro, se asume habilitado=TRUE.
 *
 * Referencia: Tabla 19 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 */
class ConfigDocenteJuego extends Model
{
    protected $table = 'config_docente_juego';

    protected $fillable = [
        'docente_id', 'juego_id', 'grado', 'habilitado',
    ];

    protected $casts = ['habilitado' => 'boolean'];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }
}
