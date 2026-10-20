<?php

namespace App\Repositories;

use App\Models\ContenidoNivel;
use App\Models\Nivel;
use Illuminate\Support\Collection;

/**
 * REPOSITORIO: EjercicioRepository
 * =========================================================================
 * Capa de acceso a datos del contenido educativo: ejercicios
 * (contenido_nivel) y niveles (niveles).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class EjercicioRepository
{
    /** Busca un ejercicio por id; lanza ModelNotFoundException si no existe. */
    public function buscar(int $id): ContenidoNivel
    {
        return ContenidoNivel::findOrFail($id);
    }

    /** Cantidad total de ejercicios cargados en el nivel. */
    public function contarDelNivel(int $nivelId): int
    {
        return ContenidoNivel::where('nivel_id', $nivelId)->count();
    }

    /**
     * Próximo ejercicio del nivel que no esté en $excluirIds.
     *
     * Si se pasa $semilla, el orden es "al azar" pero ESTABLE para esa semilla
     * (MD5 del id con la semilla): al recargar la página se ve el mismo
     * ejercicio. Sin semilla, se respeta la columna 'orden' del nivel.
     *
     * @param  int         $nivelId
     * @param  array       $excluirIds  IDs de ejercicios ya respondidos
     * @param  string|null $semilla     p. ej. el id de la sesión
     * @return ContenidoNivel|null      null si no quedan ejercicios
     */
    public function siguienteNoRespondido(int $nivelId, array $excluirIds, ?string $semilla = null): ?ContenidoNivel
    {
        $disponibles = ContenidoNivel::where('nivel_id', $nivelId)
            ->whereNotIn('id', $excluirIds);

        if ($semilla !== null) {
            return $disponibles
                ->orderByRaw('MD5(CONCAT(id, ?))', [$semilla])
                ->first();
        }

        return $disponibles->orderBy('orden', 'asc')->first();
    }

    /** Nivel inmediatamente anterior (mismo juego y grado), o null si es el primero. */
    public function nivelAnterior(Nivel $nivel): ?Nivel
    {
        return Nivel::where('juego_id', $nivel->juego_id)
            ->where('grado', $nivel->grado)
            ->where('numero_nivel', $nivel->numero_nivel - 1)
            ->first();
    }

    /** Nivel contiguo (mismo juego y grado) desplazado $delta posiciones: +1 siguiente, -1 anterior. */
    public function nivelAdyacente(Nivel $nivel, int $delta): ?Nivel
    {
        return Nivel::where('juego_id', $nivel->juego_id)
            ->where('grado', $nivel->grado)
            ->where('numero_nivel', $nivel->numero_nivel + $delta)
            ->first();
    }

    /** Datos básicos de todos los niveles (para calcular qué niveles están abiertos). */
    public function todosLosNiveles(): Collection
    {
        return Nivel::get(['id', 'juego_id', 'grado', 'numero_nivel', 'desbloqueado_por_defecto']);
    }
}
