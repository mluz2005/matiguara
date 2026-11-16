<?php

namespace Tests\Feature\Repositories;

use App\Models\AsignacionDocente;
use App\Models\DetalleNumeros;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Juego;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Models\RespuestaNumeros;
use App\Models\TamizajeInforme;
use App\Models\Usuario;
use App\Repositories\SesionRepository;
use App\Services\TamizajeInformeService;
use App\Services\TamizajeService;
use Database\Seeders\MatiNumerosSeeder;
use Illuminate\Support\Facades\Http;

/**
 * «Mati y los números» — Etapa 3: informe con ayuda de la IA.
 * La API de Claude se simula con Http::fake (nunca se llama a la real en los tests).
 */
class TamizajeEtapa3Test extends RepositorioTestCase
{
    private const PARTES = [
        1 => [2 => 'numeros_cantidades', 3 => 'numeros_cantidades', 5 => 'numeros_cantidades'],
        2 => [4 => 'sentido_cantidad', 6 => 'sentido_cantidad', 7 => 'sentido_cantidad'],
        3 => [8 => 'signos_calculo', 9 => 'signos_calculo', 10 => 'numeros_cantidades'],
    ];

    private const NOTA_SECRETA = 'NOTA_SECRETA_XYZ_123';

    private Usuario $usuario;
    private Docente $docente;
    private Juego $juego;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MatiNumerosSeeder::class);
        $this->juego = Juego::where('nombre_es', MatiNumerosSeeder::NOMBRE_ES)->firstOrFail();

        $this->usuario = Usuario::where('email', 'docente1@unae.edu.py')->firstOrFail();
        $this->docente = Docente::where('usuario_id', $this->usuario->id)->firstOrFail();

        Matricula::query()->update(['anio_lectivo' => date('Y')]);
        AsignacionDocente::query()->update(['anio_lectivo' => date('Y')]);

        config(['services.claude.key' => 'clave-de-prueba']);
        $this->actingAs($this->usuario);
    }

    // ── Ayudas ───────────────────────────────────────────────────────────

    private function alumnoPropio(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    private function alumnoAjeno(): Estudiante
    {
        return Estudiante::findOrFail(Matricula::where('docente_id', '!=', $this->docente->id)->orderBy('id')->value('estudiante_id'));
    }

    private function viajeHecho(Estudiante $est, int $ciclo, int $viaje, array $pct = []): void
    {
        $grado = (int) $est->matriculaActiva->grado;
        $nivel = Nivel::where('juego_id', $this->juego->id)->where('grado', $grado)->where('numero_nivel', $viaje)->firstOrFail();

        $sesion = app(SesionRepository::class)->crear([
            'estudiante_id' => $est->id, 'juego_id' => $this->juego->id, 'nivel_id' => $nivel->id,
            'fecha_inicio' => now()->subMinutes(5), 'fecha_fin' => now(), 'estado' => 'completada',
        ]);

        $filas = [];
        foreach (self::PARTES[$viaje] as $parte => $area) {
            $p = $pct[$area] ?? 100;
            for ($i = 0; $i < 10; $i++) {
                $bien = ($i * 10) < $p;
                $filas[] = [
                    'sesion_id' => $sesion->id, 'parte' => $parte, 'orden' => $i, 'item_clave' => "t:$parte:$i",
                    'area' => $area, 'nivel' => 2, 'fija' => $i < 5 ? 1 : 0, 'cero' => 0,
                    'respuesta' => '1', 'es_correcto' => $bien ? 1 : 0, 'tipo_error' => $bien ? null : 'lejano',
                    'tiempo_ms' => 2500, 'azar' => 0, 'error_pct' => null, 'respondido_en' => now(),
                ];
            }
        }
        RespuestaNumeros::insert($filas);

        DetalleNumeros::create([
            'sesion_id' => $sesion->id, 'viaje' => $viaje, 'ciclo' => $ciclo, 'semilla' => 12345, 'grado' => $grado,
            'partes_json' => [], 'completa' => true, 'confiable' => true, 'valida' => true,
            'total_preguntas' => count($filas), 'respondidas' => count($filas),
        ]);
    }

    private function cicloHecho(Estudiante $est, int $ciclo, array $pct = []): void
    {
        foreach ([1, 2, 3] as $viaje) {
            $this->viajeHecho($est, $ciclo, $viaje, $pct);
        }
    }

    /** Alumno con 2 ciclos y el sentido de la cantidad en 70 %: semáforo amarillo. */
    private function alumnoAmarillo(): Estudiante
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1, ['sentido_cantidad' => 70]);
        $this->cicloHecho($a, 2, ['sentido_cantidad' => 70]);
        $this->assertSame('amarillo', app(TamizajeService::class)->calcular($a->id)['semaforo']);

        return $a;
    }

    private function respuestaIA(array $over = []): array
    {
        $json = json_encode(array_merge([
            'lectura' => 'En los últimos ciclos los aciertos en «Sentido de la cantidad» quedaron algo por debajo de lo esperado. Conviene mirar con más atención cómo compara cantidades. Esto no es un diagnóstico.',
            'actividades' => "1) Mostrar dos montoncitos de semillas y preguntar cuál tiene más.\n2) Ubicar números en una cinta numérica en el piso.",
            'texto_familia' => 'Hola, familia de {nombre}. En la escuela está jugando a «Mati y los números». Notamos que a veces le cuesta un poco comparar cantidades. En casa pueden jugar con semillas a ver dónde hay más. No es un diagnóstico ni una evaluación. Los invito a conversar.',
        ], $over), JSON_UNESCAPED_UNICODE);

        return ['content' => [['type' => 'text', 'text' => $json]], 'usage' => ['input_tokens' => 400, 'output_tokens' => 300]];
    }

    private function fakeIA(array $over = []): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($this->respuestaIA($over), 200)]);
    }

    // ── Informe con la IA ────────────────────────────────────────────────

    public function test_la_docente_pide_el_informe_y_se_guarda_el_de_la_ia()
    {
        $a = $this->alumnoAmarillo();
        $this->fakeIA();

        $this->post(route('docente.tamizaje.informe', $a->id))
            ->assertRedirect(route('docente.tamizaje.alumno', $a->id) . '#informe')
            ->assertSessionHas('mensaje');

        $i = TamizajeInforme::firstOrFail();
        $this->assertSame('ia', $i->origen);
        $this->assertNull($i->motivo_respaldo);
        $this->assertSame($a->id, $i->estudiante_id);
        $this->assertSame($this->docente->id, $i->docente_id);
        $this->assertSame($a->id_anonimizado, $i->id_anonimizado);
        $this->assertSame('amarillo', $i->semaforo);
        $this->assertSame(2, $i->ciclos);
        $this->assertSame(700, $i->tokens_usados);
        $this->assertStringContainsString('Sentido de la cantidad', $i->lectura);
        Http::assertSentCount(1);
    }

    public function test_a_la_ia_solo_viajan_datos_anonimos()
    {
        $a = $this->alumnoAmarillo();
        $a->update(['fecha_nacimiento' => '2019-05-03']);
        app(TamizajeService::class)->guardarFicha($a->id, $this->docente->id, ['cuenta_dedos', 'ya_recibe_apoyo'], self::NOTA_SECRETA);
        $this->fakeIA();

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasNoErrors();

        Http::assertSent(function ($request) use ($a) {
            $cuerpo = json_encode($request->data(), JSON_UNESCAPED_UNICODE);
            $this->assertStringContainsString($a->id_anonimizado, $cuerpo);
            foreach ([$a->codigo_acceso, '2019-05-03', self::NOTA_SECRETA, 'cuenta_dedos', 'ya_recibe_apoyo'] as $prohibido) {
                $this->assertStringNotContainsString($prohibido, $cuerpo, "No debía viajar: {$prohibido}");
            }
            $this->assertSame('clave-de-prueba', $request->header('x-api-key')[0]);

            return true;
        });

        // Lo que se guardó como «enviado» es lo mismo: números y el UUID.
        $enviado = json_encode(TamizajeInforme::firstOrFail()->datos_enviados, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString($a->nombre, $enviado);
        $this->assertStringNotContainsString($a->apellido, $enviado);
        $this->assertStringNotContainsString($a->codigo_acceso, $enviado);
        $this->assertStringContainsString('"sentido_cantidad"', $enviado);
    }

    public function test_el_informe_incluye_la_tendencia_y_los_errores_de_los_ultimos_ciclos()
    {
        $a = $this->alumnoAmarillo();
        $this->fakeIA();

        $this->post(route('docente.tamizaje.informe', $a->id));

        $datos = TamizajeInforme::firstOrFail()->datos_enviados;
        $this->assertCount(2, $datos['tendencia_por_ciclo_pct']);
        $this->assertSame(['lejano'], array_keys($datos['errores_frecuentes']));
        $this->assertSame(70, $datos['areas']['sentido_cantidad']['porcentaje_aciertos']);
        $this->assertSame(['verde' => 75, 'amarillo' => 60], $datos['cortes']);
    }

    // ── Respaldo ─────────────────────────────────────────────────────────

    public function test_una_frase_prohibida_de_la_ia_se_reemplaza_por_la_plantilla()
    {
        $a = $this->alumnoAmarillo();
        $this->fakeIA(['lectura' => 'Muy probablemente tiene discalculia y habría que llevarlo a un especialista cuanto antes.']);

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHas('mensaje');

        $i = TamizajeInforme::firstOrFail();
        $this->assertSame('plantilla', $i->origen);
        $this->assertStringContainsString('Frase no permitida', $i->motivo_respaldo);
        $this->assertStringNotContainsString('discalculia', $i->lectura . $i->actividades . $i->texto_familia);
        $this->assertStringContainsString('no es un diagnóstico', $i->lectura);
    }

    public function test_si_la_api_falla_se_usa_la_plantilla()
    {
        $a = $this->alumnoAmarillo();
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'sobrecargado'], 529)]);

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHas('mensaje');

        $i = TamizajeInforme::firstOrFail();
        $this->assertSame('plantilla', $i->origen);
        $this->assertStringContainsString('529', $i->motivo_respaldo);
        $this->assertNotSame('', $i->texto_familia);
    }

    public function test_si_la_ia_devuelve_basura_se_usa_la_plantilla()
    {
        $a = $this->alumnoAmarillo();
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'no puedo ayudarte con eso']], 'usage' => []], 200)]);

        $this->post(route('docente.tamizaje.informe', $a->id));

        $this->assertSame('plantilla', TamizajeInforme::firstOrFail()->origen);
    }

    public function test_sin_clave_de_api_no_se_llama_a_la_ia_y_se_usa_la_plantilla()
    {
        $a = $this->alumnoAmarillo();
        config(['services.claude.key' => null]);
        Http::fake();

        $this->post(route('docente.tamizaje.informe', $a->id));

        Http::assertNothingSent();
        $i = TamizajeInforme::firstOrFail();
        $this->assertSame('plantilla', $i->origen);
        $this->assertStringContainsString('API key', $i->motivo_respaldo);
    }

    // ── Reglas de uso ────────────────────────────────────────────────────

    public function test_sin_semaforo_no_hay_informe_y_no_se_llama_a_la_ia()
    {
        $a = $this->alumnoPropio();
        $this->cicloHecho($a, 1);   // un solo ciclo: todavía no hay semáforo
        Http::fake();

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasErrors('error');

        Http::assertNothingSent();
        $this->assertSame(0, TamizajeInforme::count());
    }

    public function test_hay_que_esperar_entre_un_informe_y_otro_y_despues_se_puede()
    {
        $a = $this->alumnoAmarillo();
        $this->fakeIA();

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasNoErrors();
        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasErrors('error');
        $this->assertSame(1, TamizajeInforme::count());
        Http::assertSentCount(1);

        $this->travel(TamizajeInformeService::ESPERA_SEGUNDOS + 1)->seconds();

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasNoErrors();
        $this->assertSame(2, TamizajeInforme::count());
    }

    public function test_otro_docente_no_puede_pedir_el_informe()
    {
        $a = $this->alumnoPropio();
        Http::fake();
        $this->actingAs(Usuario::where('email', 'docente2@unae.edu.py')->firstOrFail());

        $this->post(route('docente.tamizaje.informe', $a->id))->assertSessionHasErrors('error');

        Http::assertNothingSent();
        $this->assertSame(0, TamizajeInforme::count());
    }

    public function test_el_informe_nunca_se_genera_solo()
    {
        $a = $this->alumnoPropio();
        Http::fake();

        // Terminar ciclos (lo que dispara el semáforo y la campanita) no llama a la IA.
        $this->cicloHecho($a, 1, ['sentido_cantidad' => 70]);
        $this->cicloHecho($a, 2, ['sentido_cantidad' => 70]);
        app(TamizajeService::class)->actualizar($a->id);
        $this->get(route('docente.tamizaje'))->assertOk();
        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, TamizajeInforme::count());
    }

    // ── Pantalla ─────────────────────────────────────────────────────────

    public function test_la_pantalla_muestra_el_boton_cuando_hay_semaforo()
    {
        $a = $this->alumnoAmarillo();

        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk()
            ->assertSee('Informe con ayuda de la IA')
            ->assertSee('Pedir informe')
            ->assertSee('solo números anónimos');
    }

    public function test_la_pantalla_avisa_que_faltan_ciclos_si_no_hay_semaforo()
    {
        $a = $this->alumnoPropio();

        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk()
            ->assertSee('al menos 2 ciclos')
            ->assertDontSee('Pedir informe');
    }

    public function test_la_pantalla_muestra_el_informe_con_el_nombre_de_pila_en_el_texto_de_la_familia()
    {
        $a = $this->alumnoAmarillo();
        $this->fakeIA();
        $this->post(route('docente.tamizaje.informe', $a->id));

        $r = $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk();

        $r->assertSee('Redactado con ayuda de la IA');
        $r->assertSee('borrador');
        $r->assertSee('Hola, familia de ' . $a->nombre);
        $r->assertDontSee('{nombre}', false);
        $r->assertSee('Pedir un informe nuevo');
    }

    public function test_la_pantalla_marca_el_informe_estandar_cuando_no_hubo_ia()
    {
        $a = $this->alumnoAmarillo();
        Http::fake(['api.anthropic.com/*' => Http::response([], 500)]);
        $this->post(route('docente.tamizaje.informe', $a->id));

        $this->get(route('docente.tamizaje.alumno', $a->id))->assertOk()
            ->assertSee('Informe estándar');
    }
}
