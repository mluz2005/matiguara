<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODELO: Juego
 * -------------------------------------------------------------------------
 * Juego educativo bilingüe (castellano + guaraní) disponible en el sistema.
 * Cada juego tiene niveles propios organizados por grado (1°, 2°, 3°).
 *
 * CONFIGURACIÓN DE RACHA (RN-08):
 * Si dias_reinicio_racha es NULL, se lee el default de la institución.
 * SistemaAdaptativoService::obtenerDiasRacha() implementa esta lógica.
 *
 * Referencia: Tabla 9 de 21 — Sección 1.3.9
 * RF relacionados: RF-02, RF-03, RF-05
 * RN relacionadas: RN-06, RN-08
 * -------------------------------------------------------------------------
 */
class Juego extends Model
{
    protected $table = 'juegos';
    protected $fillable = [
        'tipo_mecanica_id', 'nombre_es', 'nombre_gn',
        'instrucciones_es', 'instrucciones_gn',
        'activo', 'dias_reinicio_racha',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function tipoMecanica(): BelongsTo
    {
        return $this->belongsTo(TipoMecanica::class, 'tipo_mecanica_id');
    }

    public function niveles(): HasMany
    {
        return $this->hasMany(Nivel::class, 'juego_id');
    }

    /**
     * Niveles del juego filtrados por grado específico.
     * Uso: $juego->nivelesPorGrado(1)->orderBy('numero_nivel')->get()
     */
    public function nivelesPorGrado(int $grado): HasMany
    {
        return $this->niveles()->where('grado', $grado);
    }

    public function configuracionesDocente(): HasMany
    {
        return $this->hasMany(ConfigDocenteJuego::class, 'juego_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
