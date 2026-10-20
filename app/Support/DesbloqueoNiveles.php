<?php

namespace App\Support;

use App\Models\Nivel;
use App\Repositories\EjercicioRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\JuegoRepository;
use App\Repositories\SesionRepository;

/**
 * DESBLOQUEO DE NIVELES (2026-10-11)
 * =========================================================================
 * Un nivel está ABIERTO para un estudiante cuando se cumple alguna de estas:
 *
 *   1. Es un nivel habilitado desde el comienzo (desbloqueado_por_defecto).
 *   2. Ya quedó desbloqueado en su progreso (progreso_estudiante.nivel_desbloqueado).
 *      Se marca cuando el niño SUPERA el nivel anterior o cuando el sistema
 *      adaptativo 3/2 lo sube de nivel.
 *   3. El niño ya jugó ese nivel alguna vez (así nadie pierde acceso a algo
 *      que ya usaba antes de que existiera esta regla).
 *   4. Superó el nivel anterior en alguna partida pasada (cubre a quienes
 *      jugaron antes de esta regla y no tienen el registro de progreso).
 *
 * Funciona igual para Suma de Frutas, Flash Matemático y Encuentra el par.
 * No usa columnas nuevas: lee tablas que ya existen.
 * =========================================================================
 */
class DesbloqueoNiveles
{
    /**
     * IDs de todos los niveles abiertos para el estudiante.
     *
     * @return int[]
     */
    public static function idsAbiertos(int $estudianteId): array
    {
        $niveles = app(EjercicioRepository::class)->todosLosNiveles();

        $porClave = [];
        foreach ($niveles as $n) {
            $porClave[$n->juego_id . '|' . $n->grado . '|' . $n->numero_nivel] = $n->id;
        }
        $porId = $niveles->keyBy('id');

        $abiertos = [];

        // 1) Habilitados desde el comienzo
        foreach ($niveles as $n) {
            if ($n->desbloqueado_por_defecto) $abiertos[$n->id] = true;
        }

        // 2) Desbloqueados en el progreso del estudiante
        $ids = app(EstudianteRepository::class)->idsNivelesDesbloqueados($estudianteId);
        foreach ($ids as $id) $abiertos[$id] = true;

        // 3) y 4) Según las partidas jugadas
        $sesiones = app(SesionRepository::class)->nivelesJugados($estudianteId);
        foreach ($sesiones as $s) {
            $abiertos[$s->nivel_id] = true;                       // 3) ya lo jugó
            if ($s->nivel_superado && isset($porId[$s->nivel_id])) { // 4) lo superó → abre el siguiente
                $n = $porId[$s->nivel_id];
                $clave = $n->juego_id . '|' . $n->grado . '|' . ($n->numero_nivel + 1);
                if (isset($porClave[$clave])) $abiertos[$porClave[$clave]] = true;
            }
        }

        return array_map('intval', array_keys($abiertos));
    }

    /** ¿Puede el estudiante jugar este nivel? */
    public static function estaAbierto(int $estudianteId, Nivel $nivel): bool
    {
        if ($nivel->desbloqueado_por_defecto) return true;
        return in_array($nivel->id, self::idsAbiertos($estudianteId), true);
    }

    /**
     * Marca un nivel como abierto para el estudiante (lo usa el docente).
     * Es lo mismo que hace el sistema cuando el niño supera el nivel anterior.
     */
    public static function abrir(int $estudianteId, Nivel $nivel): void
    {
        app(EstudianteRepository::class)->desbloquearNivel($estudianteId, $nivel->juego_id, $nivel);
    }

    /**
     * Estado de cada nivel del grado, por juego, para mostrarle al docente.
     * estado: 'superado' (✓) | 'abierto' (⭐) | 'cerrado' (🔒)
     *
     * @return array<int, array{juego: Juego, niveles: array<int, array{nivel: Nivel, estado: string}>}>
     */
    public static function resumenPorJuego(int $estudianteId, int $grado): array
    {
        $abiertos  = array_flip(self::idsAbiertos($estudianteId));
        $superados = array_flip(
            app(SesionRepository::class)->idsNivelesSuperados($estudianteId)
        );

        $juegos = app(JuegoRepository::class)->activosConNivelesDelGrado($grado);

        $resumen = [];
        foreach ($juegos as $juego) {
            if ($juego->niveles->isEmpty()) continue;
            $filas = [];
            foreach ($juego->niveles as $nivel) {
                $estado = isset($superados[$nivel->id]) ? 'superado'
                        : (isset($abiertos[$nivel->id]) ? 'abierto' : 'cerrado');
                $filas[] = ['nivel' => $nivel, 'estado' => $estado];
            }
            $resumen[] = ['juego' => $juego, 'niveles' => $filas];
        }
        return $resumen;
    }
}
