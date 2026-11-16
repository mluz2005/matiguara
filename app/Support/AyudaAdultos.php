<?php

namespace App\Support;

use App\Services\NumerosService;
use App\Services\TamizajeInformeService;
use App\Services\TamizajeService;
use App\Support\Numeros\GeneradorItems;
use App\Support\Numeros\TextosTamizaje;

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
 *   grupo   → (opcional) subtítulo que se muestra ARRIBA de esta sección, para agrupar las que siguen
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
                 'Si querés, elegí la **fecha de nacimiento** (día, mes y año). Es opcional y sirve solo para que **Mati lo salude el día de su cumpleaños**.',
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
                 'Cambiá lo que necesites: **Nombre**, **Apellido**, **Correo del padre, madre o tutor**, **Fecha de nacimiento** (para quitarla, dejá los tres campos vacíos) o **Nuevo PIN** (dejalo vacío si no querés cambiar el PIN).',
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

            // ─── «MATI Y LOS NÚMEROS» (Atención numérica) ───────────────────────────
            // Los números de estas secciones (cortes, tiempos, límites) se toman de las
            // mismas constantes que usa el sistema, así la ayuda no se desactualiza.
            ...self::numeros(),

            ['id' => 'reglas', 'icono' => 'graph-up-arrow', 'grupo' => 'Otros juegos', 'titulo' => 'Cómo suben y bajan los niveles',
             'notas' => [
                 '**Suma de Frutas y Flash Matemático:** cada partida tiene 5 ejercicios. Con **3 respuestas correctas seguidas** el niño sube un nivel; con **2 errores seguidos** baja uno. Un nivel se considera **superado** cuando se aciertan al menos 3 de los 5 ejercicios, y entonces se abre el siguiente.',
                 '**Encuentra el par:** el nivel se supera encontrando todos los pares antes de que se acabe el tiempo. Con **3 partidas ganadas seguidas** sube de nivel; con **2 partidas perdidas seguidas** baja.',
                 '**El camino a la escuela:** el niño lleva a su personaje de la casa a la escuela y arregla con números los obstáculos del camino (cada uno es un problema con su historia). Mientras camina puede juntar chipas en los callejones. Las estrellas dependen de la ayuda: **3** si llega sin ayuda, **2** si usó pistas o la flecha, **1** si tuvo que ver la solución. Un camino se **supera** si el niño vio la solución en **un obstáculo como máximo**; con **3 caminos superados seguidos** sube de nivel y con **2 no superados seguidos** baja. Si se equivoca, el juego le da una pista; si se equivoca de nuevo, le muestra cómo se resuelve y le da después un problema parecido.',
                 '**Flash Matemático:** los números se ven unos segundos y desaparecen; el niño tiene que recordarlos. En los niveles más altos hay tres números y restas.',
                 'Al superar un nivel, la pantalla final ofrece el botón **¡Siguiente nivel!**. En **Encuentra el par**, cuando el niño cambia de nivel (sube o baja), el juego **pasa solo al nivel nuevo**, sin pantalla final, y le muestra un aviso con las medallas que ganó.',
                 '**Tiempo de juego y descanso (todos los juegos):** para cuidar la vista y la atención, cada sesión tiene un tiempo máximo (8 minutos por defecto) y, después, una pausa antes de volver a jugar (30 minutos por defecto). El niño siempre termina el ejercicio que está resolviendo. Ambos valores se cambian en **Configurar juegos → Tiempo de juego** (0 = sin límite).',
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
    // «MATI Y LOS NÚMEROS» — guía completa para la docente (Atención numérica)
    // ---------------------------------------------------------------------
    // Los valores (cortes, tiempos, límites) salen de las constantes del sistema:
    // si cambian allá, esta ayuda se actualiza sola.
    private static function numeros(): array
    {
        $viajesMin  = TamizajeService::MIN_CICLOS * TamizajeService::VIAJES_POR_CICLO;      // 6
        $ventana    = TamizajeService::VENTANA_CICLOS;                                       // 3
        $verde      = TamizajeService::CORTE_VERDE_DEFECTO;                                  // 75
        $amarillo   = TamizajeService::CORTE_AMARILLO_DEFECTO;                               // 60
        $rapido     = self::coma(NumerosService::AZAR_MS / 1000, 2);                         // 0,35
        $rapidoPct  = NumerosService::AZAR_MAX_PORCENTAJE;                                   // 35
        $t1 = GeneradorItems::totalDelViaje(1);
        $t2 = GeneradorItems::totalDelViaje(2);
        $t3 = GeneradorItems::totalDelViaje(3);
        $toquesCal  = GeneradorItems::PARTES[1]['fijas'];                                    // 4
        $ms         = GeneradorItems::PUNTOS_RAPIDOS_MS;
        $minArea    = TamizajeService::MIN_RESPUESTAS_AREA;
        $ceroMin    = TamizajeService::CERO_MIN;
        $ceroPct    = TamizajeService::CERO_PCT_ERROR;
        $bajoMin    = TamizajeService::NIVEL_BAJO_MIN;
        $bajoPct    = TamizajeService::NIVEL_BAJO_PCT;
        $espera     = TamizajeInformeService::ESPERA_SEGUNDOS;
        $notaMax    = TextosTamizaje::FICHA_NOTA_MAX;

        return [
            // ── 1. Qué es y cómo se usa ─────────────────────────────────────
            ['id' => 'atencion-numerica', 'icono' => 'eye', 'grupo' => 'Atención numérica · «Mati y los números»',
             'titulo' => 'Qué es y cómo se usa',
             'intro' => '«Mati y los números» es un juego para niños de 1.° a 3.° grado. Mientras juegan unos minutos por día, el sistema anota cómo responden y te muestra, en **Atención numérica**, un **semáforo** que te ayuda a decidir **a quién mirar con más atención** en matemática. Sirve para darse cuenta a tiempo, antes de que una base débil se arrastre año tras año.',
             'pasos' => [
                 'Dejá que cada niño haga **un viaje por día**, en un momento tranquilo y **sin que nadie le ayude** a responder. Un viaje son unos minutos de juegos cortos con Mati.',
                 'Esperá a que complete **' . TamizajeService::MIN_CICLOS . ' ciclos (' . $viajesMin . ' viajes)**: recién ahí aparece su semáforo. Antes de eso verás ⚪ «Todavía sin datos suficientes».',
                 'Entrá a **Atención numérica** (en el menú) al menos **una vez por semana**, y cada vez que suene la **campanita** de arriba.',
                 'Tocá **Ver** en los niños que salgan 🟡 o 🔴: mirá en qué área bajaron, las señales extra y las respuestas.',
                 'Anotá lo que observás en el aula en la **ficha de observación** del niño.',
                 'Si lo ves oportuno, conversá con la dirección y con la familia. Volvé a mirar el semáforo a las pocas semanas.',
             ],
             'notas' => [
                 '**No es un diagnóstico, no es un examen y no sirve para calificar ni para comparar niños entre sí.** Es una llamada de atención. Un color puede deberse al cansancio, a la distracción o a un mal día: **lo que vos observás en el aula pesa más**.',
                 '**Solo vos lo ves.** El semáforo y el informe aparecen únicamente en tu panel de docente; las familias no los ven. Un color sin explicación puede asustar o tranquilizar sin motivo. Sos vos quien decide si conversa con la familia y cómo.',
                 '**No reemplaza a un profesional.** Si pese al apoyo en el aula la dificultad persiste, consultar a un profesional es una decisión de la familia, con orientación de la dirección. La herramienta no la toma ni la recomienda.',
                 '**Está en etapa de piloto.** Todavía no fue validada con niños paraguayos y los cortes del semáforo son provisorios: un niño con buenas notas puede salir amarillo y uno con dificultades puede salir verde. Por eso tu mirada es lo que completa el semáforo.',
                 '**No suma puntos, medallas ni rachas** y no aparece en **Ver progreso**: sus resultados se ven solo en **Atención numérica**. Si querés, podés desactivarlo para tu grado en **Configurar Juegos**.',
                 'La lista de **Atención numérica** trae, al pie, un resumen de cómo se calcula. Las secciones siguientes lo explican con más detalle.',
             ]],

            // ── 2. Qué hacen los niños ──────────────────────────────────────
            ['id' => 'numeros-juego', 'icono' => 'map',
             'titulo' => 'Qué hacen los niños en cada viaje',
             'intro' => 'Cada viaje es una serie corta de juegos con Mati por la selva. Hay **3 viajes distintos** y los tres, uno de cada tipo, forman un **ciclo**. Antes de cada parte Mati explica qué hay que hacer, en castellano o en guaraní.',
             'notas' => [
                 '**Viaje 1 · Los símbolos** (unas ' . $t1 . ' preguntas): ver un número y tocar el montón de ramitas que tiene esa cantidad, ver ramitas y tocar su número, y decir cuál de dos números es más grande. Mide el área **Números y cantidades**.',
                 '**Viaje 2 · Las cantidades** (unas ' . $t2 . ' preguntas): decir **dónde hay más puntos sin contar**, ver puntos que aparecen un instante y decir cuántos eran, y ubicar un número en una línea. Mide el área **Sentido de la cantidad**.',
                 '**Viaje 3 · Los signos** (unas ' . $t3 . ' preguntas): elegir el **signo que falta** en una cuenta (más o menos), resolver cuentas con palillos y completar el número que falta en una secuencia. Mide **Signos y cálculo** y, con la secuencia, también **Números y cantidades**.',
                 '**Calentamiento (en todos los viajes):** ' . $toquesCal . ' toques sobre una luciérnaga. Sirve para ver cómo responde el niño al tocar la pantalla. **No cuenta para el semáforo.**',
                 '**Preguntas fijas y adaptativas:** en cada parte, unas preguntas son **iguales para todos los niños del grado** (así se pueden comparar con el tiempo) y otras **se adaptan**: si el niño falla, vienen más fáciles; si acierta, más difíciles. Por eso el sistema también puede notar si un niño necesita casi siempre las más fáciles.',
                 '**Puntos rápidos:** los puntos se ven **' . self::coma($ms[1] / 1000) . ' s en 1.° grado, ' . self::coma($ms[2] / 1000) . ' s en 2.° y ' . self::coma($ms[3] / 1000) . ' s en 3.°**, y después desaparecen. La idea es captar la cantidad de un vistazo. Cada viaje guarda con qué tiempo se hizo.',
                 '**Los números crecen con el grado:** en 1.° aparecen números chicos (hasta 5, 10 o 15 según el nivel), en 2.° hasta 20, 50 o 100, y en 3.° hasta 100, 500 o 1000. La docente no tiene que configurar nada: se arma según el grado en que está matriculado el niño.',
                 '**Los viajes se abren en orden** (primero el 1, después el 2 y el 3) y se sugiere **uno por día**: si el niño ya hizo el de hoy, Mati lo invita a seguir mañana y a jugar otros juegos, aunque puede tocar **Seguir igual**.',
                 'Si un viaje se corta (se acaba el tiempo de juego, el niño sale), queda **incompleto** y **no cuenta** hasta que lo haga completo.',
                 'Los textos en guaraní son una **primera versión** y deben ser revisados por personas hablantes.',
             ]],

            // ── 3. Cómo leer el semáforo ────────────────────────────────────
            ['id' => 'numeros-semaforo', 'icono' => 'circle-fill',
             'titulo' => 'Cómo leer el semáforo',
             'intro' => 'El semáforo resume lo que se ve en los últimos viajes del niño. Nunca dice que el niño «tiene» algo: dice **dónde conviene mirar**. El color siempre viene con un ícono y con palabras.',
             'notas' => [
                 '🟢 **Sin señales por ahora.** Seguí observando como siempre.',
                 '🟡 **Mirar con más atención.** Hay algunas señales. Observá cómo trabaja con los números en el aula y practicá con material concreto (semillas, fichas, palillos).',
                 '🔴 **Mirar con más atención y conversar.** Hay varias señales. Observá, registrá lo que ves y, si lo ves oportuno, conversá con la dirección y con la familia.',
                 '⚪ **Todavía sin datos suficientes.** Faltan viajes: hacen falta **' . TamizajeService::MIN_CICLOS . ' ciclos completos (' . $viajesMin . ' viajes)** válidos.',
             ]],

            ['id' => 'numeros-calculo', 'icono' => 'calculator',
             'titulo' => 'Cómo se calcula el color',
             'pasos' => [
                 'Se usan solo los viajes **completos, válidos y confiables**. Hace falta haber completado al menos **' . TamizajeService::MIN_CICLOS . ' ciclos**.',
                 'Se miran los **últimos ' . $ventana . ' ciclos** (hasta ' . ($ventana * TamizajeService::VIAJES_POR_CICLO) . ' viajes). Cuando el niño mejora, los ciclos viejos dejan de contar y el color puede mejorar.',
                 'Se calcula el **porcentaje de aciertos de cada área** (promedio de esos ciclos). Un área necesita al menos ' . $minArea . ' respuestas por ciclo y datos de ' . TamizajeService::MIN_CICLOS . ' ciclos para tener porcentaje.',
                 'El color del niño es el del **área más baja**: **verde** desde ' . $verde . ' %, **amarillo** desde ' . $amarillo . ' %, **rojo** por debajo. Los valores de tu institución están escritos al pie de la lista de Atención numérica.',
                 'Si el color sale verde pero hay una **señal extra**, pasa a amarillo y el detalle lo aclara («los aciertos están bien, pero hay señales extra»).',
             ],
             'notas' => [
                 '**Área «Números y cantidades»:** reconocer números, relacionarlos con cantidades, compararlos y ordenarlos.',
                 '**Área «Sentido de la cantidad»:** calcular a ojo cuántos hay, comparar montones de puntos y ubicar números en una línea.',
                 '**Área «Signos y cálculo»:** elegir el signo que falta (más o menos) y resolver cuentas con palillos.',
                 '**Qué no cuenta:** el calentamiento, los viajes incompletos, los viajes marcados como **no válidos** y las respuestas **demasiado rápidas** (menos de ' . $rapido . ' segundos: posible toque al azar). Si más del ' . $rapidoPct . ' % de las respuestas de un viaje fueron demasiado rápidas, **todo ese viaje** se considera poco confiable y no cuenta.',
                 'Si el niño tiene ciclos completos pero ninguno es confiable, el detalle te lo avisa: conviene que **repita los viajes con calma**.',
                 '**Señal extra «se confunde seguido con el cero»:** se mira cuando respondió al menos ' . $ceroMin . ' preguntas con cero y se equivocó en ' . $ceroPct . ' % o más de ellas.',
                 '**Señal extra «necesitó casi siempre las preguntas más fáciles»:** se mira cuando respondió al menos ' . $bajoMin . ' preguntas que se adaptan y ' . $bajoPct . ' % o más fueron del nivel más fácil.',
                 '**Confusión de signos:** el sistema registra cuando el niño puso el signo contrario (más en lugar de menos, o al revés). Aparece como «operación contraria» en las notas de cada pregunta y te ayuda a distinguir **una confusión de símbolos** de **una dificultad para calcular**.',
                 'Los cortes son **provisorios**. La administración puede ajustarlos para tu institución cuando haya datos del piloto.',
             ]],

            ['id' => 'numeros-campanita', 'icono' => 'bell-fill',
             'titulo' => 'La campanita de avisos',
             'notas' => [
                 'Te avisa cuando el color de un niño **empeora** a amarillo o rojo. Con el aviso podés ir directo al detalle.',
                 'Si el color sigue igual **no se repite**, y si mejora **no avisa**.',
                 'Al abrir el detalle del niño, el aviso queda como leído.',
                 'La campanita no reemplaza la revisión semanal: mirá la lista completa de **Atención numérica** de vez en cuando.',
             ]],

            // ── 4. Las pantallas ────────────────────────────────────────────
            ['id' => 'numeros-pantalla', 'icono' => 'table',
             'titulo' => 'La lista y la ficha de cada niño',
             'pasos' => [
                 'Entrá a **Atención numérica**. Arriba ves **cuántos niños hay de cada color** y cuántos están sin datos.',
                 'En la lista ves, por niño: el **grado**, el **semáforo**, el **porcentaje de cada área**, los **ciclos** completados y si tiene **ficha** de observación. Las señales extra aparecen debajo del nombre.',
                 'Tocá **Ver** para abrir el detalle. De arriba hacia abajo encontrás: el semáforo con las tres áreas, **tus observaciones**, el **informe con ayuda de la IA**, el **resumen para copiar** y los **viajes hechos**.',
                 'En **Viajes hechos** tocá un viaje para abrirlo y ver **pregunta por pregunta**: la parte, qué se preguntó, qué respondió el niño, cuál era la correcta, si estuvo bien, cuánto tardó y una nota con el tipo de error.',
             ],
             'notas' => [
                 '**Tipos de error que puede mostrar:** confundió el cero, número parecido (por ejemplo 6 y 9), uno de más o de menos, operación contraria, respuesta lejana, comparación y «muy rápido». Fijate si **el mismo tipo de error se repite**: un patrón dice más que un error suelto.',
                 'Cada viaje tiene etiquetas: **Completo** o **Incompleto**, el **porcentaje de aciertos**, **Muchos toques rápidos** si pasó el límite, y **No válido** si lo marcaste vos.',
                 'Los viajes del niño aparecen con su ciclo y su número (por ejemplo «Ciclo 2 · Viaje 3 · Los signos»).',
             ]],

            ['id' => 'numeros-no-valido', 'icono' => 'slash-circle',
             'titulo' => 'Marcar un viaje como no válido',
             'intro' => 'Si un viaje se hizo en mal momento, no tiene sentido que cuente para el semáforo.',
             'pasos' => [
                 'Entrá al detalle del niño y abrí el viaje en **Viajes hechos**.',
                 'Elegí el motivo: **estaba distraído o cansado**, **hubo una interrupción**, **alguien lo ayudó a responder**, **falla del equipo o de la pantalla**, u **otro motivo**.',
                 'Tocá **Marcar como no válido** y confirmá.',
             ],
             'notas' => [
                 'El viaje **deja de contar** para el semáforo y el niño **lo vuelve a hacer**.',
                 'Si te equivocaste, tocá **Volver a contarlo** en ese mismo viaje.',
                 'Solo se pueden marcar los viajes **completos**. Uno incompleto ya no cuenta por sí solo.',
             ]],

            // ── 5. Observaciones, resumen e informe ─────────────────────────
            ['id' => 'numeros-observaciones', 'icono' => 'journal-text',
             'titulo' => 'Tus observaciones y el resumen para copiar',
             'pasos' => [
                 'En el detalle del niño, en **Tus observaciones**, marcá lo que veas en el aula: cuenta con los dedos incluso con números chicos, se confunde al leer o escribir números, tarda mucho en comparar cantidades, se frustra o evita las actividades con números, ya recibe apoyo (psicopedagogía u otro).',
                 'Si querés, escribí una **nota corta** (hasta ' . $notaMax . ' caracteres).',
                 'Tocá **Guardar observaciones**. En la lista aparece una marca verde en la columna **Ficha**.',
                 'Para conversar con la dirección, la familia o un profesional, tocá **Copiar resumen** en **Resumen para copiar** y pegalo donde lo necesites. Revisalo antes de compartirlo.',
             ],
             'notas' => [
                 'Todo es **opcional**. Las observaciones son solo para vos: **no las ve la familia, no las ve nadie más y nunca se envían a la IA**.',
                 'Tus observaciones completan al semáforo: un color con observaciones que lo confirman o lo desmienten te da una idea mucho más clara.',
             ]],

            ['id' => 'numeros-informe', 'icono' => 'stars',
             'titulo' => 'Informe con ayuda de la IA',
             'intro' => 'La IA redacta un **borrador** con lo que se ve en los datos. Es opcional y **solo se genera cuando vos lo pedís**.',
             'pasos' => [
                 'Abrí el detalle del niño. El informe se puede pedir cuando ya tiene semáforo.',
                 'Tocá **Pedir informe** y esperá unos segundos.',
                 'Leé las tres partes: **Qué se ve**, **Ideas para el aula** y **Texto para conversar con la familia**.',
                 'Si querés usar el texto para la familia, **editalo en la misma pantalla** y tocá **Copiar texto**. Lo que cambies ahí no se guarda.',
                 'Para otro informe, tocá **Pedir un informe nuevo**. Hay que esperar ' . $espera . ' segundos entre un pedido y otro.',
             ],
             'notas' => [
                 'Es un **borrador**: leelo y adaptalo con tu criterio antes de usarlo o compartirlo. **No es un diagnóstico.**',
                 '**Privacidad.** A la IA se envían **solo datos anónimos**: porcentajes por área, señales, ciclos y errores frecuentes, con un código que no lleva nombre. **Nunca** el nombre, el código de acceso, la fecha de nacimiento ni tus observaciones. El texto de la familia lleva el nombre de pila recién cuando se muestra en tu pantalla.',
                 '**Control de lo que escribe.** El sistema revisa el texto: si la IA usa etiquetas médicas, frases alarmantes o que ponen un rótulo al niño, no lo muestra y te deja un **informe estándar** con los mismos datos.',
                 'Verás una etiqueta: **«Redactado con ayuda de la IA»** o **«Informe estándar (la IA no estuvo disponible)»**. En ambos casos el contenido es un borrador.',
                 'Se muestra el último informe pedido, con su fecha.',
                 'Es distinto del **Diagnóstico con IA** de Ver progreso, que analiza el desempeño en los otros juegos.',
             ]],

            // ── 6. Qué hacer ────────────────────────────────────────────────
            ['id' => 'numeros-que-hacer', 'icono' => 'compass',
             'titulo' => 'Qué hacer cuando un niño sale amarillo o rojo',
             'intro' => 'El semáforo no dice qué le pasa al niño: dice **dónde mirar**. Una forma ordenada de actuar:',
             'pasos' => [
                 '**Abrí el detalle** y fijate en qué área bajó y si hay señales extra.',
                 '**Revisá los viajes.** Si alguno se hizo con ruido, cansancio o ayuda, marcalo como **no válido** y pedí que lo repita.',
                 '**Observá en el aula** durante unos días: cómo cuenta, cómo compara, cómo reacciona ante las actividades con números. Anotalo en la ficha.',
                 '**Practicá con material concreto** (semillas, tapitas, fichas, palillos), en momentos cortos y con juegos.',
                 '**Conversá** con la dirección y, si lo ves oportuno, con la familia. El texto sugerido del informe te puede ayudar.',
                 '**Volvé a mirar** el semáforo a las pocas semanas para ver si cambió.',
             ],
             'notas' => [
                 '**Si baja «Números y cantidades»:** contar objetos reales y unir cada cantidad con su número escrito; ordenar tarjetas del 0 al 10 y decir cuál va antes y cuál después.',
                 '**Si baja «Sentido de la cantidad»:** mostrar dos montoncitos y preguntar cuál tiene más **antes de contar**, y después contar juntos para comprobar; dibujar una cinta numérica en el piso y pedir que ubique un número caminando hasta ahí.',
                 '**Si baja «Signos y cálculo»:** con fichas o palillos, agregar y sacar, y decir en voz alta si se hizo una suma o una resta; contar pequeños cuentos («llegaron dos más», «se fueron tres») y escribir la cuenta de lo que pasó.',
                 '**Si aparece mucha «operación contraria»:** el niño puede saber calcular pero confundir los símbolos. Conviene reforzar con situaciones reales qué significa juntar (+) y quitar (−) antes de pasar a las cuentas escritas.',
                 '**Conviene evitar:** poner etiquetas al niño, compararlo con otros delante de la clase, usar el semáforo para calificar o mostrar el color a la familia sin explicarlo.',
                 'Si pese al apoyo en el aula la dificultad persiste, hablalo con la dirección y con la familia. La decisión de consultar a un profesional es de la familia.',
             ]],

            // ── 7. Dudas ────────────────────────────────────────────────────
            ['id' => 'numeros-dudas', 'icono' => 'question-circle',
             'titulo' => 'Dudas de Atención numérica',
             'notas' => [
                 '**No aparece el semáforo de un niño.** Necesita **' . TamizajeService::MIN_CICLOS . ' ciclos completos (' . $viajesMin . ' viajes)** válidos. Abrí su detalle y mirá cuántos ciclos lleva. Un viaje incompleto, no válido o con muchos toques rápidos no cuenta.',
                 '**Un niño con buenas notas salió amarillo, o uno con dificultades salió verde.** Puede pasar: es un piloto, los cortes son provisorios y el semáforo solo mira estos juegos. Tu observación pesa más. Contale a la administración para ajustar los cortes con los datos del piloto.',
                 '**El niño hizo un viaje con ruido, con ayuda o muy cansado.** Marcalo como no válido (ver «Marcar un viaje como no válido»).',
                 '**El niño dejó un viaje a medias.** Queda como incompleto y no cuenta. Tiene que hacerlo completo.',
                 '**¿El color puede mejorar?** Sí. Se miran los últimos ' . $ventana . ' ciclos: a medida que el niño mejora, los ciclos viejos dejan de contar.',
                 '**¿Las familias ven el semáforo?** No. Las familias ven solo su panel de avance. Vos decidís si conversás con ellas y cómo.',
                 '**No me llega la campanita.** Solo avisa cuando el color empeora a amarillo o rojo. Si ya estaba así o mejora, no avisa.',
                 '**El informe dice «Informe estándar».** La IA no estuvo disponible o escribió algo que no corresponde, y el sistema usó un texto estándar con los mismos datos. Podés volver a pedir otro.',
                 '**No puedo pedir otro informe.** Hay que esperar ' . $espera . ' segundos entre un pedido y otro.',
                 '**¿La IA recibe el nombre del niño?** No. Solo recibe números anónimos.',
                 '**¿Quién cambia los cortes del semáforo?** La administración, en **Instituciones → Configuración**.',
                 '**Un niño no ve «Mati y los números».** Revisá **Configurar Juegos**: puede estar desactivado para tu grado.',
             ]],
        ];
    }

    /** Número con coma decimal («1,5»); sin decimales sobrantes («2»). */
    private static function coma(float|int $n, int $decimales = 1): string
    {
        $t = number_format((float) $n, $decimales, ',', '');
        return str_contains($t, ',') ? rtrim(rtrim($t, '0'), ',') : $t;
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
                 '**¿Qué datos de mi hijo o hija se guardan?** Mirá la nota en la pantalla «Acerca de MatiGuarã».',
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
                 '**Cortes del semáforo de «Mati y los números»:** en **Instituciones → Configuración** se define desde qué porcentaje de aciertos el color es verde y desde cuál es amarillo (por defecto 75 y 60). Son cortes provisorios: conviene revisarlos con los datos del piloto.',
             ]],
        ];
    }
}
