<?php

namespace Database\Seeders;

use App\Models\Juego;
use App\Models\Nivel;
use App\Models\TarjetaNivel;
use App\Models\TipoMecanica;
use Illuminate\Database\Seeder;

/**
 * SEEDER: MemoriaJuegoSeeder — "Encuentra el par / Jaheka pe par ojoajúva"
 * =========================================================================
 * Crea el tercer juego de MatiGuará: memoria visual de pares.
 *
 * SIMPLIFICACIÓN DE DISEÑO (documentada, revisable):
 * Los 10 niveles se crean todos con grado=1, como una progresión ÚNICA
 * compartida (a diferencia de Suma de Frutas/Flash Matemático, que
 * particionan niveles por grado). Esto es intencional para este primer
 * lanzamiento del juego: 2° y 3° grado acceden a la MISMA progresión de
 * 10 niveles. Si se necesita que cada grado tenga su propio punto de
 * entrada diferenciado (ej: 3° grado arranca directo en nivel 5), se
 * puede ajustar más adelante duplicando niveles con grado=2/3.
 *
 * CORRECCIÓN DE NUMERACIÓN GUARANÍ:
 * El guión de audio generado anteriormente (guion_audio_guarani.csv)
 * tenía un error: "6" y "8" estaban ambos mapeados a "poapy". La
 * numeración correcta (base 5, sistema aditivo) es:
 *   6 = poteĩ (po+peteĩ) | 7 = pokõi (po+mokõi) |
 *   8 = poapy (po+mbohapy) | 9 = porundy (po+irundy)
 * Este seeder usa la numeración corregida. Recomendado corregir también
 * el guión de audio antes de la sesión de grabación.
 *
 * TODAS las traducciones guaraní son aproximaciones — DEBEN ser
 * validadas por la hablante nativa antes de producción (mismo criterio
 * que el resto del proyecto).
 *
 * Referencia: Documento de requerimientos "Encuentra el par"
 * =========================================================================
 */
class MemoriaJuegoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Tipo de mecánica ────────────────────────────────────────────
        $mecMemoria = TipoMecanica::firstOrCreate(
            ['tipo' => 'memoria', 'nombre' => 'Memoria de pares']
        );

        // ── 2. Juego ─────────────────────────────────────────────────────
        $juego = Juego::create([
            'tipo_mecanica_id' => $mecMemoria->id,
            'nombre_es'        => 'Encuentra el par',
            'nombre_gn'        => 'Jaheka pe par ojoajúva',
            'instrucciones_es' => 'Volteá dos cartas y encontrá los pares idénticos antes de que se acabe el tiempo.',
            'instrucciones_gn' => 'Embojere mokõi kuatia ha ejuhu umi ojoguáva tiémpo opama mboyve.',
            'activo'           => true,
            'dias_reinicio_racha' => null,
        ]);

        // ── 3. Pool de objetos escolares (reutilizable entre niveles) ──────
        $objetos = [
            ['valor' => 'mochila',     'es' => 'Mochila',     'gn' => 'Mochila',           'emoji' => '🎒'],
            ['valor' => 'lapiz',       'es' => 'Lápiz',       'gn' => 'Kuatiajokuaaha',     'emoji' => '✏️'],
            ['valor' => 'regla',       'es' => 'Regla',       'gn' => 'Regla',              'emoji' => '📏'],
            ['valor' => 'tijera',      'es' => 'Tijera',      'gn' => 'Tijera',             'emoji' => '✂️'],
            ['valor' => 'crayon',      'es' => 'Crayón',      'gn' => 'Crayón',             'emoji' => '🖍️'],
            ['valor' => 'cuaderno',    'es' => 'Cuaderno',    'gn' => 'Kuatiañe\'ẽ',        'emoji' => '📓'],
            ['valor' => 'libro',       'es' => 'Libro',       'gn' => 'Aranduka',           'emoji' => '📚'],
            ['valor' => 'lapicera',    'es' => 'Lapicera',    'gn' => 'Lapicera',           'emoji' => '🖊️'],
            ['valor' => 'carpeta',     'es' => 'Carpeta',     'gn' => 'Carpeta',            'emoji' => '📁'],
            ['valor' => 'calculadora','es' => 'Calculadora', 'gn' => 'Calculadora',        'emoji' => '🧮'],
        ];

        // ── 4. Pool de números 0-9 (numeral ↔ cantidad) ────────────────────
        // Guaraní corregido — ver nota de cabecera
        $numeros = [
            ['valor' => '0', 'gn' => 'Zero'],     // Sin término tradicional extendido — validar
            ['valor' => '1', 'gn' => 'Peteĩ'],
            ['valor' => '2', 'gn' => 'Mokõi'],
            ['valor' => '3', 'gn' => 'Mbohapy'],
            ['valor' => '4', 'gn' => 'Irundy'],
            ['valor' => '5', 'gn' => 'Po'],
            ['valor' => '6', 'gn' => 'Poteĩ'],
            ['valor' => '7', 'gn' => 'Pokõi'],
            ['valor' => '8', 'gn' => 'Poapy'],
            ['valor' => '9', 'gn' => 'Porundy'],
        ];

        // ── 5. Definición de los 10 niveles ────────────────────────────────
        // [numero_nivel, pares, tiempo_limite_seg, dificultad, contenido, desbloqueado_default]
        $definicionNiveles = [
            [1,  3, 90,  1, 'objeto', true],
            [2,  4, 100, 2, 'objeto', false],
            [3,  5, 110, 2, 'objeto', false],
            [4,  5, 100, 3, 'numero', false],
            [5,  6, 120, 3, 'objeto', false],
            [6,  6, 110, 4, 'numero', false],
            [7,  7, 130, 4, 'mixto',  false],
            [8,  8, 140, 5, 'mixto',  false],
            [9,  8, 130, 5, 'numero', false],
            [10, 9, 150, 5, 'mixto',  false],
        ];

        foreach ($definicionNiveles as [$numNivel, $pares, $tiempoSeg, $dificultad, $contenido, $desbloqueado]) {

            $nivel = Nivel::create([
                'juego_id'                 => $juego->id,
                'grado'                    => 1, // Progresión única — ver nota de cabecera
                'numero_nivel'             => $numNivel,
                'dificultad'               => $dificultad,
                'min_aciertos_pasar'       => $pares, // debe encontrar TODAS las parejas
                'total_ejercicios'         => $pares, // reutilizado semánticamente = cantidad de pares
                'desbloqueado_por_defecto' => $desbloqueado,
                'tiempo_mostrar_ms'        => null,   // no aplica a este juego
                'tiempo_limite_seg'        => $tiempoSeg,
            ]);

            $this->crearPoolTarjetas($nivel, $contenido, $pares, $objetos, $numeros);
        }

        $this->command->info("  ✅ Juego 3: {$juego->nombre_es} — 10 niveles, pools de tarjetas generados");
    }

    /**
     * Crea el pool de tarjetas disponibles para un nivel.
     * El pool es ligeramente más grande que la cantidad de pares necesarios,
     * para que cada repetición del nivel muestre una selección distinta.
     */
    private function crearPoolTarjetas(
        Nivel $nivel,
        string $contenido,
        int $pares,
        array $objetos,
        array $numeros
    ): void {
        if ($contenido === 'objeto') {
            $this->insertarObjetos($nivel, $objetos);
        } elseif ($contenido === 'numero') {
            $this->insertarNumeros($nivel, $numeros, $pares);
        } else { // mixto: la mitad de objetos, la mitad de números
            $mitad = (int) ceil($pares / 2);
            $this->insertarObjetos($nivel, array_slice($objetos, 0, min(count($objetos), $pares + 1)));
            $this->insertarNumeros($nivel, $numeros, $mitad + 1);
        }
    }

    private function insertarObjetos(Nivel $nivel, array $objetos): void
    {
        foreach ($objetos as $obj) {
            TarjetaNivel::create([
                'nivel_id'       => $nivel->id,
                'tipo_contenido' => 'objeto',
                'valor'          => $obj['valor'],
                'nombre_es'      => $obj['es'],
                'nombre_gn'      => $obj['gn'],
                'emoji'          => $obj['emoji'],
                'activo'         => true,
            ]);
        }
    }

    private function insertarNumeros(Nivel $nivel, array $numeros, int $cantidad): void
    {
        $seleccion = array_slice($numeros, 0, min(count($numeros), $cantidad + 2));

        foreach ($seleccion as $num) {
            TarjetaNivel::create([
                'nivel_id'       => $nivel->id,
                'tipo_contenido' => 'numero',
                'valor'          => $num['valor'],
                'nombre_es'      => "Número {$num['valor']}",
                'nombre_gn'      => $num['gn'],
                'emoji'          => null, // se renderiza como numeral/cantidad, no emoji
                'activo'         => true,
            ]);
        }
    }
}
