<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: Diagnostico
 * -------------------------------------------------------------------------
 * Diagnóstico pedagógico generado por la API Claude. INMUTABLE.
 * No se editan ni eliminan (salvo eliminación del estudiante egresado).
 *
 * Contiene snapshot numérico + texto generado por IA.
 * Referencia para análisis longitudinal: ordenar por fecha_generacion.
 *
 * PRIVACIDAD (RN-01): el campo id_anonimizado es el UUID del estudiante,
 * no una FK. Permite auditar la llamada a la API sin reidentificar.
 *
 * Referencia: Tabla 20 de 21 — Sección 1.3.9 ★ NUEVA v1.2
 * RF relacionados: RF-10, RF-11, RF-12, RF-15
 * RN relacionadas: RN-01, RN-02, RN-05
 * -------------------------------------------------------------------------
 */
class Diagnostico extends Model
{
    // INMUTABLE: sin updated_at porque nunca se modifica
    const UPDATED_AT = null;
    protected $table = 'diagnosticos';
    public $timestamps = false; 

    protected $fillable = [
        'estudiante_id', 'docente_id', 'id_anonimizado',
        'fecha_generacion',
        'puntaje_promedio', 'porcentaje_aciertos', 'nivel_max_alcanzado',
        'fortalezas', 'dificultades', 'recomendacion',
        'tokens_usados', 'estado',
    ];

    protected $casts = [
        'fecha_generacion'    => 'datetime',
        'puntaje_promedio'    => 'decimal:2',
        'porcentaje_aciertos' => 'decimal:2',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    /** ¿El diagnóstico se generó exitosamente? */
    public function esExitoso(): bool
    {
        return $this->estado === 'exitoso';
    }

    public function scopeExitosos($query)
    {
        return $query->where('estado', 'exitoso');
    }

    public function scopeDelEstudiante($query, int $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId)
                     ->orderBy('fecha_generacion', 'desc');
    }
}
