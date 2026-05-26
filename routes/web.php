<?php

use App\Http\Controllers\AuthController;   
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Rutas Públicas (Cualquiera las puede ver sin iniciar sesión) ─────────
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/terms', function () {
    return view('legal.terms');
})->name('terms');

Route::get('/aviso-de-privacidad', function () {
    return view('legal.privacy');
})->name('privacy');


// ── Procesamiento de Autenticación Pública (Mapeado de Formularios) ───────
Route::post('/login',    [AuthController::class, 'loginWeb'])->name('auth.login');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/logout',   [AuthController::class, 'logoutWeb'])->name('auth.logout')->middleware('auth');

// ──  Rutas Privadas / Protegidas (Solo para usuarios logueados) ──────────
Route::middleware(['auth'])->group(function () {

    // Raíz redirecciona automáticamente al dashboard analítico según el rol
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Módulos Administrativos
    Route::get('/instituciones',                        [InstitutionController::class, 'index'])->name('instituciones.index');
    Route::post('/instituciones',                       [InstitutionController::class, 'store'])->name('instituciones.store');
    Route::put('/instituciones/{institution}',          [InstitutionController::class, 'update'])->name('instituciones.update');
    Route::patch('/instituciones/{institution}/toggle', [InstitutionController::class, 'toggleStatus'])->name('instituciones.toggle');

    // Membresías y Edición Administrativa
    Route::get('/membresias', function () {
        return view('membresias.index');
    })->name('membresias.index');

    Route::get('/admin/edicion', function () {
        return view('admin.edicion');
    })->name('admin.edicion');

    // Módulos de Control de Aulas e Inscripciones
    Route::get('/aulas', function () {
        return view('aulas.index');
    })->name('aulas.index');

    Route::get('/aulas/create', function () {
        return view('aulas.create');
    })->name('aulas.create');

    Route::get('/ciclo/cierre', function () {
        return view('ciclo.cierre');
    })->name('ciclo.cierre');

    // Sistema Analítico de Asistencias y Reportes
    Route::get('/asistencias/docente', function () {
        return view('asistencias.docente');
    })->name('asistencias.docente');

    Route::get('/asistencias/alumno', function () {
        return view('asistencias.alumno');
    })->name('asistencias.alumno');

    Route::get('/justificantes', function () {
        return view('justificantes.index');
    })->name('justificantes.index');

    Route::get('/reportes', function () {
        return view('reportes.index');
    })->name('reportes.index');
});