<?php

namespace Tests\Feature\Repositories;

use App\Models\Racha;
use App\Repositories\EstudianteRepository;
use App\Services\LogroService;

class LogroServiceTest extends RepositorioTestCase
{
    private function racha(int $aciertos = 0, int $dias = 0): Racha
    {
        return new Racha(['racha_actual_aciertos' => $aciertos, 'racha_actual_dias' => $dias]);
    }

    private function nombres($coleccion): array
    {
        return $coleccion->map(fn ($lo) => $lo->logro->nombre_es)->sort()->values()->all();
    }

    public function test_otorga_logros_de_racha_y_de_primera_partida_y_no_los_repite()
    {
        $e      = $this->escenario();
        $srv    = app(LogroService::class);
        $sesion = $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => false]);

        $nuevos = $srv->evaluar($sesion, $this->racha(3));
        $nombres = $this->nombres($nuevos);

        $this->assertContains('Primera Racha', $nombres);
        $this->assertContains('Primer Paso', $nombres);
        $this->assertNotContains('Imparable', $nombres);
        $this->assertNotContains('Nivel Conquistado', $nombres, 'No superó el nivel.');

        $this->assertCount(0, $srv->evaluar($sesion, $this->racha(3)), 'Un logro ya otorgado no se repite.');
    }

    public function test_nivel_superado_cuenta_niveles_distintos()
    {
        $e   = $this->escenario();
        $srv = app(LogroService::class);

        $s1 = $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => true]);
        $this->assertContains('Nivel Conquistado', $this->nombres($srv->evaluar($s1, $this->racha())));

        // Superar el MISMO nivel otra vez no suma: "Explorador" pide 5 niveles distintos.
        $s2 = $this->nuevaSesion($e, ['estado' => 'completada', 'nivel_superado' => true]);
        $this->assertNotContains('Explorador', $this->nombres($srv->evaluar($s2, $this->racha())));
    }

    public function test_dias_consecutivos_otorga_semana_completa()
    {
        $e      = $this->escenario();
        $sesion = $this->nuevaSesion($e, ['estado' => 'completada']);

        $nombres = $this->nombres(app(LogroService::class)->evaluar($sesion, $this->racha(0, 7)));

        $this->assertContains('Semana Completa', $nombres);
        $this->assertNotContains('Leyenda', $nombres);
    }

    public function test_flash_master_solo_al_superar_el_ultimo_nivel_de_flash_matematico()
    {
        $e   = $this->escenario();
        $srv = app(LogroService::class);

        $flash = \App\Models\Juego::where('nombre_es', 'Flash Matemático')->firstOrFail();
        $ultimo = \App\Models\Nivel::where('juego_id', $flash->id)->orderBy('grado')->orderByDesc('numero_nivel')->firstOrFail();
        // Último nivel del grado (el que no tiene siguiente)
        $this->assertNull($ultimo->nivelSiguiente());
        $primero = \App\Models\Nivel::where('juego_id', $flash->id)->where('grado', $ultimo->grado)->orderBy('numero_nivel')->firstOrFail();

        $base = ['estudiante_id' => $e['estudiante']->id, 'juego_id' => $flash->id, 'estado' => 'completada', 'nivel_superado' => true];

        $sPrimero = app(\App\Repositories\SesionRepository::class)->crear($base + ['nivel_id' => $primero->id, 'fecha_inicio' => now()]);
        $this->assertNotContains('Flash Master', $this->nombres($srv->evaluar($sPrimero, $this->racha())));

        $sUltimo = app(\App\Repositories\SesionRepository::class)->crear($base + ['nivel_id' => $ultimo->id, 'fecha_inicio' => now()]);
        $this->assertContains('Flash Master', $this->nombres($srv->evaluar($sUltimo, $this->racha())));
    }
}
