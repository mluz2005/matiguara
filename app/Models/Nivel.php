<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: Nivel
 * -------------------------------------------------------------------------
 * Nivel de dificultad dentro de un juego, para un grado específico.
 * El algoritmo adaptativo 3/2 sube y baja de nivel (RF-04, RF-13).
 *
 * Referencia: Tabla 10 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 */
class Nivel extends Model
{
    public $timestamps = false;
    protected $table = 'niveles';

    protected $fillable = [
        'juego_id', 'grado', 'numero_nivel', 'dificultad',
        'min_aciertos_pasar', 'total_ejercicios', 'desbloqueado_por_defecto',
        'tiempo_mostrar_ms',   // Flash Matemático: ms que quedan visibles los operandos
        'tiempo_limite_seg',   // Encuentra el par: segundos totales de la partida
    ];

    protected $casts = ['desbloqueado_por_defecto' => 'boolean'];

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(ContenidoNivel::class, 'nivel_id');
    }

    /** Obtiene el nivel siguiente (para desbloqueo tras superación). */
    public function nivelSiguiente(): ?self
    {
        return self::where('juego_id', $this->juego_id)
                   ->where('grado', $this->grado)
                   ->where('numero_nivel', $this->numero_nivel + 1)
                   ->first();
    }
}
