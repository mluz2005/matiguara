<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * MODELO: Institucion
 * -------------------------------------------------------------------------
 * Representa una institución educativa registrada en el sistema.
 *
 * Relaciones:
 *   - hasMany AsignacionDocente  → docentes asignados en el tiempo
 *   - hasMany Matricula          → matrículas de estudiantes
 *   - hasOne  ConfigInstitucion  → configuración operativa (singleton)
 *
 * Referencia: Tabla 1 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 *
 * @property int    $id
 * @property string $nombre
 * @property string $codigo   Código único (UK)
 * @property string $ciudad
 * @property string $tipo     EEB | Colegio | Instituto
 * @property bool   $activo
 */
class Institucion extends Model
{
    /**
     * Campos que se pueden asignar masivamente.
     * Todos los campos editables por la Administradora (CU-26, CU-27).
     */
    protected $table = 'instituciones';
    protected $fillable = [
        'nombre',
        'codigo',
        'ciudad',
        'tipo',
        'activo',
    ];

    /**
     * Conversión automática de tipos (casting).
     * Laravel castea automáticamente estos campos al leerlos del modelo.
     */
    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── RELACIONES ────────────────────────────────────────────────────

    /**
     * Docentes asignados a esta institución (historial completo).
     * Para obtener solo los activos: ->asignaciones()->where('activo', true)
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class, 'institucion_id');
    }

    /**
     * Matrículas de todos los estudiantes en esta institución.
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'institucion_id');
    }

    /**
     * Configuración operativa de la institución (singleton).
     * Si no existe, InstitucionController la crea con valores por defecto.
     */
    public function config(): HasOne
    {
        return $this->hasOne(ConfigInstitucion::class, 'institucion_id');
    }

    // ── SCOPES ────────────────────────────────────────────────────────

    /**
     * Scope: solo instituciones activas.
     * Uso: Institucion::activas()->get()
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
