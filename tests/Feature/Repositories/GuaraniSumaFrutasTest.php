<?php

namespace Tests\Feature\Repositories;

use App\Models\CatalogoLogro;
use App\Models\ContenidoNivel;
use App\Models\Juego;
use App\Models\MensajeRetro;
use App\Support\EnunciadosGuarani;
use App\Support\TextosGuaraniCompartidos;
use Database\Seeders\ContenidoGrados23Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Guaraní revisado de Suma de Frutas y de los textos compartidos:
 *   • lo que dejan los seeders (instalación nueva) y
 *   • lo que corrige la migración 2026_11_19_000002 en una base que ya tenía datos.
 */
class GuaraniSumaFrutasTest extends RepositorioTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_19_000002_aplicar_guarani_revisado_suma_de_frutas.php';

    private function frutas(): Juego
    {
        return Juego::where('nombre_es', 'Suma de Frutas')->firstOrFail();
    }

    private function aplicarMigracion(): void
    {
        (require base_path(self::MIGRACION))->up();
    }

    public function test_los_seeders_dejan_el_guarani_revisado()
    {
        $juego = $this->frutas();
        $this->assertSame("Yva Ñemongu'e", $juego->nombre_gn);
        $this->assertSame('Embojuaju umi yva rehecháva ha eiporavo mbohovái oĩporãva. ¡Ikatu nde!', $juego->instrucciones_gn);

        // Ejercicio base (nivel 2 de 1.° grado: 7 + 3)
        $e = ContenidoNivel::where('valor_a', 7)->where('valor_b', 3)->where('operador', '+')
            ->whereHas('nivel', fn ($q) => $q->where('juego_id', $juego->id)->where('grado', 1)->where('numero_nivel', 2))
            ->firstOrFail();
        $this->assertSame("¿Mboy'e pa pokõi ha mbohapy?", $e->enunciado_gn);

        foreach (TextosGuaraniCompartidos::MENSAJES as $es => $gn) {
            $this->assertSame($gn, MensajeRetro::where('texto_es', $es)->value('texto_gn'), $es);
        }
        foreach (TextosGuaraniCompartidos::MEDALLAS as $es => [$nombre, $descripcion]) {
            $logro = CatalogoLogro::where('nombre_es', $es)->first();
            if ($logro === null) {
                continue; // Flash Master la crea una migración; ver el test siguiente
            }
            $this->assertSame($nombre, $logro->nombre_gn, $es);
            $this->assertSame($descripcion, $logro->descripcion_gn, $es);
        }
    }

    public function test_ningun_enunciado_de_suma_de_frutas_conserva_la_palabra_mas_o_menos()
    {
        $this->seed(ContenidoGrados23Seeder::class);

        $viejos = ContenidoNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $this->frutas()->id))
            ->where(fn ($q) => $q->where('enunciado_gn', 'like', '% más %')->orWhere('enunciado_gn', 'like', '% menos %'))
            ->count();

        $this->assertSame(0, $viejos);
    }

    public function test_la_migracion_corrige_una_base_con_el_guarani_viejo_y_es_repetible()
    {
        $juego = $this->frutas();

        // Se simula la base de producción: guaraní viejo en todas las filas afectadas.
        DB::table('juegos')->where('id', $juego->id)->update([
            'nombre_gn' => "Tesãi Rendápe Ñemongu'e", 'instrucciones_gn' => 'viejo',
        ]);
        ContenidoNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $juego->id))
            ->update(['enunciado_gn' => "¿Mboy'e pa viejo más viejo?"]);
        DB::table('mensajes_retro')->update(['texto_gn' => 'viejo']);
        DB::table('catalogo_logros')->update(['nombre_gn' => 'viejo', 'descripcion_gn' => null]);

        $this->aplicarMigracion();
        $this->aplicarMigracion(); // repetirla no cambia nada más

        $this->assertSame("Yva Ñemongu'e", $juego->fresh()->nombre_gn);

        foreach (ContenidoNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $juego->id))->with('nivel')->get() as $f) {
            $esperado = EnunciadosGuarani::sumaFrutas(
                (int) $f->nivel->grado, (int) $f->nivel->numero_nivel,
                (int) $f->valor_a, (string) $f->operador, (int) $f->valor_b
            );
            $this->assertSame($esperado, $f->enunciado_gn, "{$f->valor_a} {$f->operador} {$f->valor_b}");
        }

        foreach (TextosGuaraniCompartidos::MENSAJES as $es => $gn) {
            $this->assertSame($gn, MensajeRetro::where('texto_es', $es)->value('texto_gn'), $es);
        }
        foreach (TextosGuaraniCompartidos::MEDALLAS as $es => [$nombre, $descripcion]) {
            $logro = CatalogoLogro::where('nombre_es', $es)->first();
            $this->assertNotNull($logro, "Falta la medalla {$es}");
            $this->assertSame($nombre, $logro->nombre_gn, $es);
            $this->assertSame($descripcion, $logro->descripcion_gn, $es);
        }
    }

    public function test_la_migracion_no_toca_a_los_otros_juegos()
    {
        $otro = Juego::where('nombre_es', '!=', 'Suma de Frutas')->firstOrFail();
        $antes = [$otro->nombre_gn, $otro->instrucciones_gn];

        $this->aplicarMigracion();

        $otro->refresh();
        $this->assertSame($antes, [$otro->nombre_gn, $otro->instrucciones_gn]);
    }

    // ── Flash Matemático ────────────────────────────────────────────────

    public function test_flash_los_seeders_dejan_el_guarani_revisado()
    {
        $flash = Juego::where('nombre_es', 'Flash Matemático')->firstOrFail();
        $this->assertSame("¡Emaña porã umi papapýre, okañýta hína! Eñemandu'a hesekuéra ha eiporavo osẽva.", $flash->instrucciones_gn);

        $e = ContenidoNivel::where('valor_a', 9)->where('valor_b', 5)->where('operador', '+')
            ->whereHas('nivel', fn ($q) => $q->where('juego_id', $flash->id)->where('grado', 1)->where('numero_nivel', 3))
            ->firstOrFail();
        $this->assertSame('¡Flash! porundy ha po.', $e->enunciado_gn);
    }

    public function test_flash_la_migracion_corrige_los_15_originales_y_no_toca_los_agregados()
    {
        $flash = Juego::where('nombre_es', 'Flash Matemático')->firstOrFail();
        $ids = ContenidoNivel::whereHas('nivel', fn ($q) => $q->where('juego_id', $flash->id))->pluck('id');

        DB::table('juegos')->where('id', $flash->id)->update(['instrucciones_gn' => 'viejo']);
        DB::table('contenido_nivel')->whereIn('id', $ids)->update(['enunciado_gn' => '¡Flash! viejo más viejo.']);

        $migracion = require base_path('database/migrations/2026_11_19_000003_aplicar_guarani_revisado_flash_matematico.php');
        $migracion->up();
        $migracion->up(); // repetirla no cambia nada más

        $this->assertSame(
            "¡Emaña porã umi papapýre, okañýta hína! Eñemandu'a hesekuéra ha eiporavo osẽva.",
            $flash->fresh()->instrucciones_gn
        );

        $corregidos = 0;
        foreach (ContenidoNivel::whereIn('id', $ids)->with('nivel')->get() as $f) {
            $esperado = $f->nivel->grado == 1 && $f->operador === '+' && $f->operador_2 === null
                ? EnunciadosGuarani::flashBase((int) $f->nivel->numero_nivel, (int) $f->valor_a, (int) $f->valor_b)
                : null;
            if ($esperado !== null) {
                $corregidos++;
                $this->assertSame($esperado, $f->enunciado_gn);
            } else {
                $this->assertSame('¡Flash! viejo más viejo.', $f->enunciado_gn, 'Un ejercicio agregado no debe tocarse.');
            }
        }
        $this->assertSame(15, $corregidos);
    }
}
