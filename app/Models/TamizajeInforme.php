<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO: TamizajeInforme — informe de «Atención numérica» redactado con ayuda de la IA (o con la
 * plantilla de respaldo). Es un BORRADOR de apoyo para la docente, no un diagnóstico.
 * El texto para la familia guarda el marcador {nombre}: se reemplaza al mostrarlo, aquí (y en la IA) no hay nombres.
 */
class TamizajeInforme extends Model
{
    protected $table = 'tamizaje_informes';

    protected $fillable = [
        'estudiante_id', 'docente_id', 'id_anonimizado', 'semaforo', 'ciclos',
        'lectura', 'actividades', 'texto_familia',
        'origen', 'motivo_respaldo', 'datos_enviados', 'tokens_usados', 'generado_en',
    ];

    protected $casts = [
        'datos_enviados' => 'array',
        'generado_en'    => 'datetime',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function esDeLaIA(): bool
    {
        return $this->origen === 'ia';
    }

    /** El texto para la familia con el nombre de pila puesto (el nombre nunca viaja a la IA). */
    public function textoFamiliaPara(string $nombre): string
    {
        return str_replace('{nombre}', $nombre, $this->texto_familia);
    }
}
