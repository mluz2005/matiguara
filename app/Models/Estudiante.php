<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * MODELO: Estudiante  [VERSIÓN SEMANA 1 — extiende Authenticatable]
 * =========================================================================
 * Extiende Authenticatable (en lugar de Model) para que Laravel pueda
 * usar instancias de Estudiante como usuarios en el guard 'estudiante'.
 *
 * AUTENTICACIÓN INFANTIL (RF-01):
 *   - NO usa email (los niños de 6-9 años no tienen email)
 *   - codigo_acceso: código imprimible entregado por el docente (SAN-1A-007)
 *   - pin_hash: PIN de 4 dígitos hasheado con bcrypt
 *
 * PRIVACIDAD (RN-01 / UNICEF 2021):
 *   - id_anonimizado (UUID v4): el ÚNICO dato que sale hacia la API Claude
 *   - Nunca se expone nombre, código ni PIN a servicios externos
 *
 * CICLO DE VIDA (RF-18 / RN-10):
 *   - fecha_baja_programada: calculada por MatriculaService al cerrar 3° grado
 *   - EgresadoCleanupJob desactiva la cuenta cuando llega esa fecha
 *
 * Referencia: Tabla 3 de 21 — Sección 1.3.9 TFG
 * RF: RF-01, RF-07, RF-14, RF-18
 * RN: RN-01, RN-10
 * =========================================================================
 */
class Estudiante extends Authenticatable
{
    // ── Configuración del modelo ──────────────────────────────────────────

    /**
     * Nombre de la columna que identifica al usuario en la sesión de Laravel.
     * Por defecto Laravel usa 'id' y eso es correcto — no cambiar.
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'codigo_acceso',
        'email_tutor',       // correo del padre/madre/tutor (acceso de familias)
        'pin_hash',
        'id_anonimizado',
        'activo',
        'fecha_baja_programada',
    ];

    /**
     * Campos que NUNCA se incluyen al serializar (JSON, toArray).
     * Protege el hash del PIN y el UUID anonimizador.
     */
    protected $hidden = [
        'pin_hash',
        'id_anonimizado',    // Solo para uso interno del sistema
    ];

    protected $casts = [
        'activo'                => 'boolean',
        'fecha_nacimiento'      => 'date',
        'fecha_baja_programada' => 'date',
    ];

    // ── Métodos requeridos por Authenticatable ────────────────────────────

    /**
     * Devuelve el nombre de la columna de la contraseña.
     * Requerido por el contrato Authenticatable de Laravel.
     * Nuestro campo se llama 'pin_hash' en lugar del estándar 'password'.
     */
    public function getAuthPassword(): string
    {
        return $this->pin_hash;
    }

    // ── Factory methods ───────────────────────────────────────────────────

    /**
     * Genera un UUID v4 único para id_anonimizado.
     * Llamar SIEMPRE al crear un estudiante nuevo.
     * El UUID es el único identificador enviado a la API Claude (RN-01).
     */
    public static function generarIdAnonimizado(): string
    {
        do {
            $uuid = Str::uuid()->toString();
        } while (self::where('id_anonimizado', $uuid)->exists());

        return $uuid;
    }

    /**
     * Hashea el PIN numérico de 4 dígitos con bcrypt.
     * Valida el formato antes de hashear.
     *
     * @throws \InvalidArgumentException si el PIN no tiene exactamente 4 dígitos
     */
    public static function hashearPin(string $pin): string
    {
        if (!preg_match('/^\d{4}$/', $pin)) {
            throw new \InvalidArgumentException(
                'El PIN debe ser exactamente 4 dígitos numéricos (0-9).'
            );
        }
        return Hash::make($pin);
    }

    /**
     * Verifica si el PIN ingresado coincide con el hash almacenado.
     * Usado por EstudianteProvider::validateCredentials().
     */
    public function verificarPin(string $pinIngresado): bool
    {
        return Hash::check($pinIngresado, $this->pin_hash);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /** Nombre completo formateado: "Apellido, Nombre" */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellido}, {$this->nombre}";
    }

    /** ¿Tiene fecha de baja programada? */
    public function tieneBajaProgramada(): bool
    {
        return $this->fecha_baja_programada !== null;
    }

    /** ¿La fecha de baja ya llegó o pasó? (candidato para cleanup) */
    public function bajaVencida(): bool
    {
        return $this->tieneBajaProgramada()
            && $this->fecha_baja_programada->isPast();
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'estudiante_id');
    }

    public function matriculaActiva()
    {
        return $this->hasOne(Matricula::class, 'estudiante_id')
                    ->where('activo', true)
                    ->latestOfMany();
    }

    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoEstudiante::class, 'estudiante_id');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionJuego::class, 'estudiante_id');
    }

    public function logros(): HasMany
    {
        return $this->hasMany(LogroObtenido::class, 'estudiante_id');
    }

    public function rachas(): HasMany
    {
        return $this->hasMany(Racha::class, 'estudiante_id');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'estudiante_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /** Solo estudiantes con cuenta activa. */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Estudiantes cuya fecha_baja_programada ya venció.
     * EgresadoCleanupJob usa este scope diariamente (RF-18).
     */
    public function scopeBajaVencida($query)
    {
        return $query->where('activo', true)
                     ->whereNotNull('fecha_baja_programada')
                     ->whereDate('fecha_baja_programada', '<=', now());
    }
}
