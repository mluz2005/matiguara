<?php

namespace App\Console\Commands;

use App\Models\DetalleNumeros;
use App\Models\RespuestaNumeros;
use Illuminate\Console\Command;

/**
 * COMANDO: numeros:exportar — datos de «Mati y los números» en CSV (anónimos)
 * =========================================================================
 *   php artisan numeros:exportar                    → storage/app/numeros_export_AAAAMMDD_HHMM.csv
 *   php artisan numeros:exportar --ruta=/tmp/n.csv  → en la ruta indicada
 *   php artisan numeros:exportar --solo-completos   → solo viajes completos y válidos
 *
 * Una fila por pregunta respondida. El niño se identifica SOLO con su id_anonimizado
 * (el UUID que ya usa el diagnóstico con IA): no incluye nombre, código de acceso,
 * escuela ni correo. Sirve para revisar los datos del piloto antes de que existan los
 * informes de la etapa 2 y para analizarlos fuera del sistema (planilla, R, Python).
 * =========================================================================
 */
class ExportarNumeros extends Command
{
    protected $signature = 'numeros:exportar {--ruta= : Archivo CSV de salida} {--solo-completos : Solo viajes completos y válidos}';

    protected $description = 'Exporta a CSV (anónimo) las respuestas de «Mati y los números»';

    private const COLUMNAS = [
        'id_anonimizado', 'grado', 'ciclo', 'viaje', 'fecha', 'dispositivo', 'viaje_completo', 'viaje_confiable', 'viaje_valido',
        'pct_azar', 'tiempo_base_ms', 'parte', 'orden', 'area', 'nivel', 'tipo_pregunta', 'item', 'respuesta', 'es_correcto',
        'tipo_error', 'tiempo_ms', 'azar', 'error_linea_pct', 'cero',
    ];

    public function handle(): int
    {
        $ruta = $this->option('ruta') ?: storage_path('app/numeros_export_' . now()->format('Ymd_Hi') . '.csv');
        $fh = fopen($ruta, 'w');
        if ($fh === false) {
            $this->error("No se pudo escribir en $ruta");
            return self::FAILURE;
        }
        fwrite($fh, "\xEF\xBB\xBF");                 // BOM: Excel abre bien los acentos
        fputcsv($fh, self::COLUMNAS, ';');

        $consulta = DetalleNumeros::with(['sesion.estudiante:id,id_anonimizado'])->orderBy('id');
        if ($this->option('solo-completos')) {
            $consulta->where('completa', true)->where('valida', true);
        }

        $filas = 0;
        $consulta->chunkById(100, function ($detalles) use ($fh, &$filas) {
            foreach ($detalles as $d) {
                $anon = $d->sesion?->estudiante?->id_anonimizado ?? '';
                $respuestas = RespuestaNumeros::where('sesion_id', $d->sesion_id)->orderBy('id')->get();
                foreach ($respuestas as $r) {
                    $item = $d->partes_json[(string) $r->parte]['items'][$r->orden] ?? [];
                    fputcsv($fh, [
                        $anon, $d->grado, $d->ciclo, $d->viaje, $d->sesion?->fecha_inicio?->format('Y-m-d H:i'), $d->sesion?->device_tipo,
                        (int) $d->completa, (int) $d->confiable, (int) $d->valida, $d->pct_azar, $d->tiempo_base_ms,
                        $r->parte, $r->orden, $r->area, $r->nivel, $item['tipo'] ?? '', $r->item_clave, $r->respuesta, (int) $r->es_correcto,
                        $r->tipo_error, $r->tiempo_ms, (int) $r->azar, $r->error_pct, (int) $r->cero,
                    ], ';');
                    $filas++;
                }
            }
        });

        fclose($fh);
        $this->info("✅ $filas respuestas exportadas a $ruta");
        return self::SUCCESS;
    }
}
