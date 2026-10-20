<?php

namespace Database\Seeders;

use App\Models\AsignacionDocente;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Padre;
use App\Models\Usuario;
use App\Models\Institucion;
use Illuminate\Database\Seeder;

/**
 * SEEDER: RelacionesSeeder
 * -------------------------------------------------------------------------
 * Crea las relaciones institucionales:
 * - Asignaciones de docentes a instituciones/grados
 * - Matrículas de estudiantes
 * - Vínculo padre-hijo
 *
 * Depende de: InstitucionesSeeder, UsuariosSeeder, EstudiantesSeeder
 * -------------------------------------------------------------------------
 */
class RelacionesSeeder extends Seeder
{
    public function run(): void
    {
        $inst1 = Institucion::where('codigo', 'EEB-ENC-001')->firstOrFail();
        $doc1  = Docente::first();
        $doc2  = Docente::skip(1)->first();

        // ── Asignación docente 1 → institución 1 / 1° grado ──────────
        AsignacionDocente::create([
            'docente_id'     => $doc1->id,
            'institucion_id' => $inst1->id,
            'grado'          => 1,
            'turno'          => 'mañana',
            'anio_lectivo'   => 2026,
            'activo'         => true,
            'fecha_inicio'   => '2026-03-01',
        ]);

        // ── Asignación docente 2 → institución 1 / 2° grado ──────────
        AsignacionDocente::create([
            'docente_id'     => $doc2->id,
            'institucion_id' => $inst1->id,
            'grado'          => 2,
            'turno'          => 'mañana',
            'anio_lectivo'   => 2026,
            'activo'         => true,
            'fecha_inicio'   => '2026-03-01',
        ]);

        // ── Matrículas de estudiantes ─────────────────────────────────
        $estudiantes1g = Estudiante::whereIn('codigo_acceso', [
            'SAN-1A-001', 'SAN-1A-002', 'SAN-1A-003'
        ])->get();

        foreach ($estudiantes1g as $est) {
            Matricula::create([
                'estudiante_id'  => $est->id,
                'institucion_id' => $inst1->id,
                'docente_id'     => $doc1->id,
                'grado'          => 1,
                'anio_lectivo'   => 2026,
                'activo'         => true,
                'fecha_inicio'   => '2026-03-01',
            ]);
        }

        $estudiantes2g = Estudiante::whereIn('codigo_acceso', [
            'SAN-2B-001', 'SAN-2B-002'
        ])->get();

        foreach ($estudiantes2g as $est) {
            Matricula::create([
                'estudiante_id'  => $est->id,
                'institucion_id' => $inst1->id,
                'docente_id'     => $doc2->id,
                'grado'          => 2,
                'anio_lectivo'   => 2026,
                'activo'         => true,
                'fecha_inicio'   => '2026-03-01',
            ]);
        }

        // ── Vínculo padre-hijo ────────────────────────────────────────
        $usuPadre = Usuario::where('email', 'padre@correo.com')->first();
        $estAna   = Estudiante::where('codigo_acceso', 'SAN-1A-001')->first();

        Padre::create([
            'usuario_id'     => $usuPadre->id,
            'estudiante_id'  => $estAna->id,
            'relacion'       => 'padre',
        ]);

        $this->command->info('  ✅ Asignaciones, matrículas y vínculo padre-hijo creados');
    }
}
