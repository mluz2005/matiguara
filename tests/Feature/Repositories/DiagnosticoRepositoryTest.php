<?php

namespace Tests\Feature\Repositories;

use App\Models\Docente;
use App\Repositories\DiagnosticoRepository;

class DiagnosticoRepositoryTest extends RepositorioTestCase
{
    public function test_crear_guarda_el_diagnostico_con_el_uuid_anonimizado()
    {
        $e       = $this->escenario();
        $docente = Docente::first();

        $d = app(DiagnosticoRepository::class)->crear([
            'estudiante_id'       => $e['estudiante']->id,
            'docente_id'          => $docente->id,
            'id_anonimizado'      => $e['estudiante']->id_anonimizado,
            'fecha_generacion'    => now(),
            'puntaje_promedio'    => 12.5,
            'porcentaje_aciertos' => 80.0,
            'nivel_max_alcanzado' => 2,
            'fortalezas'          => 'Suma de una cifra.',
            'dificultades'        => 'Cálculo con resta.',
            'recomendacion'       => 'Practicar restas con apoyo visual.',
            'tokens_usados'       => null,
            'estado'              => 'exitoso',
        ]);

        $this->assertDatabaseHas('diagnosticos', [
            'id'             => $d->id,
            'estudiante_id'  => $e['estudiante']->id,
            'id_anonimizado' => $e['estudiante']->id_anonimizado,
            'estado'         => 'exitoso',
        ]);
        $this->assertTrue($d->esExitoso());
    }
}
