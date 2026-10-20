<?php

namespace Tests\Feature\Repositories;

use App\Models\Juego;
use App\Models\Nivel;

/**
 * Los sembradores guardan tiempo_mostrar_ms (Flash Matemático) y
 * tiempo_limite_seg (Encuentra el par) con Nivel::create(). Estas columnas
 * no estaban en $fillable, por lo que Eloquent las descartaba en silencio y,
 * en una instalación nueva, los niveles quedaban sin esos tiempos.
 */
class NivelTiemposTest extends RepositorioTestCase
{
    public function test_flash_matematico_guarda_el_tiempo_de_exposicion_de_los_operandos()
    {
        $flash = Juego::where('nombre_es', 'Flash Matemático')->firstOrFail();

        $conTiempo = Nivel::where('juego_id', $flash->id)->whereNotNull('tiempo_mostrar_ms')->pluck('tiempo_mostrar_ms')->all();

        $this->assertNotEmpty($conTiempo);
        $this->assertContains(8000, array_map('intval', $conTiempo));
    }

    public function test_encuentra_el_par_guarda_el_tiempo_limite_de_cada_nivel()
    {
        $e = $this->escenarioMemoria();

        $niveles = Nivel::where('juego_id', $e['juego']->id)->get();

        $this->assertCount(10, $niveles);
        $this->assertTrue($niveles->every(fn ($n) => $n->tiempo_limite_seg !== null && (int) $n->tiempo_limite_seg > 0));
    }
}
