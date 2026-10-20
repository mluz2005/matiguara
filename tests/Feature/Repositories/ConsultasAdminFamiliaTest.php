<?php

namespace Tests\Feature\Repositories;

use App\Models\AsignacionDocente;
use App\Models\Diagnostico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Institucion;
use App\Models\MensajeRetro;
use App\Models\Padre;
use App\Models\Usuario;
use App\Repositories\DiagnosticoRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\MensajeRetroRepository;
use App\Repositories\PadreRepository;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRepository;

/**
 * Consultas agregadas en la Etapa 3b (administración y acceso de familias).
 */
class ConsultasAdminFamiliaTest extends RepositorioTestCase
{
    // ── DocenteRepository (administración) ──────────────────────────────

    public function test_docentes_contar_listar_buscar_y_crear()
    {
        $repo = app(DocenteRepository::class);
        $total = Docente::where('activo', true)->count();

        $this->assertSame($total, $repo->contarActivos());
        $lista = $repo->activosConAsignacion();
        $this->assertCount($total, $lista);
        $this->assertTrue($lista->first()->relationLoaded('usuario'));
        $this->assertTrue($lista->first()->relationLoaded('asignacionActiva'));

        $usuario = app(UsuarioRepository::class)->crear([
            'nombre' => 'Nueva', 'apellido' => 'Docente', 'email' => 'nueva@unae.edu.py',
            'password_hash' => bcrypt('x'), 'perfil' => 'docente', 'activo' => true,
        ]);
        $doc = $repo->crearPerfil($usuario->id);
        $this->assertTrue((bool) $doc->activo);
        $this->assertSame($doc->id, $repo->buscar($doc->id)->id);

        $asig = $repo->crearAsignacion([
            'docente_id' => $doc->id, 'institucion_id' => Institucion::first()->id, 'grado' => 2,
            'turno' => 'tarde', 'anio_lectivo' => date('Y'), 'activo' => true, 'fecha_inicio' => now()->toDateString(),
        ]);
        $this->assertInstanceOf(AsignacionDocente::class, $asig);
        $this->assertSame($total + 1, $repo->contarActivos());
    }

    public function test_desactivar_docente_cierra_cuenta_y_asignaciones_sin_borrar_historial()
    {
        $repo = app(DocenteRepository::class);
        $doc  = Docente::orderBy('id')->first();

        $repo->desactivar($doc);

        $this->assertFalse((bool) $doc->fresh()->activo);
        $this->assertFalse((bool) $doc->usuario->fresh()->activo);
        $asignaciones = AsignacionDocente::where('docente_id', $doc->id)->get();
        $this->assertGreaterThanOrEqual(1, $asignaciones->count(), 'El historial se conserva.');
        $this->assertTrue($asignaciones->every(fn ($a) => !$a->activo && $a->motivo_baja === 'otro' && $a->fecha_fin !== null));
        $this->assertFalse($repo->activosConAsignacion()->contains('id', $doc->id));
    }

    // ── UsuarioRepository ───────────────────────────────────────────────

    public function test_usuario_por_correo_distingue_mayusculas_y_devuelve_null_si_no_existe()
    {
        $repo = app(UsuarioRepository::class);

        $this->assertSame('docente1@unae.edu.py', $repo->porCorreo('docente1@unae.edu.py')->email);
        $this->assertNull($repo->porCorreo('nadie@unae.edu.py'));

        $u = $repo->crear([
            'nombre' => 'Fam', 'apellido' => 'X', 'email' => 'mixto@familia.com',
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => true,
        ]);
        $this->assertSame($u->id, $repo->porCorreo('mixto@familia.com')->id);
    }

    // ── PadreRepository ─────────────────────────────────────────────────

    public function test_padre_vinculos_crear_y_vincular_sin_duplicar()
    {
        $repo = app(PadreRepository::class);
        $usuario = Usuario::create([
            'nombre' => 'Fam', 'apellido' => 'Y', 'email' => 'y@familia.com',
            'password_hash' => bcrypt('x'), 'perfil' => 'padre', 'activo' => true,
        ]);
        [$a, $b] = Estudiante::orderBy('id')->take(2)->get()->all();

        $this->assertFalse($repo->tieneVinculo($usuario->id));
        $this->assertCount(0, $repo->vinculosDelUsuario($usuario->id));

        $repo->crearVinculo(['usuario_id' => $usuario->id, 'estudiante_id' => $a->id, 'relacion' => 'madre']);
        $v1 = $repo->vincularSiNoExiste($usuario->id, $a->id, 'tutor');   // ya existe: no cambia ni duplica
        $v2 = $repo->vincularSiNoExiste($usuario->id, $b->id, 'tutor');   // nuevo

        $this->assertSame('madre', $v1->relacion);
        $this->assertSame('tutor', $v2->relacion);
        $this->assertTrue($repo->tieneVinculo($usuario->id));

        $vinculos = $repo->vinculosDelUsuario($usuario->id);
        $this->assertCount(2, $vinculos);
        $this->assertTrue($vinculos->first()->relationLoaded('estudiante'));
    }

    // ── EstudianteRepository (global y acceso de familias) ──────────────

    public function test_estudiante_contar_activos_y_busquedas_por_codigo_y_correo()
    {
        $repo = app(EstudianteRepository::class);
        $ana  = Estudiante::where('codigo_acceso', 'SAN-1A-001')->firstOrFail();
        $juan = Estudiante::where('codigo_acceso', 'SAN-1A-002')->firstOrFail();

        $total = Estudiante::where('activo', true)->count();
        $this->assertSame($total, $repo->contarActivos());

        $this->assertSame($ana->id, $repo->buscarActivoPorCodigo('SAN-1A-001')->id);
        $this->assertNull($repo->buscarActivoPorCodigo('NO-EXISTE'));
        $ana->update(['activo' => false]);
        $this->assertNull($repo->buscarActivoPorCodigo('SAN-1A-001'), 'Un estudiante inactivo no se encuentra.');
        $this->assertSame($total - 1, $repo->contarActivos());

        $juan->update(['email_tutor' => 'familia@correo.com']);
        $this->assertSame($juan->id, $repo->buscarPorCodigoYCorreoTutor('SAN-1A-002', 'familia@correo.com')->id);
        $this->assertNull($repo->buscarPorCodigoYCorreoTutor('SAN-1A-002', 'otra@correo.com'));
        $this->assertNull($repo->buscarPorCodigoYCorreoTutor('SAN-1A-003', 'familia@correo.com'));
    }

    public function test_estudiante_activos_por_correo_tutor_ignora_inactivos_y_mayusculas()
    {
        $repo = app(EstudianteRepository::class);
        $juan  = Estudiante::where('codigo_acceso', 'SAN-1A-002')->firstOrFail();
        $sofia = Estudiante::where('codigo_acceso', 'SAN-1A-003')->firstOrFail();
        $juan->update(['email_tutor' => 'Familia@Correo.com']);
        $sofia->update(['email_tutor' => 'familia@correo.com', 'activo' => false]);

        $hijos = $repo->activosPorCorreoTutor('familia@correo.com');

        $this->assertSame([$juan->id], $hijos->pluck('id')->all());
    }

    // ── Otros repositorios ──────────────────────────────────────────────

    public function test_contadores_globales_de_sesiones_y_diagnosticos()
    {
        $e = $this->escenario();
        $this->nuevaSesion($e, ['estado' => 'completada']);
        $this->nuevaSesion($e, ['estado' => 'en_curso']);

        $this->assertSame(1, app(SesionRepository::class)->contarCompletadasGlobal());

        $base = ['estudiante_id' => $e['estudiante']->id, 'docente_id' => Docente::first()->id,
                 'id_anonimizado' => $e['estudiante']->id_anonimizado, 'fecha_generacion' => now()];
        Diagnostico::create($base + ['estado' => 'exitoso']);
        Diagnostico::create($base + ['estado' => 'fallido']);

        $this->assertSame(1, app(DiagnosticoRepository::class)->contarExitosos());
    }

    public function test_mensajes_todos_crear_y_actualizar()
    {
        $repo = app(MensajeRetroRepository::class);

        $todos = $repo->todos();
        $this->assertSame(MensajeRetro::count(), $todos->count());
        // Orden de la base (tipo y contexto son enum: se ordenan por su posición en el enum)
        $esperado = MensajeRetro::orderBy('tipo')->orderBy('contexto')->pluck('id')->all();
        $this->assertEquals($esperado, $todos->pluck('id')->all(), 'Ordenados por tipo y contexto.');

        $nuevo = $repo->crear(['tipo' => 'acierto', 'contexto' => 'general', 'texto_es' => 'Muy bien', 'texto_gn' => 'Iporãiterei', 'activo' => true]);
        $repo->actualizar($nuevo->id, ['texto_es' => 'Excelente', 'activo' => false]);
        $this->assertSame('Excelente', $nuevo->fresh()->texto_es);
        $this->assertFalse((bool) $nuevo->fresh()->activo);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $repo->actualizar(999999, ['texto_es' => 'x']);
    }

    public function test_juegos_todos_con_mecanica_incluye_inactivos()
    {
        $e = $this->escenario();
        \App\Models\Juego::where('id', $e['juego']->id)->update(['activo' => false]);

        $todos = app(JuegoRepository::class)->todosConMecanica();

        $this->assertTrue($todos->contains('id', $e['juego']->id));
        $this->assertTrue($todos->first()->relationLoaded('tipoMecanica'));
        $this->assertSame(\App\Models\Nivel::where('juego_id', $e['juego']->id)->count(), $todos->firstWhere('id', $e['juego']->id)->niveles_count);
    }
}
