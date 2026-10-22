<?php

namespace Tests\Feature\Repositories;

use App\Models\ProgresoEstudiante;
use App\Services\SesionService;
use App\Services\SistemaAdaptativoService;

/**
 * Prueba de integración del flujo de la Figura 6: SesionService y
 * SistemaAdaptativoService trabajando sobre los repositorios (BD real de pruebas).
 */
class FlujoSesionTest extends RepositorioTestCase
{
    public function test_los_servicios_se_resuelven_con_sus_repositorios_inyectados()
    {
        $this->assertInstanceOf(SesionService::class, app(SesionService::class));
        $this->assertInstanceOf(SistemaAdaptativoService::class, app(SistemaAdaptativoService::class));
    }

    public function test_sesion_completa_responder_cerrar_y_desbloquear_el_siguiente_nivel()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);

        $sesion = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id, 'mobile');
        $this->assertSame('en_curso', $sesion->estado);

        // Una sesión huérfana anterior queda abandonada al iniciar otra
        $otra = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $this->assertSame('abandonada', $sesion->fresh()->estado);
        $sesion = $otra;

        $vistos = [];
        $total  = (int) $e['nivel1']->total_ejercicios;
        for ($i = 0; $i < $total; $i++) {
            $ejercicio = $sesiones->obtenerProximoEjercicio($sesion);
            $this->assertNotNull($ejercicio);
            $this->assertNotContains($ejercicio->id, $vistos, 'No debe repetir ejercicios dentro de la sesión.');
            $vistos[] = $ejercicio->id;

            $r = $sesiones->registrarRespuesta($sesion, $ejercicio, (string) $ejercicio->respuesta_correcta, 2000);
            $this->assertTrue($r['es_correcto']);
            $this->assertSame(1, $r['numero_intento']);
            $this->assertSame(10, $r['puntos'], 'Suma de Frutas no da bonus por velocidad: 10 puntos fijos.');
        }

        $this->assertSame($total, (int) $sesion->aciertos);
        $this->assertSame(10 * $total, (int) $sesion->puntaje_obtenido);
        $this->assertTrue($sesiones->debeTerminar($sesion, $e['nivel1']));

        $cerrada = $sesiones->cerrar($sesion, false);

        $this->assertSame('completada', $cerrada->estado);
        $this->assertTrue((bool) $cerrada->nivel_superado);

        $progreso = ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)
            ->where('nivel_id', $e['nivel1']->id)->firstOrFail();
        $this->assertSame(1, (int) $progreso->total_partidas);
        $this->assertSame($total, (int) $progreso->total_aciertos);

        $siguiente = ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)
            ->where('nivel_id', $e['nivel2']->id)->firstOrFail();
        $this->assertTrue((bool) $siguiente->nivel_desbloqueado);
    }

    public function test_primer_error_en_suma_de_frutas_no_cuenta_todavia_y_clasifica_el_tipo()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);
        $sesion   = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $ejercicio = $sesiones->obtenerProximoEjercicio($sesion);

        $r = $sesiones->registrarRespuesta($sesion, $ejercicio, '999', 500);

        $this->assertFalse($r['es_correcto']);
        $this->assertTrue($r['reintento_pendiente']);
        $this->assertSame(0, $r['puntos']);
        $this->assertSame('atencion', $r['movimiento']->tipo_error, 'Menos de 1 s = error de atención.');
        $this->assertSame(0, (int) $sesion->errores, 'El primer error espera la segunda oportunidad.');
        $this->assertSame(0, (int) $sesion->aciertos);
    }

    public function test_segunda_oportunidad_vuelve_el_mismo_ejercicio_y_si_acierta_da_5_puntos()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);
        $sesion   = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $ejercicio = $sesiones->obtenerProximoEjercicio($sesion);

        $sesiones->registrarRespuesta($sesion, $ejercicio, '999', 3000);

        $this->assertTrue($sesiones->esReintento($sesion, $ejercicio));
        $mismo = $sesiones->obtenerProximoEjercicio($sesion);
        $this->assertSame($ejercicio->id, $mismo->id, 'Tras fallar, se vuelve a mostrar el mismo ejercicio.');

        $r = $sesiones->registrarRespuesta($sesion, $mismo, (string) $mismo->respuesta_correcta, 3000);

        $this->assertTrue($r['es_correcto']);
        $this->assertFalse($r['reintento_pendiente']);
        $this->assertSame(2, $r['numero_intento']);
        $this->assertSame(5, $r['puntos']);
        $this->assertSame(5, (int) $sesion->puntaje_obtenido);
        $this->assertSame(1, (int) $sesion->aciertos);
        $this->assertSame(0, (int) $sesion->errores);

        $this->assertFalse($sesiones->esReintento($sesion, $mismo));
        $siguiente = $sesiones->obtenerProximoEjercicio($sesion);
        $this->assertNotSame($ejercicio->id, $siguiente->id, 'Resuelto el reintento, avanza al ejercicio siguiente.');
    }

    public function test_si_falla_tambien_la_segunda_vez_cuenta_un_solo_error_y_sigue_adelante()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);
        $sesion   = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $ejercicio = $sesiones->obtenerProximoEjercicio($sesion);

        $sesiones->registrarRespuesta($sesion, $ejercicio, '999', 3000);
        $r = $sesiones->registrarRespuesta($sesion, $ejercicio, '998', 3000);

        $this->assertFalse($r['es_correcto']);
        $this->assertFalse($r['reintento_pendiente'], 'No hay tercera oportunidad.');
        $this->assertSame(0, $r['puntos']);
        $this->assertSame(1, (int) $sesion->errores, 'Un ejercicio fallado dos veces suma un solo error.');
        $this->assertNotSame($ejercicio->id, $sesiones->obtenerProximoEjercicio($sesion)->id);
    }

    public function test_el_bonus_de_velocidad_solo_existe_en_flash_matematico()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);

        $nivelFlash = \App\Models\Nivel::has('ejercicios')->whereNotNull('tiempo_mostrar_ms')
            ->whereHas('juego', fn ($q) => $q->where('nombre_es', 'Flash Matemático'))
            ->orderBy('grado')->orderBy('numero_nivel')->firstOrFail();
        $ejFlash = \App\Models\ContenidoNivel::where('nivel_id', $nivelFlash->id)->orderBy('orden')->firstOrFail();

        $sesionFlash = $sesiones->iniciar($e['estudiante']->id, $nivelFlash->juego_id, $nivelFlash->id);
        $rapido = $sesiones->registrarRespuesta($sesionFlash, $ejFlash, (string) $ejFlash->respuesta_correcta, 2000);
        $this->assertSame(15, $rapido['puntos'], 'Flash: 10 + 5 por responder en menos de 5 s.');

        // Flash no tiene segunda oportunidad: un error cuenta de inmediato.
        $otro = \App\Models\ContenidoNivel::where('nivel_id', $nivelFlash->id)->orderBy('orden')->skip(1)->firstOrFail();
        $fallo = $sesiones->registrarRespuesta($sesionFlash, $otro, '999', 3000);
        $this->assertFalse($fallo['reintento_pendiente']);
        $this->assertSame(1, (int) $sesionFlash->errores);

        $sesionSuma = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        $ej = $sesiones->obtenerProximoEjercicio($sesionSuma);
        $r  = $sesiones->registrarRespuesta($sesionSuma, $ej, (string) $ej->respuesta_correcta, 1000);
        $this->assertSame(10, $r['puntos'], 'Suma de Frutas: sin bonus aunque responda en 1 s.');
    }

    public function test_racha_de_aciertos_se_acumula_y_se_reinicia_con_un_error()
    {
        $e        = $this->escenario();
        $sesiones = app(SesionService::class);

        $sesiones->actualizarRacha($e['estudiante']->id, $e['juego']->id, true);
        $racha = $sesiones->actualizarRacha($e['estudiante']->id, $e['juego']->id, true);
        $this->assertSame(2, (int) $racha->racha_actual_aciertos);
        $this->assertSame(1, (int) $racha->racha_actual_dias);

        $racha = $sesiones->actualizarRacha($e['estudiante']->id, $e['juego']->id, false);
        $this->assertSame(0, (int) $racha->racha_actual_aciertos);
        $this->assertSame(2, (int) $racha->racha_maxima_aciertos);
    }

    public function test_regla_3_2_sube_con_tres_aciertos_y_baja_con_dos_errores()
    {
        $e         = $this->escenario();
        $sesiones  = app(SesionService::class);
        $adaptivo  = app(SistemaAdaptativoService::class);

        // 3 aciertos seguidos en el nivel 1 -> sube al nivel 2 y lo deja desbloqueado
        $sesion = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        foreach ($e['ejercicios']->take(3) as $ej) {
            $sesiones->registrarRespuesta($sesion, $ej, (string) $ej->respuesta_correcta, 2000);
        }
        $ev = $adaptivo->evaluar($sesion);
        $this->assertTrue($ev['cambio_nivel']);
        $this->assertSame('sube', $ev['tipo_cambio']);
        $this->assertSame($e['nivel2']->id, $ev['nivel_nuevo']->id);
        $this->assertTrue((bool) ProgresoEstudiante::where('estudiante_id', $e['estudiante']->id)
            ->where('nivel_id', $e['nivel2']->id)->value('nivel_desbloqueado'));

        // 2 errores seguidos en el nivel 2 -> baja al nivel 1
        $sesion2 = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel2']->id);
        $ejs2 = $e['nivel2']->ejercicios()->orderBy('orden')->take(2)->get();
        foreach ($ejs2 as $ej) {
            $sesiones->registrarRespuesta($sesion2, $ej, '999', 3000);
        }
        $ev2 = $adaptivo->evaluar($sesion2);
        $this->assertSame('baja', $ev2['tipo_cambio']);
        $this->assertSame($e['nivel1']->id, $ev2['nivel_nuevo']->id);

        // En el primer nivel no se puede bajar más
        $sesion3 = $sesiones->iniciar($e['estudiante']->id, $e['juego']->id, $e['nivel1']->id);
        foreach ($e['ejercicios']->take(2) as $ej) {
            $sesiones->registrarRespuesta($sesion3, $ej, '999', 3000);
        }
        $ev3 = $adaptivo->evaluar($sesion3);
        $this->assertFalse($ev3['cambio_nivel']);
    }
}
