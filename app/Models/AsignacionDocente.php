<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: AsignacionDocente
 * -------------------------------------------------------------------------
 * Asignación de un docente a una institución y grado específico.
 * Mantiene el historial completo de movimientos docentes.
 *
 * Referencia: Tabla 5 de 21 — Sección 1.3.9
 * RF relacionados: RF-08, RF-14
 * -------------------------------------------------------------------------
 */
class AsignacionDocente extends Model
{
    protected $table = 'asignaciones_docente';

    protected $fillable = [
        'docente_id', 'institucion_id', 'grado', 'turno',
        'anio_lectivo', 'activo', 'motivo_baja', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
    ];

    // ── RELACIONES ────────────────────────────────────────────────────

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    // ── SCOPES ────────────────────────────────────────────────────────

    /** Solo asignaciones vigentes. */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /** Asignaciones del año lectivo indicado. */
    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio_lectivo', $anio);
    }
}
