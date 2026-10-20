<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: Matricula
 * -------------------------------------------------------------------------
 * Matrícula anual del estudiante. El registro activo define el contexto
 * actual: grado, institución y docente del estudiante (RF-17).
 *
 * Al cerrar una matrícula de grado=3, MatriculaService::cerrarMatricula() calcula
 * fecha_baja_programada en el estudiante (RF-18 / RN-10).
 *
 * Referencia: Tabla 7 de 21 — Sección 1.3.9
 * RF relacionados: RF-17, RF-18
 * -------------------------------------------------------------------------
 */
class Matricula extends Model
{
    protected $table = 'matriculas';
    protected $fillable = [
        'estudiante_id', 'institucion_id', 'docente_id',
        'grado', 'anio_lectivo', 'activo', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    /** ¿Es matrícula de 3° grado? Determina el ciclo de vida RF-18. */
    public function esTercerGrado(): bool
    {
        return $this->grado === 3;
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
