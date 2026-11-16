<?php

namespace Tests\Feature\Repositories;

use App\Models\Usuario;

/**
 * La pantalla Ayuda de la docente muestra la guía completa de «Mati y los números».
 */
class AyudaNumerosTest extends RepositorioTestCase
{
    public function test_la_ayuda_de_la_docente_incluye_la_guia_de_mati_y_los_numeros(): void
    {
        $docente = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();

        $r = $this->actingAs($docente)->get('/ayuda');

        $r->assertOk()
            ->assertSee('Atención numérica · «Mati y los números»', false)
            ->assertSee('Qué es y cómo se usa')
            ->assertSee('Qué hacen los niños en cada viaje')
            ->assertSee('Cómo leer el semáforo')
            ->assertSee('Cómo se calcula el color')
            ->assertSee('Marcar un viaje como no válido')
            ->assertSee('Informe con ayuda de la IA')
            ->assertSee('Qué hacer cuando un niño sale amarillo o rojo')
            ->assertSee('Dudas de Atención numérica')
            ->assertSee('id="numeros-semaforo"', false)
            ->assertSee('id="atencion-numerica"', false);
    }

    public function test_la_ayuda_de_las_familias_no_menciona_el_semaforo(): void
    {
        $padre = Usuario::whereHas('padre')->first();
        if (!$padre) {
            $this->markTestSkipped('No hay un usuario de familia en los datos de prueba.');
        }

        $this->actingAs($padre)->get('/ayuda')
            ->assertOk()
            ->assertDontSee('semáforo')
            ->assertDontSee('Atención numérica');
    }
}
