<?php

namespace Tests\Feature\Repositories;

use App\Models\Nivel;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Support\DesbloqueoNiveles;

class DesbloqueoNivelesTest extends RepositorioTestCase
{
    /** Un nivel cerrado por defecto y su anterior, dentro del mismo juego y grado. */
    private function parCerradoYAnterior(array $e): array
    {
        $cerrado = Nivel::where('juego_id', $e['juego']->id)
            ->where('grado', $e['nivel1']->grado)
            ->where('desbloqueado_por_defecto', false)
            ->orderBy('numero_nivel')
            ->firstOrFail();
        $anterior = app(EjercicioRepository::class)->nivelAnterior($cerrado);
        $this->assertNotNull($anterior, 'El nivel cerrado necesita un nivel anterior.');

        return [$cerrado, $anterior];
    }

    public function test_un_estudiante_nuevo_solo_tiene_abiertos_los_niveles_por_defecto()
    {
        $e = $this->escenario();
        [$cerrado] = $this->parCerradoYAnterior($e);

        $abiertos = DesbloqueoNiveles::idsAbiertos($e['estudiante']->id);

        $this->assertNotContains($cerrado->id, $abiertos);
        $porDefecto = Nivel::where('desbloqueado_por_defecto', true)->pluck('id')->all();
        foreach ($porDefecto as $id) {
            $this->assertContains($id, $abiertos);
        }
        $this->assertFalse(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $cerrado));
    }

    public function test_abrir_deja_el_nivel_abierto_via_progreso()
    {
        $e = $this->escenario();
        [$cerrado] = $this->parCerradoYAnterior($e);

        DesbloqueoNiveles::abrir($e['estudiante']->id, $cerrado);

        $this->assertTrue(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $cerrado));
        $this->assertContains($cerrado->id, app(EstudianteRepository::class)->idsNivelesDesbloqueados($e['estudiante']->id)->all());
    }

    public function test_haber_jugado_un_nivel_lo_deja_abierto()
    {
        $e = $this->escenario();
        [$cerrado] = $this->parCerradoYAnterior($e);

        $this->nuevaSesion($e, ['nivel_id' => $cerrado->id, 'estado' => 'abandonada']);

        $this->assertTrue(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $cerrado));
    }

    public function test_superar_el_nivel_anterior_abre_el_siguiente_sin_registro_de_progreso()
    {
        $e = $this->escenario();
        [$cerrado, $anterior] = $this->parCerradoYAnterior($e);

        $this->nuevaSesion($e, ['nivel_id' => $anterior->id, 'estado' => 'completada', 'nivel_superado' => true]);

        $this->assertTrue(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $cerrado));
    }

    public function test_jugar_el_anterior_sin_superarlo_no_abre_el_siguiente()
    {
        $e = $this->escenario();
        [$cerrado, $anterior] = $this->parCerradoYAnterior($e);

        $this->nuevaSesion($e, ['nivel_id' => $anterior->id, 'estado' => 'completada', 'nivel_superado' => false]);

        $this->assertFalse(DesbloqueoNiveles::estaAbierto($e['estudiante']->id, $cerrado));
    }

    public function test_resumen_por_juego_marca_superado_abierto_y_cerrado()
    {
        $e = $this->escenario();
        [$cerrado, $anterior] = $this->parCerradoYAnterior($e);
        $this->nuevaSesion($e, ['nivel_id' => $anterior->id, 'estado' => 'completada', 'nivel_superado' => true]);

        $resumen = DesbloqueoNiveles::resumenPorJuego($e['estudiante']->id, $e['nivel1']->grado);

        $entrada = collect($resumen)->first(fn ($r) => $r['juego']->id === $e['juego']->id);
        $this->assertNotNull($entrada);

        $estados = collect($entrada['niveles'])->mapWithKeys(fn ($f) => [$f['nivel']->id => $f['estado']]);
        $this->assertSame('superado', $estados[$anterior->id]);
        $this->assertSame('abierto', $estados[$cerrado->id]);

        // Los niveles más allá del recién abierto siguen cerrados (si existen y no son por defecto)
        $lejano = Nivel::where('juego_id', $e['juego']->id)
            ->where('grado', $e['nivel1']->grado)
            ->where('desbloqueado_por_defecto', false)
            ->where('numero_nivel', '>', $cerrado->numero_nivel)
            ->orderBy('numero_nivel')
            ->first();
        if ($lejano) {
            $this->assertSame('cerrado', $estados[$lejano->id]);
        }
    }
}
