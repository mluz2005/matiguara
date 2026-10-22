<?php

namespace App\Support;

/**
 * AYUDA PARA ADULTOS — docentes, familias y administración
 * =========================================================================
 * Todos los textos de la pantalla "Ayuda" de los adultos están ACÁ, en un solo
 * archivo, para poder corregirlos sin tocar nada más.
 *
 * Cada sección:
 *   id      → ancla de la página (ayuda#crear-cuenta). Se usa para enlazar desde otras pantallas.
 *   icono   → ícono de Bootstrap Icons (sin el prefijo "bi-")
 *   titulo  → título en castellano
 *   gn      → (opcional) título corto en guaraní. ⚠️ BORRADOR: validar con una persona hablante.
 *   intro   → (opcional) frase de introducción
 *   pasos   → (opcional) lista numerada
 *   notas   → (opcional) lista con viñetas
 * En los textos, **así** se escribe en negrita.
 * =========================================================================
 */
class AyudaAdultos
{
    /** Secciones según el perfil: 'docente', 'padre' o 'admin'. */
    public static function secciones(string $rol): array
    {
        return match ($rol) {
            'docente' => self::docente(),
            'padre'   => self::padre(),
            default   => self::admin(),
        };
    }

    public static function titulo(string $rol): array
    {
        return match ($rol) {
            'docente' => ['es' => 'Ayuda para docentes',        'gn' => 'Pytyvõ'],
            'padre'   => ['es' => 'Ayuda para padres y madres', 'gn' => 'Pytyvõ'],
            default   => ['es' => 'Ayuda para administración',  'gn' => 'Pytyvõ'],
        };
    }

    /** Convierte **negrita** en <strong>, escapando antes todo lo demás. */
    public static function formatear(string $texto): string
    {
        $seguro = e($texto);
        return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $seguro);
    }

    // ---------------------------------------------------------------------
    // DOCENTES
    // ---------------------------------------------------------------------
    private static function docente(): array
    {
        return [
            ['id' => 'primeros-pasos', 'icono' => 'flag-fill', 'titulo' => 'Primeros pasos', 'gn' => 'Eñepyrũ',
             'intro' => 'Para empezar con tu clase:',
             'pasos' => [
                 'Entrá a **Cuentas** y creá la cuenta de cada estudiante (te lo explico en la sección siguiente).',
                 'Anotá el **código de acceso** de cada niño o niña y entregáselo junto con su PIN.',
                 'Cargá el **correo de su padre, madre o tutor** (al crear la cuenta o después, en **Editar**): con ese correo y el código del niño, la familia entra a ver su avance.',
                 'Los niños entran desde la pantalla de inicio, tocando **¡Quiero jugar!**, y escriben su código y su PIN.',
                 'Cuando hayan jugado, mirá su avance en **Mis Estudiantes → Ver progreso**.',
                 'Si querés, revisá en **Configurar Juegos** qué juegos ve tu grado.',
             ]],

            ['id' => 'crear-cuenta', 'icono' => 'person-plus-fill', 'titulo' => 'Crear la cuenta de un estudiante',
             'pasos' => [
                 'En el menú, entrá a **Cuentas** (o tocá el botón **Nueva cuenta** en Mi Panel o en Mis Estudiantes).',
                 'Completá **Nombre** y **Apellido**.',
                 'Elegí el **grado en el que empieza** (1.°, 2.° o 3.°). Viene marcado el de tu clase, pero si el niño o la niña ya cursa 2.° o 3.°, elegilo acá y juega con los niveles de ese grado desde el primer día.',
                 'Elegí un **PIN de 4 números, junto con el estudiante**, y escribilo de nuevo en **Confirmar PIN**.',
                 'Escribí el **correo del padre, madre o tutor**. Es opcional: si hoy no lo tenés, lo cargás después.',
                 'Tocá **Crear cuenta y matrícula**.',
                 'La cuenta aparece abajo, en **Cuentas registradas**, con su **código de acceso**. Anotalo.',
                 'Tocá **Ficha** en su fila para imprimir la **hoja de acceso** (código y pasos para el niño y para la familia). **Imprimir fichas del grado** las imprime todas juntas, dos por hoja.',
             ],
             'notas' => [
                 'El PIN no se puede volver a ver. Si el niño lo olvida, se cambia por uno nuevo (ver "Cambiar el PIN").',
                 'Justo después de crear la cuenta (o de cambiar un PIN) aparece el botón **Imprimir ficha con el PIN**: el PIN se muestra **una sola vez**. Si abrís la ficha más tarde, el PIN no sale y tiene casillas para anotarlo a mano junto con el niño o la niña.',
                 'Si no cambiás el grado, el estudiante queda matriculado en el grado de tu clase. Cada alumno puede estar en un grado distinto: la columna **Grado** de la lista lo muestra.',
                 'Si dos hermanos tienen el **mismo correo de familia**, la familia los ve a los dos con un solo ingreso.',
                 'El correo de una cuenta de docente o de administración **no se puede usar** como correo de familia.',
             ]],

            ['id' => 'entrada-nino', 'icono' => 'box-arrow-in-right', 'titulo' => 'Cómo entra un niño o una niña a jugar',
             'pasos' => [
                 'En la pantalla de inicio, tocar **¡Quiero jugar!**.',
                 'Escribir el **código de acceso** que le dio la docente.',
                 'Tocar los 4 números de su **PIN secreto** en el teclado de la pantalla.',
                 'Tocar **¡Entrar a jugar!**.',
             ],
             'notas' => [
                 'Los niños tienen en el menú de juegos un botón **❓ ¿Cómo se juega?** en cada juego, con dibujos y audio.',
             ]],

            ['id' => 'editar-cuenta', 'icono' => 'pencil-square', 'titulo' => 'Editar los datos de un estudiante: nombre, correo de la familia, PIN, desactivar o reactivar',
             'pasos' => [
                 'Entrá a **Cuentas** y **tocá al estudiante** en la lista **Cuentas registradas** (o tocá su botón **Editar**).',
                 'Sus datos se completan solos en la columna de la izquierda, en **Datos del alumno**.',
                 'Cambiá lo que necesites: **Nombre**, **Apellido**, **Correo del padre, madre o tutor** o **Nuevo PIN** (dejalo vacío si no querés cambiar el PIN).',
                 'Tocá **Guardar cambios**.',
                 'Para volver al formulario de alta, tocá **Nuevo alumno**.',
             ],
             'notas' => [
                 'Debajo de cada nombre se ve el correo de la familia, o el aviso **Sin correo de la familia**.',
                 'El botón **Desactivar cuenta** (o **Reactivar cuenta**) está en el mismo panel. Una cuenta **inactiva no puede entrar a jugar**: el niño ve el mensaje «Tu cuenta no está activa por ahora. Pedile ayuda a tu maestra». Si la desactivás mientras está jugando, se le cierra la sesión en su próxima acción.',
                 'Si **cambiás o quitás el correo de la familia**, la cuenta que tenía el correo anterior deja de ver a ese niño o niña.',
                 'Si cambiás el PIN, aparece el botón **Imprimir ficha con el PIN**: el PIN se muestra **una sola vez**.',
             ]],

            ['id' => 'cerrar-anio', 'icono' => 'mortarboard-fill', 'titulo' => 'Cerrar el año de un estudiante: pase de grado o egreso',
             'pasos' => [
                 'Entrá a **Cuentas** y **tocá al estudiante** en la lista.',
                 'En el panel **Datos del alumno**, tocá el botón **Cerrar año** (dice «pasa a 2.° grado», «pasa a 3.° grado» o «egresa de 3.er grado»).',
                 'Confirmá el aviso. El sistema te muestra el resultado arriba de la pantalla.',
             ],
             'notas' => [
                 'Los alumnos de **1.° y 2.° grado pasan al grado siguiente**, con la misma institución y el mismo docente. El pase solo es posible si ya hay niveles de juego cargados para ese grado.',
                 'Los alumnos de **3.° grado egresan**: ya no pueden jugar, pero ven su progreso hasta que la cuenta se desactiva sola. Esa fecha es el día del cierre más los **meses de conservación** que define la administración (4 por defecto). El historial no se borra.',
                 'En **Ver progreso** podés revisar todos los grados que cursó el alumno. Los estudiantes solo ven sus grados anteriores si la institución lo habilitó.',
             ]],

            ['id' => 'juegos', 'icono' => 'controller', 'titulo' => 'Elegir qué juegos ve tu grado',
             'pasos' => [
                 'En el menú, entrá a **Configurar Juegos**.',
                 'Activá o desactivá cada juego con su interruptor.',
                 'Tocá **Guardar configuración**.',
             ],
             'notas' => [
                 'Los juegos desactivados **no aparecen** en la pantalla de tus estudiantes.',
                 'La configuración vale para tu grado.',
             ]],

            ['id' => 'progreso', 'icono' => 'bar-chart-fill', 'titulo' => 'Ver el avance de un estudiante',
             'pasos' => [
                 'Entrá a **Mis Estudiantes**: ves cuántas sesiones jugó cada uno, su porcentaje de aciertos y si la cuenta está activa.',
                 'Tocá **Ver progreso** en la fila del estudiante.',
                 'Vas a ver el **Progreso por nivel** de cada juego, las **Últimas sesiones** y los **Logros obtenidos**.',
             ]],

            ['id' => 'abrir-niveles', 'icono' => 'unlock-fill', 'titulo' => 'Abrir niveles a un estudiante',
             'intro' => 'Los niveles se abren solos cuando el niño supera el anterior. Pero si un alumno avanza más rápido, o quedó trabado, podés abrírselos vos.',
             'pasos' => [
                 'Entrá a **Mis Estudiantes → Ver progreso** del alumno.',
                 'Buscá la tarjeta **Niveles de (nombre del alumno)**.',
                 'Tocá **Abrir** en un nivel cerrado, o **Abrir todos los niveles** de un juego.',
                 'Aceptá la pregunta de confirmación.',
             ]],

            ['id' => 'reglas', 'icono' => 'graph-up-arrow', 'titulo' => 'Cómo suben y bajan los niveles',
             'notas' => [
                 '**Suma de Frutas y Flash Matemático:** cada partida tiene 5 ejercicios. Con **3 respuestas correctas seguidas** el niño sube un nivel; con **2 errores seguidos** baja uno. Un nivel se considera **superado** cuando se aciertan al menos 3 de los 5 ejercicios, y entonces se abre el siguiente.',
                 '**Encuentra el par:** el nivel se supera encontrando todos los pares antes de que se acabe el tiempo. Con **3 partidas ganadas seguidas** sube de nivel; con **2 partidas perdidas seguidas** baja.',
                 '**Flash Matemático:** los números se ven unos segundos y desaparecen; el niño tiene que recordarlos. En los niveles más altos hay tres números y restas.',
                 'Al superar un nivel, la pantalla final ofrece el botón **¡Siguiente nivel!**. En **Encuentra el par**, cuando el niño cambia de nivel (sube o baja), el juego **pasa solo al nivel nuevo**, sin pantalla final, y le muestra un aviso con las medallas que ganó.',
                 'Las **medallas** se ganan solas, según cómo juega cada niño.',
             ]],

            ['id' => 'diagnostico', 'icono' => 'robot', 'titulo' => 'Diagnóstico con IA',
             'pasos' => [
                 'Entrá a **Mis Estudiantes → Ver progreso** del alumno y tocá **Solicitar diagnóstico IA**.',
                 'Si querés, elegí un juego en **Analizar juego específico**. Si lo dejás vacío, se analiza el desempeño global.',
                 'Tocá **Generar diagnóstico** y aceptá la confirmación.',
                 'El resultado queda guardado en el **Historial de diagnósticos**, donde podés tocar **Ver completo**.',
             ],
             'notas' => [
                 'El estudiante necesita **al menos 1 sesión completada**.',
                 'Cada diagnóstico usa recursos de la IA: pedilo solo cuando lo necesites.',
                 'La IA recibe **solo datos numéricos anonimizados**. Nunca se envía el nombre ni el código del estudiante.',
                 'El informe describe patrones de desempeño matemático. **No es un diagnóstico médico, clínico ni psicopedagógico.** Para evaluaciones especializadas, consultá a un profesional.',
             ]],

            ['id' => 'preguntas', 'icono' => 'question-circle-fill', 'titulo' => 'Preguntas frecuentes', 'gn' => 'Porandu',
             'notas' => [
                 '**Un niño olvidó su PIN.** Cuentas → tocá al niño en la lista → escribí el Nuevo PIN → Guardar cambios.',
                 '**No recuerdo el código de un niño.** Está en Cuentas, en la lista Cuentas registradas, columna Código.',
                 '**Una familia no puede entrar.** Revisá en Cuentas que el correo de la familia esté cargado y bien escrito (bajo el nombre del niño) y que el código sea el correcto. Podés corregirlo en Editar.',
                 '**Un niño no ve un juego.** Revisá Configurar Juegos: puede estar desactivado para tu grado.',
                 '**Un nivel aparece con 🔒.** Se abre cuando el niño supera el anterior, o lo abrís vos desde Ver progreso.',
                 '**Un estudiante no aparece en la lista.** Revisá que su cuenta esté creada en Cuentas y que figure como Activo.',
             ]],
        ];
    }

    // ---------------------------------------------------------------------
    // PADRES Y MADRES — lenguaje muy simple, pasos cortos
    // ---------------------------------------------------------------------
    private static function padre(): array
    {
        return [
            ['id' => 'ingresar', 'icono' => 'box-arrow-in-right', 'titulo' => 'Cómo entrar', 'gn' => 'Eike',
             'intro' => 'Entrás con dos datos. No hay contraseña que recordar.',
             'pasos' => [
                 'En la pantalla de inicio, tocá **Soy padre/madre**.',
                 'Escribí tu **correo electrónico**: el mismo que le diste a la docente.',
                 'Escribí el **código de tu hijo o hija**. Es el mismo que él o ella usa para jugar (por ejemplo, SAN-1A-007).',
                 'Tocá **Ingresar**.',
             ],
             'notas' => [
                 'Si usás un teléfono o computadora de otra persona, tocá **Salir** cuando termines.',
                 'Si el sistema dice que el correo o el código no son correctos, revisá que estén escritos igual que en el papel de la docente. Si sigue sin funcionar, pedile que revise el correo que cargó.',
                 'Después de varios intentos equivocados, hay que esperar un minuto antes de volver a probar.',
             ]],

            ['id' => 'vincular', 'icono' => 'link-45deg', 'titulo' => 'Más de un hijo o hija',
             'intro' => 'Si la docente cargó **el mismo correo** para tus hijos, los ves a todos con un solo ingreso.',
             'pasos' => [
                 'Entrá como siempre, con tu correo y el código de **cualquiera** de tus hijos.',
                 'Arriba del panel aparecen los **nombres de tus hijos**.',
                 'Tocá el nombre del que querés ver.',
             ],
             'notas' => [
                 'Solo ves a los niños que tienen **tu correo** cargado. Si falta alguno, pedile a la docente que le cargue tu correo.',
                 'Si tu correo cambió, la docente puede actualizarlo.',
             ]],

            ['id' => 'panel', 'icono' => 'house-heart-fill', 'titulo' => 'Qué muestra tu panel',
             'notas' => [
                 '**Veces que jugó:** cuántas partidas completó.',
                 '**Respuestas bien:** el porcentaje de respuestas correctas.',
                 '**Días seguidos:** cuántos días seguidos jugó.',
                 '**Últimas partidas:** lo que jugó últimamente, con sus estrellas. Si aparece **¡Nivel superado!**, pasó ese nivel.',
                 '**Nota de la docente:** un mensaje de la docente, si dejó alguno.',
                 'Los datos se actualizan en el momento.',
             ]],

            ['id' => 'medallas', 'icono' => 'trophy-fill', 'titulo' => 'Qué son las medallas',
             'intro' => 'Son premios que tu hijo o hija gana jugando, por ejemplo por jugar varios días seguidos o por superar niveles.',
             'notas' => [
                 'Para verlas todas, tocá **Ver todos** en **Logros obtenidos**.',
                 'Cada medalla tiene su dibujo y se gana sola, sin que nadie tenga que hacer nada.',
             ]],

            ['id' => 'ayudar', 'icono' => 'chat-heart-fill', 'titulo' => 'Cómo acompañar a tu hijo o hija',
             'notas' => [
                 'Funciona mejor jugar **poco tiempo, pero seguido**, que mucho tiempo un solo día.',
                 'Felicitá el **esfuerzo**, no solo el resultado. Equivocarse es parte de aprender.',
                 'Si se traba, preguntale: «¿cómo lo pensaste?». Explicarlo lo ayuda a entender.',
                 'Si ves que el juego le resulta muy difícil o muy fácil, contale a la docente.',
             ]],

            ['id' => 'preguntas', 'icono' => 'question-circle-fill', 'titulo' => 'Preguntas frecuentes', 'gn' => 'Porandu',
             'notas' => [
                 '**No veo datos todavía.** Es normal si tu hijo o hija no jugó ninguna partida. Cuando juegue, aparecerá.',
                 '**El código o el correo no funcionan.** Revisá que estén escritos igual que en el papel de la docente. Si sigue sin funcionar, pedile que confirme el correo que cargó.',
                 '**No veo a uno de mis hijos.** Hablá con la docente: tiene que cargar tu correo en la cuenta de ese hijo o hija.',
                 '**Quiero usar otro correo.** Hablá con la docente: ella lo cambia y, desde ese momento, entrás con el correo nuevo.',
                 '**¿Qué datos de mi hijo o hija se guardan?** Mirá la nota en la pantalla «Acerca de MatiGuará».',
             ]],
        ];
    }

    // ---------------------------------------------------------------------
    // ADMINISTRACIÓN
    // ---------------------------------------------------------------------
    private static function admin(): array
    {
        return [
            ['id' => 'secciones', 'icono' => 'gear-fill', 'titulo' => 'Qué hay en el panel de administración',
             'notas' => [
                 '**Instituciones:** registrar, editar y configurar instituciones educativas.',
                 '**Docentes:** crear cuentas, asignarlas a instituciones y gestionar bajas.',
                 '**Mensajes bilingüe:** catálogo de mensajes de retroalimentación en castellano y guaraní.',
                 '**Juegos:** catálogo de juegos y niveles del sistema.',
             ]],
        ];
    }
}
