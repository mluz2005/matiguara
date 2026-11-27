<?php

namespace Database\Seeders;

use App\Models\MensajeRetro;
use Illuminate\Database\Seeder;

/**
 * SEEDER: MensajesRetroSeeder
 * -------------------------------------------------------------------------
 * Catálogo inicial de mensajes de retroalimentación bilingüe (RF-05, RN-06).
 * Todos los mensajes son positivos y motivadores (pedagogía infantil).
 * Máximo 160 caracteres en cada idioma.
 *
 * GUARANÍ: textos revisados por la persona hablante (IDs CMP-RET-01..13,
 * 2026-11). Deben coincidir con App\Support\TextosGuaraniCompartidos::MENSAJES.
 * -------------------------------------------------------------------------
 */
class MensajesRetroSeeder extends Seeder
{
    public function run(): void
    {
        $mensajes = [
            // ── ACIERTOS ──────────────────────────────────────────────
            ['tipo' => 'acierto', 'contexto' => 'general',
             'texto_es' => '¡Excelente! Lo lograste. Seguí así, vas muy bien.',
             'texto_gn' => '¡Iporãite! Rejapo porã. Eikove katu, iporãite.'],

            ['tipo' => 'acierto', 'contexto' => 'general',
             'texto_es' => '¡Muy bien! Esa respuesta es correcta. ¡Sos increíble!',
             'texto_gn' => '¡Iporãite! Upe mbohovái oĩporã. ¡Nde arandu!'],

            ['tipo' => 'acierto', 'contexto' => 'primer_intento',
             'texto_es' => '¡Perfecto en el primer intento! Sos un crack de las matemáticas.',
             'texto_gn' => '¡Iporãite ñepyrũme! Nde arandu matemátikape.'],

            ['tipo' => 'acierto', 'contexto' => 'reintento',
             'texto_es' => '¡Lo conseguiste! El esfuerzo siempre vale la pena. ¡Adelante!',
             'texto_gn' => '¡Rejapo! Tembiapo porã aréma opyta. ¡Eho tenonde!'],

            // ── ERRORES (siempre positivos) ───────────────────────────
            ['tipo' => 'error', 'contexto' => 'general',
             'texto_es' => 'No importa, ¡intentalo de nuevo! Cada error te hace más fuerte.',
             'texto_gn' => 'Nahániri, ¡erejere jevy! Opa jejavy omombareteve.'],

            ['tipo' => 'error', 'contexto' => 'general',
             'texto_es' => 'Casi lo tenés. ¡Revisá bien y volvé a intentarlo!',
             'texto_gn' => 'Hasyete mbytépe. ¡Ehecha porã ha erejere jevy!'],

            ['tipo' => 'error', 'contexto' => 'primer_intento',
             'texto_es' => 'Buena pregunta difícil. Pensá despacio y podrás lograrlo.',
             'texto_gn' => 'Porã porã. Emano mbegue ha rejapóta.'],

            ['tipo' => 'error', 'contexto' => 'reintento',
             'texto_es' => 'No te rindas, ¡vos podés! Mirá bien los números y probá.',
             'texto_gn' => 'Ani remboyke, ¡nde ikatúva! Ehecha porã papapy ha eñeme\'ẽ.'],

            // ── RACHAS ────────────────────────────────────────────────
            ['tipo' => 'racha', 'contexto' => 'general',
             'texto_es' => '¡Racha activa! Llevás varios aciertos seguidos. ¡No pares!',
             'texto_gn' => '¡Racha oĩva! Rejapyhy hetave acierto. ¡Ani rekyhyje!'],

            ['tipo' => 'racha', 'contexto' => 'maximo_racha',
             'texto_es' => '¡Nuevo récord! ¡Esta es tu mejor racha de aciertos! ¡Extraordinario!',
             'texto_gn' => '¡Récord pyahu! Ko ne racha iporãvéva. ¡Iporãite!'],

            // ── NIVEL SUPERADO ────────────────────────────────────────
            ['tipo' => 'nivel_superado', 'contexto' => 'general',
             'texto_es' => '¡Nivel superado! ¡Sos increíble! El siguiente nivel te espera.',
             'texto_gn' => '¡Nivel oñembohasa! ¡Nde arandu! Nivel pyahu oha\'arõ ndéve.'],

            ['tipo' => 'nivel_superado', 'contexto' => 'general',
             'texto_es' => '¡Lo lograste! Pasaste al siguiente nivel. ¡Seguí brillando!',
             'texto_gn' => '¡Rejapo! Rehasa nivel pyahúpe. ¡Ehesape katu!'],

            // ── INICIO DE SESIÓN ──────────────────────────────────────
            ['tipo' => 'inicio_sesion', 'contexto' => 'general',
             'texto_es' => '¡Bienvenido de nuevo! Hoy vas a aprender cosas increíbles. ¡Empecemos!',
             'texto_gn' => '¡Maitei jevy! Ko árape reñe\'ẽta mba\'e ipyahúva. ¡Ñañepyrũ!'],
        ];

        foreach ($mensajes as $m) {
            MensajeRetro::create(array_merge($m, ['activo' => true]));
        }
        $this->command->info('  ✅ Mensajes de retroalimentación: ' . count($mensajes));
    }
}
