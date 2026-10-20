<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Repositories\DocenteRepository;
use App\Repositories\JuegoRepository;
use App\Support\DesbloqueoNiveles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * CONTROLADOR: DocenteNivelesController (2026-10-12)
 * =========================================================================
 * Permite al docente ABRIR niveles a un alumno suyo (por ejemplo, un alumno
 * avanzado que no necesita pasar por todos los niveles, o uno que quedó
 * trabado). Las rutas están dentro del grupo del docente (sesión iniciada y
 * rol de docente), así que no se pide contraseña de nuevo.
 *
 * Seguridad: solo se puede abrir niveles de alumnos con matrícula activa en
 * la clase de ESTE docente, y solo niveles del grado de ese alumno.
 *
 * No hay "cerrar": los niveles que el niño ya jugó siguen abiertos por regla
 * (ver App\Support\DesbloqueoNiveles).
 * =========================================================================
 */
class DocenteNivelesController extends Controller
{
    public function __construct(
        private DocenteRepository $docentes,
        private JuegoRepository $juegos
    ) {
    }

    /** POST /docente/estudiantes/{id}/niveles/{nivel}/abrir */
    public function abrirNivel(int $id, Nivel $nivel): RedirectResponse
    {
        $matricula = $this->matriculaDelAlumno($id);
        if (!$matricula) abort(403, 'Este alumno no pertenece a tu clase.');

        if ((int) $nivel->grado !== (int) $matricula->grado) {
            return $this->volver($id)->with('niveles_error', 'Ese nivel no corresponde al grado del alumno.');
        }

        DesbloqueoNiveles::abrir($id, $nivel);

        return $this->volver($id)->with(
            'niveles_msg',
            'Se abrió el nivel ' . $nivel->numero_nivel . ' de ' . ($nivel->juego->nombre_es ?? 'el juego') . '.'
        );
    }

    /** POST /docente/estudiantes/{id}/juegos/{juego}/abrir-todos */
    public function abrirTodos(int $id, Juego $juego): RedirectResponse
    {
        $matricula = $this->matriculaDelAlumno($id);
        if (!$matricula) abort(403, 'Este alumno no pertenece a tu clase.');

        $niveles = $this->juegos->nivelesDelGrado($juego, (int) $matricula->grado);
        foreach ($niveles as $nivel) {
            DesbloqueoNiveles::abrir($id, $nivel);
        }

        return $this->volver($id)->with(
            'niveles_msg',
            'Se abrieron los ' . $niveles->count() . ' niveles de ' . $juego->nombre_es . '.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────

    /** Matrícula activa del alumno en la clase del docente que inició sesión (o null). */
    private function matriculaDelAlumno(int $estudianteId): ?Matricula
    {
        $docente = $this->docentes->porUsuario(Auth::id());
        if (!$docente) return null;

        return $this->docentes->matriculaActivaDelAlumno($estudianteId, $docente->id);
    }

    private function volver(int $estudianteId): RedirectResponse
    {
        return redirect()->route('docente.estudiante.progreso', $estudianteId);
    }
}
