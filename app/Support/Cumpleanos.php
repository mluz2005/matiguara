<?php

namespace App\Support;

use App\Models\Estudiante;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Fecha de nacimiento de los niños y saludo de cumpleaños de Mati.
 *
 * La fecha de nacimiento es OPCIONAL: se carga con día, mes y año al crear o editar la cuenta.
 * Solo se usa para saludar al niño el día de su cumpleaños; NO viaja a ningún servicio externo
 * ni al informe de la IA (RN-01).
 */
final class Cumpleanos
{
    /** Años de nacimiento aceptados: niños de primer ciclo (con margen). */
    public const EDAD_MIN = 4;
    public const EDAD_MAX = 14;

    /**
     * Saludo y frase en castellano y en guaraní (COM-CUM-01/02). Guaraní REVISADO 2026-11-19:
     * los textos viven en App\Support\TextosComunes::CUMPLE (con el marcador {nombre}).
     */
    public const SALUDO_ES = TextosComunes::CUMPLE['saludo']['es'];
    public const SALUDO_GN = TextosComunes::CUMPLE['saludo']['gn'];
    public const FRASE_ES  = TextosComunes::CUMPLE['frase']['es'];
    public const FRASE_GN  = TextosComunes::CUMPLE['frase']['gn'];

    /**
     * Arma la fecha desde los tres campos del formulario.
     * Devuelve [fecha|null, error|null]: los tres vacíos = sin fecha (sin error).
     *
     * @return array{0:?Carbon, 1:?string}
     */
    public static function desdeFormulario(mixed $dia, mixed $mes, mixed $anio, ?CarbonInterface $hoy = null): array
    {
        $dia  = trim((string) $dia);
        $mes  = trim((string) $mes);
        $anio = trim((string) $anio);

        if ($dia === '' && $mes === '' && $anio === '') {
            return [null, null];
        }
        if ($dia === '' || $mes === '' || $anio === '') {
            return [null, 'Completá día, mes y año de nacimiento, o dejá los tres vacíos.'];
        }
        if (!ctype_digit($dia) || !ctype_digit($mes) || !ctype_digit($anio) || !checkdate((int) $mes, (int) $dia, (int) $anio)) {
            return [null, 'La fecha de nacimiento no es válida. Revisá el día, el mes y el año.'];
        }

        $hoy   = $hoy ? Carbon::instance($hoy)->startOfDay() : Carbon::today();
        $fecha = Carbon::create((int) $anio, (int) $mes, (int) $dia)->startOfDay();

        if ($fecha->greaterThan($hoy)) {
            return [null, 'La fecha de nacimiento no puede ser futura.'];
        }
        $edad = $fecha->diffInYears($hoy);
        if ($edad < self::EDAD_MIN || $edad > self::EDAD_MAX) {
            return [null, 'La fecha de nacimiento no parece ser la de un niño o niña de primer ciclo. Revisá el año.'];
        }

        return [$fecha, null];
    }

    /** ¿Hoy es su cumpleaños? (quien nació un 29 de febrero lo festeja el 28 si el año no es bisiesto) */
    public static function esHoy(?CarbonInterface $nacimiento, ?CarbonInterface $hoy = null): bool
    {
        if (!$nacimiento) {
            return false;
        }
        $hoy = $hoy ?? Carbon::today();

        $dia = (int) $nacimiento->format('j');
        $mes = (int) $nacimiento->format('n');
        if ($mes === 2 && $dia === 29 && !$hoy->isLeapYear()) {
            $dia = 28;
        }

        return (int) $hoy->format('n') === $mes && (int) $hoy->format('j') === $dia;
    }

    /**
     * Cómo se saluda hoy a este niño en esta pantalla:
     *   'tarjeta' → en el menú de juegos: una tarjeta que se ve cada vez que entra ese día;
     *   'aviso'   → dentro de un juego: un aviso breve, UNA sola vez por día, que no tapa ni bloquea nada;
     *   null      → hoy no es su cumpleaños (o ya se lo saludó dentro de un juego).
     */
    public static function modoDeSaludo(?Estudiante $estudiante, bool $enMenu): ?string
    {
        if (!$estudiante || !self::esHoy($estudiante->fecha_nacimiento)) {
            return null;
        }
        if ($enMenu) {
            return 'tarjeta';
        }

        $hoy = Carbon::today()->toDateString();
        if (session('cumple_saludado') === $hoy) {
            return null;
        }
        session(['cumple_saludado' => $hoy]);

        return 'aviso';
    }

    public static function saludoEs(string $nombre): string
    {
        return TextosComunes::rellenar(self::SALUDO_ES, ['nombre' => $nombre]);
    }

    public static function saludoGn(string $nombre): string
    {
        return TextosComunes::rellenar(self::SALUDO_GN, ['nombre' => $nombre]);
    }
}
