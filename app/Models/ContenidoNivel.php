<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: ContenidoNivel
 * -------------------------------------------------------------------------
 * Ejercicio individual dentro de un nivel. Granularidad mínima del contenido.
 * El diagnóstico IA analiza los movimientos_sesion referenciados a este modelo.
 *
 * Referencia: Tabla 11 de 21 — Sección 1.3.9
 * RF relacionados: RF-02, RF-05, RF-06
 * -------------------------------------------------------------------------
 */
class ContenidoNivel extends Model
{
    public $timestamps = false;

    protected $table = 'contenido_nivel';

    protected $fillable = [
        'nivel_id', 'tipo_operacion', 'enunciado_es', 'enunciado_gn',
        'valor_a', 'operador', 'valor_b', 'respuesta_correcta',
        'opciones_json', 'orden', 'audio_es', 'audio_gn',
    ];

    protected $casts = [
        'opciones_json' => 'array',  // Laravel deserializa JSON automáticamente
    ];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    /**
     * Verifica si una respuesta dada es correcta.
     * Usa comparación flexible (trim + lowercase) para robustez.
     */
    public function esRespuestaCorrecta(string $respuesta): bool
    {
        return strtolower(trim($respuesta))
            === strtolower(trim($this->respuesta_correcta));
    }
}
