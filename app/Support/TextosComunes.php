<?php

namespace App\Support;

/**
 * TEXTOS COMUNES de MatiGuarã (se ven en todos los juegos): descanso, barra de tiempo restante,
 * cumpleaños, ingreso de estudiantes, selector «¿Quién sos?» y acceso de familias.
 *
 * Guaraní: versión REVISADA (planilla Guarani_PantallasComunes_revisado, columna «Guaraní corregido»,
 * 2026-11-19). Es la fuente única: la usan las vistas y los scripts de esas pantallas.
 * El ingreso de docentes/administración es solo para adultos y no lleva guaraní.
 *
 * Marcadores: {n} = número, {nombre} = nombre del niño/a (se rellenan con rellenar()).
 */
final class TextosComunes
{
    /** A. Descanso («¡Hora de descansar!») — COM-DES-nn */
    public const DESCANSO = [
        'pestana'            => ['es' => 'A descansar', 'gn' => 'Japyta ñapytu\'ũ'],   // COM-DES-01
        'titulo'             => ['es' => '¡Hora de descansar!', 'gn' => '¡Aravo ñapytu\'ũ haguã!'],   // COM-DES-02
        'texto'              => ['es' => 'Jugaste mucho y tus ojos necesitan un descanso.', 'gn' => 'Reñembosarái hetáma ha nde resa oikotevẽ pytu\'u.'],   // COM-DES-03
        'consejo1'           => ['es' => '👀 Mirá algo lejos, por la ventana.', 'gn' => '👀 Emaña mombyry, ovetã rupi.'],   // COM-DES-04
        'consejo2'           => ['es' => '🤸 Estirá los brazos y las piernas.', 'gn' => '🤸 Eipyso nde jyva ha nde retyma.'],   // COM-DES-05
        'consejo3'           => ['es' => '💧 Tomá un poco de agua.', 'gn' => '💧 E\'u michĩmi y.'],   // COM-DES-06
        'volvemos'           => ['es' => 'Volvemos a jugar en', 'gn' => 'Ñañembosarái jeýta'],   // COM-DES-07
        'minutos'            => ['es' => '{n} min', 'gn' => '{n} aravo\'i'],   // COM-DES-08
        'listo'              => ['es' => '¡Listo!', 'gn' => '¡Oĩmbáma!'],   // COM-DES-09
        'volver'             => ['es' => '🎮 ¡Volver a jugar!', 'gn' => '🎮 ¡Ñañembosarái jey!'],   // COM-DES-10
        'imagen'             => ['es' => 'Mati durmiendo en su nido, bien abrigado', 'gn' => 'Mati oke haitypýpe, oñeñuvã porã'],   // COM-DES-12
    ];

    /** COM-DES-11 — enlaces de la pantalla de descanso */
    public const ENLACES_DESCANSO = [
        'progreso' => ['es' => '📊 Mi progreso', 'gn' => '📊 Che ñemotenonde'],
        'logros'   => ['es' => '🏅 Mis logros',  'gn' => '🏅 Che jehupytypy'],
    ];

    /** B. Barra de tiempo restante — COM-TIE-nn */
    public const TIEMPO = [
        'aviso'              => ['es' => '🦜 ¡Ya casi! Terminá este ejercicio y descansamos un ratito.', 'gn' => '🦜 ¡Ag̃uimbaitéma! Emohu\'ã ko tembiapo ha ñapytu\'ũ sapy\'ami.'],   // COM-TIE-01
        'barra'              => ['es' => 'Tiempo de juego que queda', 'gn' => 'Aravo ñembosarái opytáva'],   // COM-TIE-02
    ];

    /** C. Cumpleaños — COM-CUM-nn */
    public const CUMPLE = [
        'saludo'             => ['es' => '¡Feliz cumpleaños, {nombre}! 🎂', 'gn' => '¡Vy\'apavẽ nde arambotýre, {nombre}! 🎂'],   // COM-CUM-01
        'frase'              => ['es' => 'Mati y tus amigos de la selva te desean un día lleno de alegría.', 'gn' => 'Mati ha ne irũnguéra ka\'aguypegua oipota ndéve peteĩ ára henyhẽva vy\'águi.'],   // COM-CUM-02
    ];

    /** D. Ingreso de estudiantes (código y PIN) — COM-LOG-nn */
    public const INGRESO = [
        'pestana'            => ['es' => '¡A Jugar! — MatiGuarã', 'gn' => '¡Ñañembosarái! — MatiGuarã'],   // COM-LOG-01
        'titulo'             => ['es' => '¡A jugar!', 'gn' => '¡Ñañembosarái!'],   // COM-LOG-02
        'subtitulo'          => ['es' => 'Ingresá tu código y tu PIN secreto', 'gn' => 'Emoinge nde código ha nde PIN ñemimby'],   // COM-LOG-03
        'burbujaInicio'      => ['es' => '¡Hola! Escribí tu código.', 'gn' => '¡Mba\'éichapa! Ehai nde código.'],   // COM-LOG-04
        'etCodigo'           => ['es' => '🪪 Tu código de acceso', 'gn' => '🪪 Nde código jeikeha'],   // COM-LOG-05
        'ayudaCodigo'        => ['es' => '📋 Tu maestra o maestro te dio este código', 'gn' => '📋 Ne mbo\'ehára ome\'ẽ ndéve ko código'],   // COM-LOG-06
        'etPin'              => ['es' => '🔐 Tu PIN secreto (4 números)', 'gn' => '🔐 Nde PIN ñemimby (4 papapy)'],   // COM-LOG-07
        'botonEntrar'        => ['es' => '🚀 ¡Entrar a jugar!', 'gn' => '🚀 ¡Eike eñembosarái!'],   // COM-LOG-09
        'burbujaPin'         => ['es' => 'Ahora tu PIN secreto.', 'gn' => 'Ko\'ágã nde PIN ñemimby.'],   // COM-LOG-10
        'burbujaSigue'       => ['es' => '¡Seguí! Te faltan {n} números.', 'gn' => '¡Eseguí katu! Ofalta ndéve {n} papapy.'],   // COM-LOG-11
        'burbujaListo'       => ['es' => '¡Listo! Tocá «Entrar a jugar».', 'gn' => '¡Oĩmbáma! Epoko «Eike eñembosarái».'],   // COM-LOG-12
        'burbujaFaltaCodigo' => ['es' => 'Falta tu código de acceso.', 'gn' => 'Ofalta nde código jeikeha.'],   // COM-LOG-13
        'avisoSinCodigo'     => ['es' => '🪪 Ingresá tu código de acceso primero.', 'gn' => '🪪 Emoinge raẽ nde código jeikeha.'],   // COM-LOG-14
        'burbujaSinCodigo'   => ['es' => '¿Cuál es tu código?', 'gn' => '¿Mba\'épa nde código?'],   // COM-LOG-15
        'avisoPinCorto'      => ['es' => '🔐 Ingresá tu PIN de 4 números.', 'gn' => '🔐 Emoinge nde PIN 4 papapy rehegua.'],   // COM-LOG-16
        'burbujaPinCorto'    => ['es' => 'Faltan números del PIN.', 'gn' => 'Ofalta papapy PIN-pe.'],   // COM-LOG-17
        'burbujaError'       => ['es' => '¡Uy! Probemos de nuevo.', 'gn' => '¡Áina! Ñaha\'ã jey.'],   // COM-LOG-18
        'bienvenida'         => ['es' => '¡Hola, {nombre}! 🎮', 'gn' => '¡Mba\'éichapa, {nombre}! 🎮'],   // COM-LOG-26
        'quienSos'           => ['es' => '¿Quién sos?', 'gn' => '¿Mávapa nde?'],   // COM-LOG-27
        'estasAca'           => ['es' => 'Estás acá', 'gn' => 'Reĩ ko\'ápe'],   // COM-LOG-29
        'acerca'             => ['es' => 'ℹ️ Acerca de MatiGuarã', 'gn' => 'ℹ️ MatiGuarã rehegua'],   // COM-LOG-30
    ];

    /** COM-LOG-08 — botones del teclado */
    public const TECLADO = [
        'borrarUltimo' => ['es' => 'Borrar el último número', 'gn' => 'Embogue papapy paha'],
        'borrarTodo'   => ['es' => 'Borrar todo',             'gn' => 'Embogue paite'],
    ];

    /** COM-LOG-28 — selector de ingreso: nombre y forma de entrar de cada perfil */
    public const ROLES = [
        'estudiante' => ['nombre' => ['es' => 'Estudiante', 'gn' => "Temimbo'e"],
                         'forma'  => ['es' => 'Con mi código y PIN', 'gn' => 'Che código ha PIN ndive']],
        'docente'    => ['nombre' => ['es' => 'Docente o administrador/a', 'gn' => "Mbo'ehára térã moakãhára"],
                         'forma'  => ['es' => 'Con correo y contraseña', 'gn' => "Correo ha ñe'ẽñemi ndive"]],
        'familia'    => ['nombre' => ['es' => 'Familia', 'gn' => 'Ogaygua'],
                         'forma'  => ['es' => 'Con correo y código', 'gn' => 'Correo ha código ndive']],
    ];

    /** E. Acceso de familias — COM-FAM-nn */
    public const FAMILIA = [
        'titulo'             => ['es' => 'Acceso para familias', 'gn' => 'Ogayguakuéra jeikeha'],   // COM-FAM-01
        'subtitulo'          => ['es' => 'Ver cómo le va a mi hijo/a', 'gn' => 'Ahecha mba\'éichapa oho che memby'],   // COM-FAM-02
    ];

    /**
     * Errores del ingreso de estudiantes (COM-LOG-19..25): castellano => guaraní.
     * El castellano es EXACTAMENTE el mensaje que arma LoginEstudianteController.
     */
    public const ERRORES_INGRESO = [
        'Ingresá tu código de acceso.' =>
            'Emoinge nde código jeikeha.',   // COM-LOG-19
        'Ingresá tu PIN de 4 números.' =>
            'Emoinge nde PIN 4 papapy rehegua.',   // COM-LOG-20
        'El PIN debe tener exactamente 4 números.' =>
            'Pe PIN oguereko va\'erã 4 papapy hekopete.',   // COM-LOG-21
        'El PIN debe tener solo números.' =>
            'Pe PIN oguereko va\'erã papapy añónte.',   // COM-LOG-22
        'Probaste muchas veces. Esperá un minuto y volvé a intentar, o pedile ayuda a tu maestra.' =>
            'Reha\'ã hetáma. Eha\'arõ peteĩ aravo\'i ha eha\'ã jey, térã ejerure pytyvõ ne mbo\'ehárape.',   // COM-LOG-23
        'Tu cuenta no está activa por ahora. Pedile ayuda a tu maestra.' =>
            'Nde cuenta ndoikói ko\'ágã. Ejerure pytyvõ ne mbo\'ehárape.',   // COM-LOG-24
        'El código o el PIN no son correctos. Pedile ayuda a tu maestra.' =>
            'Pe código térã PIN noĩporãi. Ejerure pytyvõ ne mbo\'ehárape.',   // COM-LOG-25
    ];

    /** Guaraní de un error del ingreso, o null si ese mensaje no tiene versión. */
    public static function errorIngresoGn(?string $es): ?string
    {
        return $es !== null ? (self::ERRORES_INGRESO[$es] ?? null) : null;
    }

    /** Cambia {n}, {nombre}… en un texto. */
    public static function rellenar(string $texto, array $vars): string
    {
        $buscar = [];
        $poner  = [];
        foreach ($vars as $k => $v) {
            $buscar[] = '{' . $k . '}';
            $poner[]  = (string) $v;
        }
        return str_replace($buscar, $poner, $texto);
    }

    /** Un texto en las dos lenguas, para el título de la pestaña o el aria-label: «es · gn». */
    public static function unido(array $t): string
    {
        return $t['es'] . ' · ' . $t['gn'];
    }

    /** Lo que necesita el script del ingreso (globos de Mati y avisos). */
    public static function ingresoParaJs(): array
    {
        return array_intersect_key(self::INGRESO, array_flip([
            'burbujaInicio', 'burbujaPin', 'burbujaSigue', 'burbujaListo', 'burbujaFaltaCodigo',
            'avisoSinCodigo', 'burbujaSinCodigo', 'avisoPinCorto', 'burbujaPinCorto', 'burbujaError',
        ]));
    }
}
