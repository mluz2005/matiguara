<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use App\Support\AcercaDe;
use App\Support\AyudaAdultos;
use App\Support\AyudaJuegos;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: AyudaController
 * -------------------------------------------------------------------------
 *  - ninos():  pantalla "¿Cómo se juega?" para los estudiantes (por juego o general)
 *  - adultos(): pantalla "Ayuda" de docentes, familias y administración (según el perfil)
 *  - acerca(): pantalla "Acerca de MatiGuará" (pública, la ve cualquier perfil)
 * No usa la base de datos más que para leer el nombre del juego.
 * -------------------------------------------------------------------------
 */
class AyudaController extends Controller
{
    /** GET /estudiante/ayuda/{juego?} */
    public function ninos(?Juego $juego = null): View
    {
        $titulo = $juego ? $juego->nombre_es : 'Niveles, medallas y sonido';
        $pasos  = $juego
            ? AyudaJuegos::pasosDe($juego->nombre_es)
            : AyudaJuegos::generales();

        return view('estudiante.ayuda', [
            'juego'  => $juego,
            'titulo' => $titulo,
            'pasos'  => AyudaJuegos::conAudios($pasos),
        ]);
    }

    /** GET /ayuda (con sesión de adulto) */
    public function adultos(): View
    {
        $usuario = Auth::user();
        $rol = $usuario->esDocente() ? 'docente' : ($usuario->esPadre() ? 'padre' : 'admin');

        return view('ayuda-adultos', [
            'rol'       => $rol,
            'titulo'    => AyudaAdultos::titulo($rol),
            'secciones' => AyudaAdultos::secciones($rol),
        ]);
    }

    /** GET /acerca-de */
    public function acerca(): View
    {
        return view('acerca-de', ['creditos' => AcercaDe::creditos()]);
    }
}
