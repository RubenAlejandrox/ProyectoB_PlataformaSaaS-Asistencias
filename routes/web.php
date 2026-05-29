<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\JustificationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AttendanceWebController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\AdminEditController;
use App\Http\Controllers\ProfileController;


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

    Route::get('/perfil', [ProfileController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('perfil.password');

    // Módulos Administrativos
    Route::middleware('role:Administrator')->group(function () {
        Route::get('/instituciones',                        [InstitutionController::class, 'index'])->name('instituciones.index');
        Route::post('/instituciones',                       [InstitutionController::class, 'store'])->name('instituciones.store');
        Route::put('/instituciones/{institution}',          [InstitutionController::class, 'update'])->name('instituciones.update');
        Route::patch('/instituciones/{institution}/toggle', [InstitutionController::class, 'toggleStatus'])->name('instituciones.toggle');
        Route::post('/instituciones/{institution}/generate-code', [InstitutionController::class, 'generateCode'])->name('instituciones.generate-code');

        // ── PayPal callbacks ──────────────────────────────────────────────
        Route::get('/paypal/success',      [SubscriptionController::class, 'paypalSuccess'])->name('paypal.success');
        Route::get('/paypal/cancel',       [SubscriptionController::class, 'paypalCancel'])->name('paypal.cancel');
        Route::get('/membresias',          [SubscriptionController::class, 'index'])->name('membresias.index');
        Route::post('/membresias/upgrade', [SubscriptionController::class, 'upgrade'])->name('membresias.upgrade');
    });

    // Edición Administrativa
    Route::get('/admin/edicion', [AdminEditController::class, 'index'])
        ->middleware('role:Administrator')
        ->name('admin.edicion');
    Route::put('/admin/asistencia/{attendance}', [AdminEditController::class, 'correctAttendance'])
        ->middleware('role:Administrator')
        ->name('admin.asistencia.correct');
    Route::put('/admin/alumno/{enrollment}', [AdminEditController::class, 'dropStudent'])
        ->middleware('role:Administrator')
        ->name('admin.alumno.drop');
    Route::delete('/admin/sesion/{session}', [AdminEditController::class, 'deleteSession'])
        ->middleware('role:Administrator')
        ->name('admin.sesion.delete');
    Route::get('/admin/bitacora', [AuditLogController::class, 'index'])
        ->middleware('role:Administrator')
        ->name('bitacora.index');
    Route::get('/admin/bitacora/export', [AuditLogController::class, 'exportCsv'])
        ->middleware('role:Administrator')
        ->name('bitacora.export');
    Route::get('/admin/sesiones', [AdminSessionController::class, 'index'])
        ->middleware('role:Administrator')
        ->name('admin.sesiones.index');
    Route::post('/admin/sesiones/{session}/clave-asistencia', [AdminSessionController::class, 'generateAttendanceKey'])
        ->middleware('role:Administrator')
        ->name('admin.sesiones.attendance-key');

    // ── Aulas ─────────────────────────────────────────────────────────────
    Route::get('/aulas',                            [ClassroomController::class, 'index'])->name('aulas.index');
    Route::get('/aulas/create',                     [ClassroomController::class, 'create'])->name('aulas.create');
    Route::post('/aulas',                           [ClassroomController::class, 'store'])->name('aulas.store');
    Route::patch('/aulas/{classroom}/toggle',       [ClassroomController::class, 'toggleStatus'])->name('aulas.toggle');
    Route::post('/aulas/{classroom}/generate-code', [ClassroomController::class, 'generateCode'])->name('aulas.generate-code');

    // ── Códigos de invitación ─────────────────────────────────────────────
    Route::post('/aulas/{classroom}/invitation-codes',        [InvitationCodeController::class, 'store'])->name('invitation-codes.store');
    Route::get('/aulas/{classroom}/invitation-codes/active',  [InvitationCodeController::class, 'active'])->name('invitation-codes.active');

    Route::get('/ciclo/cierre', [CycleController::class, 'index'])
        ->middleware('role:Teacher')
        ->name('ciclo.cierre');
    Route::post('/ciclo/{cycle}/close', [CycleController::class, 'close'])
        ->middleware('role:Teacher')
        ->name('ciclo.close');

    // Sistema Analítico de Asistencias
    Route::middleware('role:Teacher')->group(function () {
        Route::get('/asistencias/docente', [AttendanceWebController::class, 'teacherIndex'])
            ->name('asistencias.docente');
        Route::post('/asistencias/docente/sesion', [AttendanceWebController::class, 'openSession'])
            ->name('asistencias.docente.sesion');
        Route::post('/asistencias/docente/sesiones/{session}/clave', [AttendanceWebController::class, 'generateKey'])
            ->name('asistencias.docente.clave');
        Route::post('/asistencias/docente/sesiones/{session}/cerrar', [AttendanceWebController::class, 'closeSession'])
            ->name('asistencias.docente.cerrar');
    });

    Route::middleware('role:Student')->group(function () {
        Route::get('/asistencias/alumno', [AttendanceWebController::class, 'studentIndex'])
            ->name('asistencias.alumno');
        Route::post('/asistencias/alumno/registrar', [AttendanceWebController::class, 'register'])
            ->name('asistencias.alumno.registrar');
    });

    Route::post('/inscripciones', [EnrollmentController::class, 'storeWeb'])->name('enrollments.store');

    Route::get('/justificantes', [JustificationController::class, 'index'])->name('justificantes.index');
    Route::post('/justificantes', [JustificationController::class, 'storeWeb'])->name('justificantes.store');
    Route::patch('/justificantes/{justification}/review', [JustificationController::class, 'reviewWeb'])->name('justificantes.review');

    Route::get('/reportes', [ReportController::class, 'index'])
        ->middleware('role:Teacher,Administrator')
        ->name('reportes.index');
    Route::get('/reportes/{classroom}/matrix', [ReportController::class, 'matrix'])
        ->middleware('role:Teacher,Administrator')
        ->name('reportes.matrix');
    Route::get('/reportes/{classroom}/monthly', [ReportController::class, 'monthly'])
        ->middleware('role:Teacher,Administrator')
        ->name('reportes.monthly');
    Route::post('/reportes/{classroom}/send', [ReportController::class, 'send'])
        ->middleware('role:Teacher,Administrator')
        ->name('reportes.send');
});