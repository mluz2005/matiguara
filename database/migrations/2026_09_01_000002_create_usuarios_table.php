<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: usuarios
 * -------------------------------------------------------------------------
 * Grupo 1 — Institucional | Tabla 2 de 21
 *
 * Almacena las cuentas de acceso para usuarios adultos del sistema:
 * docentes, padres/tutores y administradoras.
 *
 * IMPORTANTE: Los estudiantes NO usan esta tabla. Su acceso es por
 * código + PIN en la tabla 'estudiantes' (ver RF-01, RN-01).
 *
 * La columna 'perfil' actúa como discriminador de roles y determina
 * qué vistas y funcionalidades se habilitan tras el login (RF-01).
 *
 * Referencia: Sección 1.3.9 — Diccionario de Datos, Tabla 2 de 21
 * RF relacionados: RF-01 (autenticación por perfil)
 * -------------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {

            // ── Clave primaria ────────────────────────────────────────
            $table->id();

            // ── Datos personales ──────────────────────────────────────
            $table->string('nombre', 100);
            $table->string('apellido', 100);

            $table->string('email', 191)->unique();
                // Clave única: es el identificador de login para adultos.
                // Máximo 191 chars por compatibilidad con índices MySQL utf8mb4.

            // ── Seguridad ─────────────────────────────────────────────
            $table->string('password_hash', 255);
                // Contraseña almacenada como hash bcrypt (costo 12).
                // NUNCA se almacena texto plano: cumple OWASP Top-10 A02.
                // En Laravel: Hash::make($password)

            // ── Control de acceso ─────────────────────────────────────
            $table->enum('perfil', ['docente', 'padre', 'admin']);
                // Rol del usuario en el sistema:
                // 'docente'  → accede al panel docente (RF-08)
                // 'padre'    → accede al panel parental (RF-09)
                // 'admin'    → accede al panel de administración (CU-22..CU-26)

            // ── Estado ────────────────────────────────────────────────
            $table->boolean('activo')->default(true);
                // Cuentas inactivas no pueden iniciar sesión.
                // Permite suspender sin eliminar (auditoría).

            // ── Auditoría ─────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
