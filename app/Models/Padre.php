<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: Padre
 * -------------------------------------------------------------------------
 * Vínculo entre un usuario adulto (perfil='padre') y un estudiante.
 * El padre solo accede al progreso de su estudiante vinculado (RN-04).
 *
 * Referencia: Tabla 4 de 21 — Sección 1.3.9
 * RN relacionadas: RN-04
 * -------------------------------------------------------------------------
 */
class Padre extends Model
{
    protected $table = 'padres';
    public $timestamps = false; // Solo tiene created_at

    protected $fillable = [
        'usuario_id', 'estudiante_id', 'relacion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
