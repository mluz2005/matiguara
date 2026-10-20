<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: ConfigInstitucion
 * -------------------------------------------------------------------------
 * Configuración operativa singleton por institución (RF-16).
 * UNIQUE(institucion_id) garantiza que exista exactamente un registro.
 *
 * Se crea automáticamente con valores por defecto al registrar
 * una institución nueva (CU-27 / MatriculaService).
 * La Administradora puede modificarla mediante CU-25.
 *
 * Referencia: Tabla 21 de 21 — Sección 1.3.9 ★ NUEVA v1.2
 * RF relacionados: RF-16, RF-17, RF-18
 * RN relacionadas: RN-08, RN-10
 * -------------------------------------------------------------------------
 */
class ConfigInstitucion extends Model
{
    protected $table = 'config_institucion';

    protected $fillable = [
        'institucion_id',
        'dias_reinicio_racha_default',
        'permitir_acceso_grados_ant',
        'meses_conservacion_egresados',
    ];

    protected $casts = [
        'permitir_acceso_grados_ant' => 'boolean',
    ];

    /**
     * Valores por defecto al crear la configuración.
     * Se aplican automáticamente cuando se registra una institución nueva.
     */
    protected $attributes = [
        'dias_reinicio_racha_default'    => 3,
        'permitir_acceso_grados_ant'     => false,
        'meses_conservacion_egresados'   => 4,
    ];

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    /**
     * Crea una configuración por defecto para una institución.
     * Llamar desde InstitucionController al registrar una institución nueva (CU-27).
     *
     * @param  int  $institucionId
     * @return self
     */
    public static function crearPorDefecto(int $institucionId): self
    {
        return self::create(['institucion_id' => $institucionId]);
        // Los valores por defecto se toman de $attributes.
    }
}
