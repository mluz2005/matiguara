<?php

namespace App\Support\Numeros;

/**
 * TEXTOS del seguimiento de «Mati y los números» para la docente (semáforo, señales, ficha).
 *
 * Reglas de redacción: es una HERRAMIENTA DE APOYO, no un diagnóstico. Nunca se dice que el
 * niño «tiene» una dificultad: se dice que conviene «mirar con más atención». La docente
 * decide qué hacer y, si lo ve oportuno, conversa con la familia.
 */
final class TextosTamizaje
{
    public const AVISO_LEGAL = 'Esto no es un diagnóstico. Es una señal para que mires con más atención cómo trabaja con los números. '
        . 'Un resultado puede deberse al cansancio, a la distracción o a un mal día. Lo que vos observás en el aula pesa más que este color.';

    public const AREAS = [
        'numeros_cantidades' => 'Números y cantidades',
        'sentido_cantidad'   => 'Sentido de la cantidad',
        'signos_calculo'     => 'Signos y cálculo',
    ];

    public const AREAS_AYUDA = [
        'numeros_cantidades' => 'Reconocer números, relacionarlos con cantidades, compararlos y ordenarlos.',
        'sentido_cantidad'   => 'Calcular a ojo cuántos hay, comparar montones de puntos y ubicar números en una línea.',
        'signos_calculo'     => 'Elegir el signo que falta (más o menos) y resolver cuentas con palillos.',
    ];

    /** color → textos y colores (el color nunca va solo: lleva siempre icono y palabras). */
    public const SEMAFORO = [
        'verde' => [
            'etiqueta' => 'Sin señales por ahora',
            'icono'    => '🟢',
            'color'    => '#177040',
            'fondo'    => '#D5E8D4',
            'texto'    => 'Por ahora no se ven señales. Seguí observando como siempre.',
        ],
        'amarillo' => [
            'etiqueta' => 'Mirar con más atención',
            'icono'    => '🟡',
            'color'    => '#7A4F00',
            'fondo'    => '#FFF2CC',
            'texto'    => 'Hay algunas señales. Conviene observar con más atención cómo trabaja con los números.',
        ],
        'rojo' => [
            'etiqueta' => 'Mirar con más atención y conversar',
            'icono'    => '🔴',
            'color'    => '#9C2B1F',
            'fondo'    => '#FADBD8',
            'texto'    => 'Hay varias señales. Conviene observar con más atención y, si lo ves oportuno, conversar con la familia.',
        ],
    ];

    public const SIN_DATOS = [
        'etiqueta' => 'Todavía sin datos suficientes',
        'icono'    => '⚪',
        'color'    => '#555555',
        'fondo'    => '#EEEEEE',
        'texto'    => 'El semáforo aparece cuando el niño completó al menos 2 ciclos (6 viajes).',
    ];

    public const SENALES = [
        'cero'       => 'Se confunde seguido con el cero',
        'nivel_bajo' => 'Necesitó casi siempre las preguntas más fáciles',
    ];

    /** Casillas opcionales de la ficha de observación de la docente. */
    public const FICHA = [
        'cuenta_dedos'       => 'Cuenta con los dedos incluso con números chicos',
        'confunde_numeros'   => 'Se confunde al leer o escribir números',
        'tarda_comparar'     => 'Tarda mucho en comparar cantidades',
        'se_frustra'         => 'Se frustra o evita las actividades con números',
        'ya_recibe_apoyo'    => 'Ya recibe apoyo (psicopedagogía u otro)',
    ];

    public const FICHA_NOTA_MAX = 300;

    /** Motivos para marcar un viaje como no válido (RF-20). */
    public const MOTIVOS_NO_VALIDA = [
        'distraido'    => 'Estaba distraído o cansado',
        'interrupcion' => 'Hubo una interrupción',
        'ayuda'        => 'Alguien lo ayudó a responder',
        'equipo'       => 'Falla del equipo o de la pantalla',
        'otro'         => 'Otro motivo',
    ];

    public const TIPOS_ERROR = [
        'cero'                => 'confundió el cero',
        'parecido'            => 'número parecido',
        'mas_menos_uno'       => 'uno de más o de menos',
        'operacion_contraria' => 'operación contraria',
        'lejano'              => 'respuesta lejana',
        'comparacion'         => 'comparación',
        'azar'                => 'posible toque al azar',
    ];

    public static function semaforo(?string $color): array
    {
        return self::SEMAFORO[$color ?? ''] ?? self::SIN_DATOS;
    }
}
