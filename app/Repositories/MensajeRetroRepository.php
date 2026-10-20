<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use App\Models\MensajeRetro;

/**
 * REPOSITORIO: MensajeRetroRepository
 * =========================================================================
 * Capa de acceso a datos del catálogo de mensajes de retroalimentación
 * bilingües (mensajes_retro).
 *
 * Capa: app/Repositories (acceso a datos).
 * =========================================================================
 */
class MensajeRetroRepository
{
    /**
     * Un mensaje activo al azar del tipo y contexto indicados, sin repetir
     * $excluirId (el último mostrado). Null si no hay ninguno.
     */
    public function aleatorio(string $tipo, string $contexto = 'general', ?int $excluirId = null): ?MensajeRetro
    {
        return MensajeRetro::obtenerAleatorio($tipo, $contexto, $excluirId);
    }

    /** Todos los mensajes, ordenados por tipo y contexto (para el panel de administración). */
    public function todos(): Collection
    {
        return MensajeRetro::orderBy('tipo')->orderBy('contexto')->get();
    }

    /** Alta de un mensaje. */
    public function crear(array $datos): MensajeRetro
    {
        return MensajeRetro::create($datos);
    }

    /** Actualiza un mensaje existente (404 si no existe). */
    public function actualizar(int $id, array $datos): void
    {
        MensajeRetro::findOrFail($id)->update($datos);
    }
}
