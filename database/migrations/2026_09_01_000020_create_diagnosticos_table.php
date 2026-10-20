<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: diagnosticos
 * -------------------------------------------------------------------------
 * Grupo 5 — Configuración y Diagnósticos | Tabla 20 de 21 ★ NUEVA v1.2
 *
 * Almacena diagnósticos pedagógicos generados por la API Claude.
 *
 * DISEÑO INMUTABLE — Los registros NO se editan ni eliminan (salvo por
 * el ciclo de vida del estudiante). Cada diagnóstico es una fotografía
 * permanente del estado pedagógico del estudiante en un momento dado.
 * Esto permite construir análisis longitudinales comparando snapshots.
 *
 * PRIVACIDAD TOTAL (RN-01 / UNICEF 2021):
 * El campo id_anonimizado es el UUID v4 del estudiante al momento del
 * diagnóstico. La API Claude NUNCA recibe nombre, código de acceso ni
 * ningún dato personal. Solo recibe id_anonimizado + métricas agregadas.
 * El campo se guarda aquí para que el diagnóstico sea autocontenido
 * (aunque el estudiante sea eliminado luego, el diagnóstico mantiene
 * el UUID que identifica la sesión con la API, sin reidentificar).
 *
 * SNAPSHOT NUMÉRICO (RF-15):
 * Los campos puntaje_promedio, porcentaje_aciertos y nivel_max_alcanzado
 * son ESTÁTICOS: se calculan en el momento de la generación y no cambian.
 * Aunque el estudiante mejore después, este snapshot refleja el estado
 * exacto cuando se generó el diagnóstico. Permite gráficos de evolución.
 *
 * SOLO BAJO DEMANDA EXPLÍCITA (RN-05):
 * El diagnóstico NO se genera automáticamente. Solo cuando el docente
 * lo solicita explícitamente en el panel (CU-11).
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 20 de 21
 * CU relacionados: CU-11 (Solicitar diagnóstico IA), CU-12 (Visualizar)
 * RF relacionados: RF-10, RF-11, RF-12, RF-15
 * RN relacionadas: RN-01, RN-02, RN-05
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {

            $table->id();

            // ── Relaciones ────────────────────────────────────────────
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->restrictOnDelete();
                // RESTRICT: el diagnóstico debe poder asociarse al estudiante.
                // Si el estudiante es eliminado (egresado), el diagnóstico
                // puede desvincularse mediante SET NULL si se desea mantener
                // el historial anónimo. Decisión: RESTRICT por ahora.

            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->restrictOnDelete();
                // El docente que solicitó el diagnóstico. Auditoría.

            // ── Anonimización preservada (RN-01) ──────────────────────
            $table->string('id_anonimizado', 36);
                // UUID v4 del estudiante AL MOMENTO del diagnóstico.
                // Se guarda aquí para que el diagnóstico sea autocontenido.
                // NO hay FK a estudiantes.id_anonimizado (es una copia intencional).

            // ── Temporalidad ──────────────────────────────────────────
            $table->timestamp('fecha_generacion')->useCurrent();
                // Momento exacto de creación del diagnóstico.
                // Base para comparaciones cronológicas entre diagnósticos.

            // ── SNAPSHOT NUMÉRICO (RF-15) — valores al momento exacto ─
            $table->decimal('puntaje_promedio', 5, 2)->nullable();
                // Promedio de puntajes de todas las sesiones del estudiante.
                // Ej: 73.45 → el estudiante promedió 73.45 puntos por sesión.

            $table->decimal('porcentaje_aciertos', 5, 2)->nullable();
                // Porcentaje de respuestas correctas sobre el total de intentos.
                // Ej: 68.20 → acertó el 68.20% de sus respuestas.

            $table->unsignedTinyInteger('nivel_max_alcanzado')->nullable();
                // Máximo nivel desbloqueado al momento del diagnóstico.

            // ── Texto generado por IA ─────────────────────────────────
            $table->text('fortalezas')->nullable();
                // Párrafo de la IA describiendo áreas de fortaleza del estudiante.
                // Ej: "El estudiante demuestra solidez en operaciones de suma simple..."

            $table->text('dificultades')->nullable();
                // Párrafo describiendo áreas de dificultad identificadas.
                // Ej: "Se observan dificultades en la resta con llevada..."

            $table->text('recomendacion')->nullable();
                // Recomendación pedagógica personalizada de la IA al docente.
                // IMPORTANTE: RN-02 — No son diagnósticos clínicos ni psicológicos.

            // ── Auditoría de la llamada a la API ─────────────────────
            $table->unsignedInteger('tokens_usados')->nullable();
                // Tokens consumidos en la llamada a la API Claude.
                // Permite estimar el costo mensual del servicio de IA.

            $table->enum('estado', ['exitoso', 'fallido'])->default('exitoso');
                // 'exitoso' → diagnóstico generado y validado correctamente.
                // 'fallido' → la API falló o devolvió JSON inválido (RF-12).
                // Los registros fallidos se guardan igual para auditoría.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};
