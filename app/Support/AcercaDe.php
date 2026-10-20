<?php

namespace App\Support;

/**
 * DATOS DE LA PÁGINA "ACERCA DE"
 * =========================================================================
 * Todo lo que muestra la pantalla "Acerca de MatiGuará" está acá, en un solo
 * lugar. Para cambiar la versión, el correo de contacto o los créditos, se
 * edita este archivo y no hace falta tocar nada más.
 * =========================================================================
 */
class AcercaDe
{
    /** Versión que se muestra. 0.9 = versión previa a las pruebas piloto. */
    public const VERSION = '0.9';
    public const ESTADO_VERSION = 'Versión previa a las pruebas piloto';
    public const FECHA = 'Octubre de 2026';

    public const AUTORA      = 'María Luz Pesallaccia Cardozo';
    public const TUTORA      = 'Lic. Laura Vega';
    public const INSTITUCION = 'FACAT — Universidad Autónoma de Encarnación (UNAE)';
    public const TRABAJO     = 'Proyecto de Grado 2026 — Licenciatura en Análisis de Sistemas Informáticos';

    /**
     * Correo de contacto o soporte. Si se deja en null, las pantallas muestran
     * "Consultá con tu docente" en lugar de un correo.
     */
    public const CONTACTO = 'maria.pesallaccia15@unae.edu.py';

    /** Créditos de música, sonidos y tecnologías. */
    public static function creditos(): array
    {
        return [
            ['titulo' => 'Música y algunos sonidos',
             'detalle' => 'Archivos MP3 descargados de Pixabay (pixabay.com), con licencia de uso gratuito y sin derechos de autor.'],
            ['titulo' => 'Otros sonidos',
             'detalle' => 'Generados con Tone.js (biblioteca de código abierto, licencia MIT).'],
            ['titulo' => 'Tecnologías',
             'detalle' => 'Laravel, Bootstrap y Bootstrap Icons (todas de código abierto).'],
            ['titulo' => 'Dibujos',
             'detalle' => 'Emojis del propio dispositivo y formas simples.'],
        ];
    }
}
