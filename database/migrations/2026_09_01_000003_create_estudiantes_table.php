<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: estudiantes
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 3 de 21
 *
 * Almacena las cuentas de los estudiantes de 6-9 años (1°, 2° y 3° grado
 * del primer ciclo de la EEB paraguaya).
 *
 * DISEÑO DE AUTENTICACIÓN INFANTIL:
 * Los niños de esta edad no tienen email. El acceso se realiza mediante:
 *   1. codigo_acceso: código imprimible que el docente entrega (ej: SAN-1A-007)
 *   2. pin_hash: PIN de 4 dígitos hasheado con bcrypt
 * Esto elimina la necesidad de recordar contraseñas complejas (RF-01).
 *
 * PRIVACIDAD (RN-01 / UNICEF 2021):
 * El campo id_anonimizado (UUID v4) es el ÚNICO identificador que sale
 * del sistema hacia servicios externos (API Claude). Nunca se envía
 * nombre, código de acceso ni ningún dato personal a la API.
 *
 * CICLO DE VIDA (RF-18 / RN-10):
 * Al cerrar la matrícula de 3° grado, MatriculaService calcula y escribe
 * fecha_baja_programada = fecha_fin + meses_conservacion_egresados.
 * El job EgresadoCleanupJob desactiva la cuenta al llegar esa fecha.
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 6 de 21
 * RF relacionados: RF-01, RF-07, RF-14, RF-18
 * RN relacionadas: RN-01, RN-10
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {

            // ── Clave primaria ────────────────────────────────────────
            $table->id();

            // ── Datos personales básicos ──────────────────────────────
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->date('fecha_nacimiento')->nullable();
                // Opcional. Útil para calcular edad y validar rango 6-9 años.

            // ── Credenciales de acceso infantil ───────────────────────
            $table->string('codigo_acceso', 20)->unique();
                // Código imprimible único para login sin email.
                // Formato: [Escuela]-[Grado][Turno]-[N°] → SAN-1A-007
                // El docente imprime y entrega al niño/a (RF-01).

            $table->string('pin_hash', 255);
                // Hash bcrypt del PIN numérico de 4 dígitos.
                // PIN elegido por el docente junto al estudiante.
                // Validación: Hash::check($pinIngresado, $estudiante->pin_hash)

            // ── Privacidad / Anonimización ────────────────────────────
            $table->string('id_anonimizado', 36)->unique();
                // UUID v4 generado al crear la cuenta.
                // Es el ÚNICO dato que se envía a la API Claude (RN-01).
                // Nunca se expone al usuario: es interno del sistema.
                // Generación: Str::uuid()->toString()

            // ── Estado de la cuenta ───────────────────────────────────
            $table->boolean('activo')->default(true);
                // FALSE → la cuenta fue desactivada (egresado o suspensión).
                // Las cuentas inactivas no pueden iniciar sesión.

            $table->date('fecha_baja_programada')->nullable();
                // RF-18 / RN-10: se escribe cuando se cierra la matrícula
                // de 3° grado. NULL mientras el estudiante está activo.
                // Calculada por MatriculaService::calcularFechaBaja().
                // El EgresadoCleanupJob monitorea este campo diariamente.

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
