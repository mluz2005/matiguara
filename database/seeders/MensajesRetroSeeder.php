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
 * GUARANÍ: los textos en guaraní deben ser validados por un docente
 * bilingüe antes de ir a producción. Los de aquí son aproximaciones.
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
             'texto_gn' => '¡Iporãve! Ejapo porã. Ejapyhy, nde mba\'e iporã.'],

            ['tipo' => 'acierto', 'contexto' => 'general',
             'texto_es' => '¡Muy bien! Esa respuesta es correcta. ¡Sos increíble!',
             'texto_gn' => '¡Porãve! Ha\'eña oñeikotevê. ¡Nde iporã kakuaa!'],

            ['tipo' => 'acierto', 'contexto' => 'primer_intento',
             'texto_es' => '¡Perfecto en el primer intento! Sos un crack de las matemáticas.',
             'texto_gn' => '¡Porã peteĩ joavy rupive! Nde arandukáva matemátikape.'],

            ['tipo' => 'acierto', 'contexto' => 'reintento',
             'texto_es' => '¡Lo conseguiste! El esfuerzo siempre vale la pena. ¡Adelante!',
             'texto_gn' => '¡Ejapo! Mba\'apopy oñeñemboyke reíva. ¡Eguata!'],

            // ── ERRORES (siempre positivos) ───────────────────────────
            ['tipo' => 'error', 'contexto' => 'general',
             'texto_es' => 'No importa, ¡intentalo de nuevo! Cada error te hace más fuerte.',
             'texto_gn' => 'Nahániri mba\'e, ¡ejapo jevy! Oñembyayi añete oñemoinge-ta.'],

            ['tipo' => 'error', 'contexto' => 'general',
             'texto_es' => 'Casi lo tenés. ¡Revisá bien y volvé a intentarlo!',
             'texto_gn' => 'Ichupe reiko. ¡Echa porã ha ejapo jevy!'],

            ['tipo' => 'error', 'contexto' => 'primer_intento',
             'texto_es' => 'Buena pregunta difícil. Pensá despacio y podrás lograrlo.',
             'texto_gn' => 'Porã potĩ hasy. Ekuaa porã ha rejapo-ta.'],

            ['tipo' => 'error', 'contexto' => 'reintento',
             'texto_es' => 'No te rindas, ¡vos podés! Mirá bien los números y probá.',
             'texto_gn' => 'Ani remboyke, ¡nde ikatu! Ehecha porã papapy ha eprueba.'],

            // ── RACHAS ────────────────────────────────────────────────
            ['tipo' => 'racha', 'contexto' => 'general',
             'texto_es' => '¡Racha activa! Llevás varios aciertos seguidos. ¡No pares!',
             'texto_gn' => '¡Racha ipype! Rejapyhy hetave acierto. ¡Ani reky!'],

            ['tipo' => 'racha', 'contexto' => 'maximo_racha',
             'texto_es' => '¡Nuevo récord! ¡Esta es tu mejor racha de aciertos! ¡Extraordinario!',
             'texto_gn' => '¡Récord pyahu! ¡Ko ne racha porãvéva! ¡Iporã kakuaa!'],

            // ── NIVEL SUPERADO ────────────────────────────────────────
            ['tipo' => 'nivel_superado', 'contexto' => 'general',
             'texto_es' => '¡Nivel superado! ¡Sos increíble! El siguiente nivel te espera.',
             'texto_gn' => '¡Nivel oñemboja! ¡Nde iporã kakuaa! Oĩ nivel pyahu nderehe.'],

            ['tipo' => 'nivel_superado', 'contexto' => 'general',
             'texto_es' => '¡Lo lograste! Pasaste al siguiente nivel. ¡Seguí brillando!',
             'texto_gn' => '¡Ejapo! Nde oñeimo\'ã nivel pyahúpe. ¡Ejekuave-sapy\'a!'],

            // ── INICIO DE SESIÓN ──────────────────────────────────────
            ['tipo' => 'inicio_sesion', 'contexto' => 'general',
             'texto_es' => '¡Bienvenido de nuevo! Hoy vas a aprender cosas increíbles. ¡Empecemos!',
             'texto_gn' => '¡Eraha jevy! Ko árape reñe\'ẽ-ta mba\'e ipyahuvéva. ¡Ñañepyrũ!'],
        ];

        foreach ($mensajes as $m) {
            MensajeRetro::create(array_merge($m, ['activo' => true]));
        }
        $this->command->info('  ✅ Mensajes de retroalimentación: ' . count($mensajes));
    }
}
