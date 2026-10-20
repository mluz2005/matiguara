<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: Docente
 * -------------------------------------------------------------------------
 * Perfil extendido del docente. Extiende Usuario mediante relación 1:1.
 * El docente.id (no usuario.id) es la FK usada en asignaciones y matrículas.
 *
 * Para acceder al email o nombre del docente:
 *   $docente->usuario->nombre    ← correcto
 *   $docente->usuario->email     ← correcto
 *
 * Referencia: Tabla 3 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 */
class Docente extends Model
{
    protected $table = 'docentes';
    protected $fillable = [
        'usuario_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── RELACIONES ────────────────────────────────────────────────────

    /** Datos de la cuenta de usuario (nombre, email, perfil). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** Asignaciones a instituciones y grados (historial completo). */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class, 'docente_id');
    }

    /**
     * Asignación activa (la vigente actualmente).
     * Un docente puede tener solo una asignación activa por institución/grado.
     */
    public function asignacionActiva()
    {
        return $this->hasOne(AsignacionDocente::class, 'docente_id')
                    ->where('activo', true)
                    ->latestOfMany();
    }

    /** Matrículas de estudiantes bajo su responsabilidad. */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'docente_id');
    }

    /** Configuración de juegos habilitados por grado. */
    public function configuracionJuegos(): HasMany
    {
        return $this->hasMany(ConfigDocenteJuego::class, 'docente_id');
    }

    /** Diagnósticos solicitados por este docente. */
    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'docente_id');
    }

    // ── HELPERS ───────────────────────────────────────────────────────

    /** Nombre completo (delega al usuario). */
    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario->apellido . ', ' . $this->usuario->nombre;
    }

    // ── SCOPES ────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
