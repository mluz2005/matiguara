<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

/**
 * MODELO: Usuario
 * -------------------------------------------------------------------------
 * Cuentas de acceso para usuarios ADULTOS: docentes, padres y administradoras.
 * Los ESTUDIANTES no usan este modelo (usan Estudiante con código+PIN).
 *
 * Extiende Authenticatable para integración con el sistema de auth de Laravel.
 * El campo 'perfil' actúa como discriminador de rol (RF-01).
 *
 * Referencia: Tabla 2 de 21 — Sección 1.3.9
 * -------------------------------------------------------------------------
 *
 * @property int    $id
 * @property string $nombre
 * @property string $apellido
 * @property string $email     Identificador de login (unique)
 * @property string $password_hash
 * @property string $perfil    docente | padre | admin
 * @property bool   $activo
 */
class Usuario extends Authenticatable
{
    protected $table = 'usuarios';
    use Notifiable;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password_hash',
        'perfil',
        'activo',
    ];

    /**
     * Campos que NUNCA se incluyen al serializar el modelo (ej: ->toArray(), JSON).
     * El password_hash nunca debe exponerse en respuestas de API.
     */
    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Laravel usa 'password' internamente para autenticación.
     * Como nuestra columna se llama 'password_hash', redefinimos el getter.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ── HELPERS DE ROL ────────────────────────────────────────────────

    /** ¿Es docente? Usado en middleware y políticas de acceso. */
    public function esDocente(): bool
    {
        return $this->perfil === 'docente';
    }

    /** ¿Es padre/tutor? */
    public function esPadre(): bool
    {
        return $this->perfil === 'padre';
    }

    /** ¿Es administradora? */
    public function esAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    // ── RELACIONES ────────────────────────────────────────────────────

    /**
     * Perfil extendido de docente (1:1).
     * NULL si el usuario no es docente.
     */
    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'usuario_id');
    }

    /**
     * Vínculo con estudiante (si el usuario es padre).
     */
    public function padre(): HasOne
    {
        return $this->hasOne(Padre::class, 'usuario_id');
    }

    // ── SCOPES ────────────────────────────────────────────────────────

    /** Scope: solo usuarios activos. */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /** Scope: filtrar por perfil. Uso: Usuario::delPerfil('docente')->get() */
    public function scopeDelPerfil($query, string $perfil)
    {
        return $query->where('perfil', $perfil);
    }
}
