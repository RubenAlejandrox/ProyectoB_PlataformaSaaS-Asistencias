<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\SubscriptionController;


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
    Route::post('/instituciones/{institution}/generate-code', [InstitutionController::class, 'generateCode'])->name('instituciones.generate-code');
    
    // ── PayPal callbacks ──────────────────────────────────────────────────
    Route::get('/paypal/success',      [SubscriptionController::class, 'paypalSuccess'])->name('paypal.success');
    Route::get('/paypal/cancel',       [SubscriptionController::class, 'paypalCancel'])->name('paypal.cancel');
    Route::get('/membresias',          [SubscriptionController::class, 'index'])->name('membresias.index');
    Route::post('/membresias/upgrade', [SubscriptionController::class, 'upgrade'])->name('membresias.upgrade');

    // Edición Administrativa (Membresías ya está registrada arriba con SubscriptionController)
    Route::get('/admin/edicion', function () {
        return view('admin.edicion');
    })->name('admin.edicion');

    // ── Aulas ─────────────────────────────────────────────────────────────
    Route::get('/aulas',                            [ClassroomController::class, 'index'])->name('aulas.index');
    Route::get('/aulas/create',                     [ClassroomController::class, 'create'])->name('aulas.create');
    Route::post('/aulas',                           [ClassroomController::class, 'store'])->name('aulas.store');
    Route::patch('/aulas/{classroom}/toggle',       [ClassroomController::class, 'toggleStatus'])->name('aulas.toggle');
    Route::post('/aulas/{classroom}/generate-code', [ClassroomController::class, 'generateCode'])->name('aulas.generate-code');

    // ── Códigos de invitación ─────────────────────────────────────────────
    Route::post('/aulas/{classroom}/invitation-codes',        [InvitationCodeController::class, 'store'])->name('invitation-codes.store');
    Route::get('/aulas/{classroom}/invitation-codes/active',  [InvitationCodeController::class, 'active'])->name('invitation-codes.active');

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