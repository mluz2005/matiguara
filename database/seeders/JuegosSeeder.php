<?php

namespace Database\Seeders;

use App\Models\ContenidoNivel;
use App\Models\Juego;
use App\Models\Nivel;
use App\Models\TipoMecanica;
use Illuminate\Database\Seeder;

/**
 * SEEDER: JuegosSeeder — Semana 4 (versión completa)
 * =========================================================================
 * Crea los dos primeros juegos completos del sistema:
 *
 * JUEGO 1 — Suma de Frutas (RF-02)
 *   Mecánica: suma simple | Grado 1: 3 niveles | 5 ejercicios por nivel
 *   Modo: números SIEMPRE VISIBLES (tiempo_mostrar_ms = NULL)
 *   Dificultad progresiva: rango 1-5 → rango 1-10 → mixto con llevada
 *
 * JUEGO 2 — Flash Matemático (decisión de diseño sep 2026)
 *   Mecánica: suma simple | Grado 1: 3 niveles | 5 ejercicios por nivel
 *   Modo: números DESAPARECEN según el nivel (8s → 6s → 4s)
 *   Target: 2° y 3° grado principalmente, pero nivel 1 accesible en 1°
 *
 * GUARANÍ:
 * Los textos en guaraní son aproximaciones pedagógicas.
 * DEBEN ser validados por un docente bilingüe antes de producción.
 * Ver: Semana 7 — Grabación de audio guaraní con hablante nativa.
 *
 * Referencia: RF-02 (banco bilingüe), RF-04 (algoritmo adaptativo)
 * CU: CU-02 (seleccionar juego y nivel), CU-03 (resolver ejercicio)
 * =========================================================================
 */
class JuegosSeeder extends Seeder
{
    public function run(): void
    {
        // ── Obtener mecánica de suma ──────────────────────────────────────
        $mecSuma = TipoMecanica::where('tipo', 'suma')
            ->where('nombre', 'Suma simple')
            ->firstOrFail();

        // ═══════════════════════════════════════════════════════════════════
        // JUEGO 1 — SUMA DE FRUTAS
        // Números siempre visibles. Diseñado para 1° grado (6-7 años).
        // ═══════════════════════════════════════════════════════════════════
        $sumaFrutas = Juego::create([
            'tipo_mecanica_id'   => $mecSuma->id,
            'nombre_es'          => 'Suma de Frutas',
            'nombre_gn'          => 'Tesãi Rendápe Ñemongu\'e',
            'instrucciones_es'   => 'Sumá las frutas que ves y elegí el resultado correcto. ¡Vos podés!',
            'instrucciones_gn'   => 'Embojuaju tesãirã rehecháva ha eiporavo mba\'e oikóva. ¡Ikatu nde!',
            'activo'             => true,
            'dias_reinicio_racha'=> null, // Usa el default de la institución (RN-08)
        ]);

        $this->crearNivelesSumaFrutas($sumaFrutas->id);
        $this->command->info("  ✅ Juego 1: {$sumaFrutas->nombre_es} — 3 niveles, 15 ejercicios");

        // ═══════════════════════════════════════════════════════════════════
        // JUEGO 2 — FLASH MATEMÁTICO
        // Números que desaparecen. Memoria de trabajo matemática.
        // ═══════════════════════════════════════════════════════════════════
        $flashMat = Juego::create([
            'tipo_mecanica_id'   => $mecSuma->id,
            'nombre_es'          => 'Flash Matemático',
            'nombre_gn'          => 'Papapy Pya\'e',
            'instrucciones_es'   => '¡Mirá bien los números, van a desaparecer! Recordalos y elegí el resultado.',
            'instrucciones_gn'   => '¡Ehecháke porã papapy, ojepe\'a-ta! Ejekuaave ha eiporavo mba\'e oikóva.',
            'activo'             => true,
            'dias_reinicio_racha'=> null,
        ]);

        $this->crearNivelesFlashMatematico($flashMat->id);
        $this->command->info("  ✅ Juego 2: {$flashMat->nombre_es} — 3 niveles, 15 ejercicios");
    }

    // ─────────────────────────────────────────────────────────────────────
    // SUMA DE FRUTAS — niveles y ejercicios
    // ─────────────────────────────────────────────────────────────────────

    private function crearNivelesSumaFrutas(int $juegoId): void
    {
        // ── NIVEL 1 — Sumas hasta 5 (dificultad 1) ───────────────────────
        $n1 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 1,
            'dificultad'              => 1,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> true,
            'tiempo_mostrar_ms'       => null, // Siempre visible
        ]);

        $this->insertarEjercicios($n1->id, [
            [2, 3,  '5',  '¿Cuánto es 2 más 3?',         '¿Mboy\'e pa mokõi más mbohapy?',          [5,4,6,3]],
            [4, 1,  '5',  '¿Cuánto es 4 más 1?',         '¿Mboy\'e pa irundy más peteĩ?',           [5,6,4,7]],
            [3, 2,  '5',  '¿Cuánto es 3 más 2?',         '¿Mboy\'e pa mbohapy más mokõi?',          [5,4,6,3]],
            [1, 4,  '5',  '¿Cuánto es 1 más 4?',         '¿Mboy\'e pa peteĩ más irundy?',           [5,3,6,4]],
            [2, 2,  '4',  '¿Cuánto es 2 más 2?',         '¿Mboy\'e pa mokõi más mokõi?',            [4,5,3,6]],
        ]);

        // ── NIVEL 2 — Sumas hasta 10 (dificultad 2) ──────────────────────
        $n2 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 2,
            'dificultad'              => 2,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> false,
            'tiempo_mostrar_ms'       => null,
        ]);

        $this->insertarEjercicios($n2->id, [
            [7, 3,  '10', '¿Cuánto es 7 más 3?',         '¿Mboy\'e pa poteĩ más mbohapy?',          [10,9,11,8]],
            [5, 4,  '9',  '¿Cuánto es 5 más 4?',         '¿Mboy\'e pa po más irundy?',              [9,8,10,7]],
            [6, 2,  '8',  '¿Cuánto es 6 más 2?',         '¿Mboy\'e pa poteĩ más mokõi?',            [8,7,9,6]],
            [4, 5,  '9',  '¿Cuánto es 4 más 5?',         '¿Mboy\'e pa irundy más po?',              [9,8,10,11]],
            [3, 7,  '10', '¿Cuánto es 3 más 7?',         '¿Mboy\'e pa mbohapy más poteĩ?',          [10,9,11,8]],
        ]);

        // ── NIVEL 3 — Sumas hasta 15, mezcla (dificultad 3) ──────────────
        $n3 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 3,
            'dificultad'              => 3,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> false,
            'tiempo_mostrar_ms'       => null,
        ]);

        $this->insertarEjercicios($n3->id, [
            [8, 5,  '13', '¿Cuánto es 8 más 5?',         '¿Mboy\'e pa poapy más po?',               [13,12,14,11]],
            [9, 4,  '13', '¿Cuánto es 9 más 4?',         '¿Mboy\'e pa pokõi más irundy?',           [13,12,14,15]],
            [7, 6,  '13', '¿Cuánto es 7 más 6?',         '¿Mboy\'e pa poteĩ más poapy?',            [13,12,14,11]],
            [6, 8,  '14', '¿Cuánto es 6 más 8?',         '¿Mboy\'e pa poapy más poapy?',            [14,13,15,12]],
            [9, 6,  '15', '¿Cuánto es 9 más 6?',         '¿Mboy\'e pa pokõi más poapy?',            [15,14,16,13]],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // FLASH MATEMÁTICO — niveles y ejercicios con countdown
    // ─────────────────────────────────────────────────────────────────────

    private function crearNivelesFlashMatematico(int $juegoId): void
    {
        // ── NIVEL 1 — 8 segundos visibles (dificultad 1) ─────────────────
        $n1 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 1,
            'dificultad'              => 1,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> true,
            'tiempo_mostrar_ms'       => 8000, // 8 segundos — nivel más accesible
        ]);

        $this->insertarEjercicios($n1->id, [
            [3, 4, '7',  '¡Mirá! 3 más 4. ¡Memorizalo!', '¡Ehecha! mbohapy más irundy. ¡Eñangarekoite!', [7,6,8,5]],
            [2, 5, '7',  '¡Mirá! 2 más 5. ¡Memorizalo!', '¡Ehecha! mokõi más po. ¡Eñangarekoite!',       [7,8,6,9]],
            [4, 3, '7',  '¡Mirá! 4 más 3. ¡Memorizalo!', '¡Ehecha! irundy más mbohapy. ¡Eñangarekoite!',[7,6,8,5]],
            [1, 5, '6',  '¡Mirá! 1 más 5. ¡Memorizalo!', '¡Ehecha! peteĩ más po. ¡Eñangarekoite!',      [6,5,7,4]],
            [5, 3, '8',  '¡Mirá! 5 más 3. ¡Memorizalo!', '¡Ehecha! po más mbohapy. ¡Eñangarekoite!',    [8,7,9,6]],
        ]);

        // ── NIVEL 2 — 6 segundos visibles (dificultad 3) ─────────────────
        $n2 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 2,
            'dificultad'              => 3,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> false,
            'tiempo_mostrar_ms'       => 6000, // 6 segundos — exige más concentración
        ]);

        $this->insertarEjercicios($n2->id, [
            [6, 4, '10', '¡Rápido! 6 más 4.', '¡Pya\'e! poapy más irundy.',  [10,9,11,8]],
            [7, 2, '9',  '¡Rápido! 7 más 2.', '¡Pya\'e! poteĩ más mokõi.',   [9,8,10,7]],
            [5, 5, '10', '¡Rápido! 5 más 5.', '¡Pya\'e! po más po.',         [10,9,11,8]],
            [8, 2, '10', '¡Rápido! 8 más 2.', '¡Pya\'e! poapy más mokõi.',   [10,9,11,12]],
            [4, 6, '10', '¡Rápido! 4 más 6.', '¡Pya\'e! irundy más poapy.',  [10,9,11,8]],
        ]);

        // ── NIVEL 3 — 4 segundos visibles (dificultad 5) ─────────────────
        $n3 = Nivel::create([
            'juego_id'                => $juegoId,
            'grado'                   => 1,
            'numero_nivel'            => 3,
            'dificultad'              => 5,
            'min_aciertos_pasar'      => 3,
            'total_ejercicios'        => 5,
            'desbloqueado_por_defecto'=> false,
            'tiempo_mostrar_ms'       => 4000, // 4 segundos — máximo desafío grado 1
        ]);

        $this->insertarEjercicios($n3->id, [
            [9, 3, '12', '¡Flash! 9 más 3.',  '¡Flash! pokõi más mbohapy.', [12,11,13,10]],
            [8, 4, '12', '¡Flash! 8 más 4.',  '¡Flash! poapy más irundy.',  [12,11,13,14]],
            [7, 5, '12', '¡Flash! 7 más 5.',  '¡Flash! poteĩ más po.',      [12,11,13,10]],
            [6, 7, '13', '¡Flash! 6 más 7.',  '¡Flash! poapy más poteĩ.',   [13,12,14,11]],
            [9, 5, '14', '¡Flash! 9 más 5.',  '¡Flash! pokõi más po.',      [14,13,15,12]],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper: inserta ejercicios en lote
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param int   $nivelId
     * @param array $ejercicios  Cada ítem: [a, b, respuesta, enunciado_es, enunciado_gn, opciones]
     */
    private function insertarEjercicios(int $nivelId, array $ejercicios): void
    {
        foreach ($ejercicios as $orden => $ej) {
            [$a, $b, $resp, $es, $gn, $opciones] = $ej;

            ContenidoNivel::create([
                'nivel_id'           => $nivelId,
                'tipo_operacion'     => 'suma_simple',
                'enunciado_es'       => $es,
                'enunciado_gn'       => $gn,
                'valor_a'            => $a,
                'operador'           => '+',
                'valor_b'            => $b,
                'respuesta_correcta' => $resp,
                'opciones_json'      => $opciones,
                'orden'              => $orden + 1,
                'audio_es'           => null, // Se agrega en Semana 7 con grabaciones
                'audio_gn'           => null,
            ]);
        }
    }
}
