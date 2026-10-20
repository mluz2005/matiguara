<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginFamiliaController;
use App\Http\Controllers\Auth\LoginEstudianteController;
use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\DocenteController;
use App\Http\Controllers\Dashboard\DocenteNivelesController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\Dashboard\EstudianteController;
use App\Http\Controllers\Dashboard\PadreController;
use Illuminate\Support\Facades\Route;

/**
 * RUTAS COMPLETAS — routes/web.php (Semana 6 — versión final)
 * =========================================================================
 * Versión actualizada con todas las rutas del sistema:
 *
 *   Públicas:    / | /login | /acceso
 *   Docente:     panel, estudiantes, progreso, diagnóstico IA, juegos, cuentas
 *   Estudiante:  juegos, sesión (iniciar/ejercicio/responder/resumen), progreso, logros
 *   Padre:       panel, vincular, logros
 *   Admin:       panel, instituciones (CRUD + config), docentes, mensajes, juegos
 * =========================================================================
 */

// ==========================================================================
// RUTAS PÚBLICAS
// ==========================================================================

Route::get('/', function () {
    return view('welcome');
})->name('inicio');

// ── Acerca de MatiGuará (pública: la ve cualquier perfil, con o sin sesión) ──
Route::get('/acerca-de', [AyudaController::class, 'acerca'])->name('acerca');

// ── Ayuda para adultos (docentes, familias, administración): muestra la guía según el perfil ──
Route::get('/ayuda', [AyudaController::class, 'adultos'])->middleware('auth')->name('ayuda');

// ── Login adultos ──────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'mostrarFormulario'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'autenticar'])->name('login.post');
Route::post('/logout',[LoginController::class, 'cerrarSesion'])->name('logout');

// ── Acceso para familias: correo del tutor (cargado por la docente) + código del niño/a ──
Route::get('/familias',  [LoginFamiliaController::class, 'mostrarFormulario'])->name('familia.login')->middleware('guest');
Route::post('/familias', [LoginFamiliaController::class, 'autenticar'])->name('familia.login.post');

// ── Login estudiantes ──────────────────────────────────────────────────────
Route::get('/acceso',        [LoginEstudianteController::class, 'mostrarFormulario'])->name('estudiante.login')->middleware('guest:estudiante');
Route::post('/acceso',       [LoginEstudianteController::class, 'autenticar'])->name('estudiante.login.post');
Route::post('/acceso/salir', [LoginEstudianteController::class, 'cerrarSesion'])->name('estudiante.logout');

// ==========================================================================
// RUTAS DOCENTE
// ==========================================================================
Route::middleware(['auth', 'es.docente'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function () {

    Route::get('/panel',      [DocenteController::class, 'panel'])->name('panel');
    Route::get('/estudiantes',[DocenteController::class, 'estudiantes'])->name('estudiantes');

    // Progreso individual
    Route::get('/estudiantes/{id}/progreso',
        [DocenteController::class, 'progresoEstudiante'])->name('estudiante.progreso');

    // Abrir niveles a un alumno (2026-10-12)
    Route::post('/estudiantes/{id}/niveles/{nivel}/abrir',
        [DocenteNivelesController::class, 'abrirNivel'])->name('estudiante.nivel.abrir');
    Route::post('/estudiantes/{id}/juegos/{juego}/abrir-todos',
        [DocenteNivelesController::class, 'abrirTodos'])->name('estudiante.juego.abrir-todos');

    // Diagnóstico IA (CU-11, CU-12)
    Route::get('/estudiantes/{id}/diagnostico',
        [DocenteController::class, 'solicitudDiagnostico'])->name('estudiante.diagnostico');
    Route::post('/estudiantes/{id}/diagnostico/generar',
        [DocenteController::class, 'generarDiagnostico'])->name('estudiante.generar-diagnostico');
    Route::get('/estudiantes/{id}/diagnostico/{diagnostico}',
        [DocenteController::class, 'verDiagnostico'])->name('estudiante.ver-diagnostico');
    Route::get('/estudiantes/{id}/diagnosticos',
        [DocenteController::class, 'historialDiagnosticos'])->name('estudiante.diagnosticos');

    // Gestión de cuentas (CU-14)
    Route::get('/cuentas',         [DocenteController::class, 'cuentas'])->name('cuentas');
    Route::post('/cuentas',        [DocenteController::class, 'crearCuenta'])->name('cuentas.crear');
    // Fichas de acceso imprimibles (cuentas/fichas va ANTES de cuentas/{id}/ficha)
    Route::get('/cuentas/fichas',      [DocenteController::class, 'fichas'])->name('cuentas.fichas');
    Route::get('/cuentas/{id}/ficha',  [DocenteController::class, 'ficha'])->name('cuentas.ficha');
    Route::put('/cuentas/{id}',    [DocenteController::class, 'editarCuenta'])->name('cuentas.editar');

    // Configuración de juegos (CU-13)
    Route::get('/juegos',          [DocenteController::class, 'configurarJuegos'])->name('juegos');
    Route::post('/juegos',         [DocenteController::class, 'guardarConfigJuegos'])->name('juegos.guardar');
});

// ==========================================================================
// RUTAS ESTUDIANTE
// ==========================================================================
Route::middleware(['auth:estudiante', \App\Http\Middleware\EstudianteActivo::class])
    ->prefix('estudiante')
    ->name('estudiante.')
    ->group(function () {

    Route::get('/juegos',            [EstudianteController::class, 'seleccionJuego'])->name('juegos');
    Route::get('/progreso',          [EstudianteController::class, 'miProgreso'])->name('progreso');
    Route::get('/logros',            [EstudianteController::class, 'misLogros'])->name('logros');

    // Ayuda para niños: "¿Cómo se juega?" (general o de un juego)
    Route::get('/ayuda/{juego?}',    [AyudaController::class, 'ninos'])->name('ayuda');

    // Flujo de sesión de juego
    Route::post('/juego/{juego}/nivel/{nivel}/iniciar',
        [EstudianteController::class, 'iniciarSesion'])->name('sesion.iniciar');
    Route::get('/sesion/{sesion}/ejercicio',
        [EstudianteController::class, 'mostrarEjercicio'])->name('sesion.ejercicio');
    Route::post('/sesion/{sesion}/responder',
        [EstudianteController::class, 'procesarRespuesta'])->name('sesion.responder');
    Route::get('/sesion/{sesion}/resumen',
        [EstudianteController::class, 'resumenSesion'])->name('sesion.resumen');
        // Juego "Encuentra el par" — mecánica de memoria (Semana 8)
Route::post('/memoria/{juego}/nivel/{nivel}/iniciar',
    [\App\Http\Controllers\Dashboard\MemoriaController::class, 'iniciarSesion'])
    ->name('memoria.iniciar');

Route::get('/memoria/sesion/{sesion}/tablero',
    [\App\Http\Controllers\Dashboard\MemoriaController::class, 'mostrarTablero'])
    ->name('memoria.tablero');

Route::post('/memoria/sesion/{sesion}/movimiento',
    [\App\Http\Controllers\Dashboard\MemoriaController::class, 'registrarMovimiento'])
    ->name('memoria.movimiento');

Route::post('/memoria/sesion/{sesion}/cerrar',
    [\App\Http\Controllers\Dashboard\MemoriaController::class, 'cerrarSesion'])
    ->name('memoria.cerrar');
});

// ==========================================================================
// RUTAS PADRE/TUTOR
// ==========================================================================
Route::middleware(['auth', 'es.padre'])
    ->prefix('padre')
    ->name('padre.')
    ->group(function () {

    Route::get('/panel',          [PadreController::class, 'panel'])->name('panel');
    Route::get('/vincular',       [PadreController::class, 'mostrarVincular'])->name('vincular');
    Route::post('/vincular',      [PadreController::class, 'vincular'])->name('vincular.post');
    Route::get('/logros',         [PadreController::class, 'logrosHijo'])->name('logros');
});

// ==========================================================================
// RUTAS ADMINISTRADORA
// ==========================================================================
Route::middleware(['auth', 'es.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    Route::get('/panel', [AdminController::class, 'panel'])->name('panel');

    // Instituciones (CU-19, CU-27)
    Route::get('/instituciones',           [AdminController::class, 'index'])->name('instituciones.index');
    Route::get('/instituciones/crear',     [AdminController::class, 'create'])->name('instituciones.create');
    Route::post('/instituciones',          [AdminController::class, 'store'])->name('instituciones.store');
    Route::get('/instituciones/{id}/editar',  [AdminController::class, 'edit'])->name('instituciones.edit');
    Route::put('/instituciones/{id}',      [AdminController::class, 'update'])->name('instituciones.update');

    // Configuración institucional (CU-25)
    Route::get('/instituciones/{id}/config',  [AdminController::class, 'config'])->name('instituciones.config');
    Route::put('/instituciones/{id}/config',  [AdminController::class, 'guardarConfig'])->name('instituciones.config.guardar');

    // Docentes (CU-20)
    Route::get('/docentes',            [AdminController::class, 'docentes'])->name('docentes');
    Route::post('/docentes',           [AdminController::class, 'crearDocente'])->name('docentes.crear');
    Route::put('/docentes/{id}/desactivar', [AdminController::class, 'desactivarDocente'])->name('docentes.desactivar');

    // Mensajes retroalimentación (CU-22)
    Route::get('/mensajes',            [AdminController::class, 'mensajes'])->name('mensajes');
    Route::post('/mensajes',           [AdminController::class, 'guardarMensaje'])->name('mensajes.guardar');

    // Catálogo de juegos (CU-21)
    Route::get('/juegos',              [AdminController::class, 'juegos'])->name('juegos');
});
