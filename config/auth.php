<?php

/**
 * CONFIGURACIÓN DE AUTENTICACIÓN — config/auth.php
 * =========================================================================
 * Define DOS guards independientes para el sistema:
 *
 * 1. GUARD 'web' → Adultos (docentes, padres, administradora)
 *    Tabla: usuarios | Credenciales: email + password (bcrypt)
 *
 * 2. GUARD 'estudiante' → Estudiantes de 6-9 años
 *    Tabla: estudiantes | Credenciales: codigo_acceso + PIN (4 dígitos)
 *    Provider personalizado: EstudianteProvider
 *
 * Referencia: RF-01 (autenticación por perfil)
 * CU: CU-01 (login adulto), CU-02 (login estudiante)
 * =========================================================================
 */
return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'usuarios',
    ],

    'guards' => [

        // ── Guard adultos (docente | padre | admin) ────────────────
        'web' => [
            'driver'   => 'session',
            'provider' => 'usuarios',
        ],

        // ── Guard estudiantes (código_acceso + PIN de 4 dígitos) ───
        // Driver personalizado registrado en AppServiceProvider
        'estudiante' => [
            'driver'   => 'session',
            'provider' => 'estudiantes',
        ],
    ],

    'providers' => [

        // Provider estándar Eloquent para adultos (email + password)
        'usuarios' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Usuario::class,
        ],

        // Provider personalizado para estudiantes
        // Registrado en AppServiceProvider::boot() como 'estudiante-provider'
        'estudiantes' => [
            'driver' => 'estudiante-provider',
            'model'  => App\Models\Estudiante::class,
        ],
    ],

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
