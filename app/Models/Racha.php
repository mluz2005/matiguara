<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: Racha
 * -------------------------------------------------------------------------
 * Registro de rachas (aciertos consecutivos y días consecutivos).
 * Un registro por combinación estudiante+juego (índice único).
 * Los valores máximos NUNCA decrementan. Los actuales sí.
 *
 * Referencia: Tabla 18 de 21 — Sección 1.3.9
 * RN relacionadas: RN-08
 * -------------------------------------------------------------------------
 */
class Racha extends Model
{
    protected $table = 'rachas';
    public $timestamps = false;
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = [
        'estudiante_id', 'juego_id',
        'racha_actual_aciertos', 'racha_maxima_aciertos',
        'racha_actual_dias', 'racha_maxima_dias',
        'ultima_sesion_fecha',
    ];

    protected $casts = [
        'ultima_sesion_fecha' => 'date',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }

    /**
     * Actualiza racha_maxima_aciertos si el actual es mayor.
     * Los máximos son unidireccionales (solo suben).
     */
    public function actualizarMaximoAciertos(): void
    {
        if ($this->racha_actual_aciertos > $this->racha_maxima_aciertos) {
            $this->racha_maxima_aciertos = $this->racha_actual_aciertos;
        }
    }

    /** Reinicia la racha actual de aciertos (ante un error). */
    public function reiniciarRachaAciertos(): void
    {
        $this->racha_actual_aciertos = 0;
    }
}
