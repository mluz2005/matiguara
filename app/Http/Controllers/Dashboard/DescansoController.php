<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\TiempoJuegoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CONTROLADOR: DescansoController — pantalla de descanso (RN-L10, RN-L11)
 * -------------------------------------------------------------------------
 * GET /estudiante/descanso
 *   - Si el alumno está en pausa: muestra cuánto falta para volver a jugar.
 *   - Si ya cumplió la pausa (o nunca llegó al límite): lo lleva al menú de juegos.
 */
class DescansoController extends Controller
{
    public function __construct(private TiempoJuegoService $tiempo)
    {
    }

    public function mostrar(): View|RedirectResponse
    {
        $estudiante = Auth::guard('estudiante')->user();
        $estado     = $this->tiempo->estado($estudiante);

        if (!$estado['bloqueado']) {
            return redirect()->route('estudiante.juegos');
        }

        // Por si llegó acá a mano con una partida abierta.
        $this->tiempo->mandarADescansar($estudiante);

        return view('estudiante.descanso', [
            'estudiante'    => $estudiante,
            'vuelveEnSeg'   => $estado['vuelve_en_seg'],
            'limiteMin'     => intdiv($estado['limite_seg'], 60),
            'pausaMin'      => intdiv($estado['pausa_seg'], 60),
        ]);
    }
}
