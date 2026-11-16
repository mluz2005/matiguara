<?php

namespace App\Console\Commands;

use App\Models\DetalleNumeros;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\RespuestaNumeros;
use App\Models\SesionJuego;
use App\Models\TamizajeAviso;
use App\Models\TamizajeEstado;
use App\Services\NumerosService;
use App\Services\TamizajeService;
use App\Support\Numeros\GeneradorItems;
use App\Support\Numeros\TextosTamizaje;
use Database\Seeders\MatiNumerosSeeder;
use Illuminate\Console\Command;

/**
 * COMANDO: numeros:demo — datos de PRUEBA para ver el semáforo sin jugar seis días
 * =========================================================================
 *   php artisan numeros:demo SAN-1A-001 --perfil=amarillo           → crea 2 ciclos de prueba y calcula el semáforo
 *   php artisan numeros:demo SAN-1A-001 --perfil=rojo --ciclos=3
 *   php artisan numeros:demo SAN-1A-001 --limpiar                   → borra lo de prueba de ese alumno
 *
 * Solo para probar las pantallas de la docente con un alumno DE PRUEBA. Los viajes quedan marcados
 * como «demo» (dispositivo) para poder borrarlos. No se ejecuta en producción salvo con --forzar.
 * Perfiles: verde (todo bien), amarillo (sentido de la cantidad ~68 %), rojo (sentido ~40 % y signos ~55 %).
 * =========================================================================
 */
class DemoNumeros extends Command
{
    protected $signature = 'numeros:demo {codigo : Código de acceso del alumno de prueba}
                            {--perfil=amarillo : verde, amarillo o rojo}
                            {--ciclos=2 : Ciclos completos a crear (1 a 6)}
                            {--limpiar : Borra los datos de demostración del alumno}
                            {--forzar : Permite correrlo en producción}';

    protected $description = 'Crea (o borra) viajes de prueba de «Mati y los números» para ver el semáforo';

    private const MARCA = 'demo';
    private const BASE  = 90;
    private const PERFILES = [
        'verde'    => [],
        'amarillo' => ['sentido_cantidad' => 68],
        'rojo'     => ['sentido_cantidad' => 40, 'signos_calculo' => 55],
    ];

    public function handle(TamizajeService $tamizaje): int
    {
        if (app()->environment('production') && !$this->option('forzar')) {
            $this->error('Este comando crea datos de prueba y no corre en producción (usá --forzar si estás seguro).');
            return self::FAILURE;
        }

        $codigo = strtoupper(trim((string) $this->argument('codigo')));
        $est = Estudiante::where('codigo_acceso', $codigo)->first();
        if (!$est) {
            $this->error("No existe un alumno con el código {$codigo}.");
            return self::FAILURE;
        }

        if ($this->option('limpiar')) {
            $borrados = SesionJuego::where('estudiante_id', $est->id)->where('device_tipo', self::MARCA)->delete();
            TamizajeAviso::where('estudiante_id', $est->id)->delete();
            TamizajeEstado::where('estudiante_id', $est->id)->delete();
            $tamizaje->actualizar($est->id);
            $this->info("Listo: {$borrados} viajes de prueba borrados y el semáforo de {$est->nombre} recalculado.");
            return self::SUCCESS;
        }

        $perfil = (string) $this->option('perfil');
        if (!array_key_exists($perfil, self::PERFILES)) {
            $this->error('El perfil debe ser verde, amarillo o rojo.');
            return self::FAILURE;
        }
        $ciclos = (int) $this->option('ciclos');
        if ($ciclos < 1 || $ciclos > 6) {
            $this->error('Los ciclos deben ser de 1 a 6.');
            return self::FAILURE;
        }

        $juego = Juego::where('nombre_es', MatiNumerosSeeder::NOMBRE_ES)->first();
        $grado = (int) ($est->matriculaActiva?->grado ?? 0);
        if (!$juego || !$grado) {
            $this->error('Falta el juego (php artisan db:seed --class=MatiNumerosSeeder) o el alumno no tiene matrícula activa.');
            return self::FAILURE;
        }

        $desde = (int) DetalleNumeros::whereHas('sesion', fn ($q) => $q->where('estudiante_id', $est->id))->max('ciclo');
        $total = $ciclos * 3;
        $i = 0;
        foreach (range($desde + 1, $desde + $ciclos) as $ciclo) {
            foreach ([1, 2, 3] as $viaje) {
                $i++;
                $this->crearViaje($est, $juego, $grado, $ciclo, $viaje, self::PERFILES[$perfil], now()->subDays($total - $i + 1)->setTime(10, 0));
            }
        }

        $res = $tamizaje->actualizar($est->id)['resultado'] ?? null;
        $this->info("Creados {$total} viajes de prueba ({$ciclos} ciclos, perfil {$perfil}) para {$est->nombre} {$est->apellido}.");
        if ($res) {
            $this->line('Semáforo: ' . TextosTamizaje::semaforo($res['semaforo'])['etiqueta']);
            foreach (TextosTamizaje::AREAS as $clave => $nombre) {
                $p = $res['areas'][$clave]['pct'];
                $this->line("  {$nombre}: " . ($p !== null ? "{$p} %" : 'sin datos suficientes'));
            }
        }
        $this->line('Para borrarlos: php artisan numeros:demo ' . $codigo . ' --limpiar');

        return self::SUCCESS;
    }

    private function crearViaje(Estudiante $est, Juego $juego, int $grado, int $ciclo, int $viaje, array $pctPorArea, $inicio): void
    {
        $nivel = Nivel::where('juego_id', $juego->id)->where('grado', $grado)->where('numero_nivel', $viaje)->firstOrFail();
        $svc   = app(NumerosService::class);

        $sesion = SesionJuego::create([
            'estudiante_id'  => $est->id,
            'juego_id'       => $juego->id,
            'nivel_id'       => $nivel->id,
            'fecha_inicio'   => $inicio,
            'fecha_fin'      => $inicio->copy()->addMinutes(4),
            'estado'         => 'completada',
            'nivel_superado' => false,
            'device_tipo'    => self::MARCA,
        ]);

        $semilla = random_int(1000, 900000000);
        $partes = [];
        $filas  = [];
        $contador = [];

        foreach (GeneradorItems::partesDelViaje($viaje) as $parte) {
            $items = GeneradorItems::parte($grado, $parte, $ciclo, GeneradorItems::ESPERADO, $semilla);
            $partes[(string) $parte] = ['nivel' => GeneradorItems::ESPERADO, 'items' => $items];

            foreach ($items as $orden => $item) {
                $area = $item['area'];
                $contador[$area] = ($contador[$area] ?? 0) + 1;
                $pct  = $pctPorArea[$area] ?? self::BASE;
                $bien = $parte === 1 || ((($contador[$area] * 37) % 100) < $pct);

                $respuesta = $bien ? $this->correcta($item) : $this->incorrecta($item);
                $errorPct  = null;
                if ($item['tipo'] === 'linea') {
                    $errorPct = round(abs((float) $respuesta - (int) $item['datos']['objetivo']) / max(1, (int) $item['datos']['max']) * 100, 1);
                }

                $filas[] = [
                    'sesion_id'     => $sesion->id,
                    'parte'         => $parte,
                    'orden'         => $orden,
                    'item_clave'    => mb_substr((string) $item['clave'], 0, 60),
                    'area'          => $area,
                    'nivel'         => GeneradorItems::ESPERADO,
                    'fija'          => (int) $item['fija'],
                    'cero'          => (int) $item['cero'],
                    'respuesta'     => mb_substr($respuesta, 0, 24),
                    'es_correcto'   => (int) $bien,
                    'tipo_error'    => $bien ? null : $svc->clasificarError($item, $respuesta),
                    'tiempo_ms'     => random_int(1500, 4500),
                    'azar'          => 0,
                    'error_pct'     => $errorPct,
                    'respondido_en' => $inicio,
                ];
            }
        }

        RespuestaNumeros::insert($filas);

        DetalleNumeros::create([
            'sesion_id'       => $sesion->id,
            'viaje'           => $viaje,
            'ciclo'           => $ciclo,
            'semilla'         => $semilla,
            'grado'           => $grado,
            'partes_json'     => $partes,
            'completa'        => true,
            'confiable'       => true,
            'valida'          => true,
            'pct_azar'        => 0,
            'tiempo_base_ms'  => 700,
            'nivel_final'     => GeneradorItems::ESPERADO,
            'duracion_s'      => 240,
            'respondidas'     => count($filas),
            'total_preguntas' => count($filas),
        ]);
    }

    private function correcta(array $item): string
    {
        return $item['tipo'] === 'linea' ? (string) $item['datos']['objetivo'] : (string) $item['correcta'];
    }

    private function incorrecta(array $item): string
    {
        switch ($item['tipo']) {
            case 'toque':
                return 'toque';
            case 'linea':
                $o = (int) $item['datos']['objetivo'];
                $max = (int) $item['datos']['max'];
                $desvio = (int) ceil($max * 0.5);
                return (string) ($o + $desvio > $max ? $o - $desvio : $o + $desvio);
            case 'mas_puntos':
            case 'mayor':
                return $item['correcta'] === 'izq' ? 'der' : 'izq';
            case 'signo':
                return $item['correcta'] === '+' ? '-' : '+';
            case 'puntos_rapidos':
                return (string) (((int) $item['correcta'] + 2) % ((int) $item['datos']['max'] + 1));
            default:
                foreach ($item['opciones'] as $o) {
                    $v = (string) (is_array($o) ? $o['valor'] : $o);
                    if ($v !== (string) $item['correcta']) {
                        return $v;
                    }
                }
        }
        return '0';
    }
}
