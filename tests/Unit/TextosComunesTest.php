<?php

namespace Tests\Unit;

use App\Support\Cumpleanos;
use App\Support\TextosComunes as T;
use PHPUnit\Framework\TestCase;

/**
 * Guaraní revisado de las pantallas comunes (planilla Guarani_PantallasComunes_revisado):
 * descanso, barra de tiempo restante, cumpleaños, ingreso de estudiantes y acceso de familias.
 */
class TextosComunesTest extends TestCase
{
    private function leer(string $ruta): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $ruta);
    }

    public function test_descanso()
    {
        $d = T::DESCANSO;
        $this->assertSame("Japyta ñapytu'ũ", $d['pestana']['gn']);
        $this->assertSame("¡Aravo ñapytu'ũ haguã!", $d['titulo']['gn']);
        $this->assertSame("Reñembosarái hetáma ha nde resa oikotevẽ pytu'u.", $d['texto']['gn']);
        $this->assertSame('👀 Emaña mombyry, ovetã rupi.', $d['consejo1']['gn']);
        $this->assertSame('🤸 Eipyso nde jyva ha nde retyma.', $d['consejo2']['gn']);
        $this->assertSame("💧 E'u michĩmi y.", $d['consejo3']['gn']);
        $this->assertSame('Ñañembosarái jeýta', $d['volvemos']['gn']);
        $this->assertSame("{n} aravo'i", $d['minutos']['gn']);
        $this->assertSame('3 aravo\'i', T::rellenar($d['minutos']['gn'], ['n' => 3]));
        $this->assertSame('¡Oĩmbáma!', $d['listo']['gn']);
        $this->assertSame('🎮 ¡Ñañembosarái jey!', $d['volver']['gn']);
        $this->assertSame('Mati oke haitypýpe, oñeñuvã porã', $d['imagen']['gn']);
        $this->assertSame('📊 Che ñemotenonde', T::ENLACES_DESCANSO['progreso']['gn']);
        $this->assertSame('🏅 Che jehupytypy', T::ENLACES_DESCANSO['logros']['gn']);
    }

    public function test_barra_de_tiempo()
    {
        $this->assertSame("🦜 ¡Ag̃uimbaitéma! Emohu'ã ko tembiapo ha ñapytu'ũ sapy'ami.", T::TIEMPO['aviso']['gn']);
        $this->assertSame('Aravo ñembosarái opytáva', T::TIEMPO['barra']['gn']);
        $this->assertSame('Tiempo de juego que queda · Aravo ñembosarái opytáva', T::unido(T::TIEMPO['barra']));
    }

    public function test_cumpleanos()
    {
        $this->assertSame("¡Vy'apavẽ nde arambotýre, Ana! 🎂", Cumpleanos::saludoGn('Ana'));
        $this->assertSame('¡Feliz cumpleaños, Ana! 🎂', Cumpleanos::saludoEs('Ana'));
        $this->assertSame('Mati ha ne irũnguéra ka\'aguypegua oipota ndéve peteĩ ára henyhẽva vy\'águi.', Cumpleanos::FRASE_GN);
    }

    public function test_ingreso_titulo_globos_y_etiquetas()
    {
        $i = T::INGRESO;
        $this->assertSame('¡Ñañembosarái! — MatiGuarã', $i['pestana']['gn']);
        $this->assertSame('¡Ñañembosarái!', $i['titulo']['gn']);
        $this->assertSame('Emoinge nde código ha nde PIN ñemimby', $i['subtitulo']['gn']);
        $this->assertSame("¡Mba'éichapa! Ehai nde código.", $i['burbujaInicio']['gn']);
        $this->assertSame('🪪 Nde código jeikeha', $i['etCodigo']['gn']);
        $this->assertSame('🔐 Nde PIN ñemimby (4 papapy)', $i['etPin']['gn']);
        $this->assertSame('🚀 ¡Eike eñembosarái!', $i['botonEntrar']['gn']);
        $this->assertSame("¡Eseguí katu! Ofalta ndéve 2 papapy.", T::rellenar($i['burbujaSigue']['gn'], ['n' => 2]));
        $this->assertSame("¡Mba'éichapa, Ana! 🎮", T::rellenar($i['bienvenida']['gn'], ['nombre' => 'Ana']));
        $this->assertSame('¿Mávapa nde?', $i['quienSos']['gn']);
        $this->assertSame('Reĩ ko\'ápe', $i['estasAca']['gn']);
        $this->assertSame("Embogue papapy paha", T::TECLADO['borrarUltimo']['gn']);
        $this->assertSame('Embogue paite', T::TECLADO['borrarTodo']['gn']);
    }

    public function test_selector_de_perfiles()
    {
        $this->assertSame("Temimbo'e", T::ROLES['estudiante']['nombre']['gn']);
        $this->assertSame('Che código ha PIN ndive', T::ROLES['estudiante']['forma']['gn']);
        $this->assertSame("Mbo'ehára térã moakãhára", T::ROLES['docente']['nombre']['gn']);
        $this->assertSame("Correo ha ñe'ẽñemi ndive", T::ROLES['docente']['forma']['gn']);
        $this->assertSame('Ogaygua', T::ROLES['familia']['nombre']['gn']);
        $this->assertSame('Correo ha código ndive', T::ROLES['familia']['forma']['gn']);
    }

    public function test_familias()
    {
        $this->assertSame('Ogayguakuéra jeikeha', T::FAMILIA['titulo']['gn']);
        $this->assertSame("Ahecha mba'éichapa oho che memby", T::FAMILIA['subtitulo']['gn']);
    }

    public function test_los_errores_del_ingreso_coinciden_con_los_mensajes_del_controlador()
    {
        $controlador = $this->leer('app/Http/Controllers/Auth/LoginEstudianteController.php');
        $this->assertCount(7, T::ERRORES_INGRESO);
        foreach (T::ERRORES_INGRESO as $es => $gn) {
            $this->assertStringContainsString("'" . $es . "'", $controlador, "El controlador no tiene el mensaje: $es");
            $this->assertNotSame('', $gn);
            $this->assertSame($gn, T::errorIngresoGn($es));
        }
        $this->assertNull(T::errorIngresoGn('Otro mensaje'));
        $this->assertNull(T::errorIngresoGn(null));
        $this->assertSame("Pe PIN oguereko va'erã papapy añónte.", T::errorIngresoGn('El PIN debe tener solo números.'));
    }

    public function test_para_js_lleva_los_textos_de_los_globos()
    {
        $js = T::ingresoParaJs();
        $this->assertCount(10, $js);
        foreach ($js as $t) {
            $this->assertNotSame('', $t['es']);
            $this->assertNotSame('', $t['gn']);
        }
    }

    public function test_las_vistas_usan_la_fuente_unica_y_no_traen_el_guarani_viejo()
    {
        foreach (['auth/login-estudiante', 'auth/login-estudiante-v2', 'auth/login-familia', 'auth/_lg2-roles',
                  'estudiante/descanso', 'estudiante/_limite-tiempo'] as $v) {
            $b = $this->leer("resources/views/$v.blade.php");
            $this->assertStringContainsString('TextosComunes', $b, $v);
            foreach (["Mba'éichapa!</p>", 'Eike</p>', 'Ñapytu\'ũ ko\'ágã', 'Mbyky!', 'Eñembosarái hetáma', "Maitei"] as $viejo) {
                $this->assertStringNotContainsString($viejo, $b, "$v: $viejo");
            }
        }
    }
}
