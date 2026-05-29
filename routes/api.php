<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionKeyController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\JustificationController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminEditController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Auth only (no plan check needed) ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ── Protected (token + plan + audit) ─────────────────────────────────────────
Route::middleware(['auth:sanctum', 'plan', 'auditoria'])->group(function () {

    // Dashboard Polimórfico (Disponible para cualquier rol autenticado)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Administrator
    Route::middleware('role:Administrator')->group(function () {
        Route::apiResource('institutions', InstitutionController::class);
        Route::apiResource('plans', PlanController::class);
        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::post('subscriptions/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::get('payments', [PaymentController::class, 'indexApi']);
        Route::post('admin/corrections', [AdminEditController::class, 'correctAttendance']);
        Route::post('admin/drop-student/{enrollment}', [AdminEditController::class, 'dropStudent']);
        Route::delete('admin/delete-session/{session}', [AdminEditController::class, 'deleteSession']);
    });

    // Teacher
    Route::middleware('role:Teacher')->group(function () {
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('sessions', SessionController::class)->parameters(['sessions' => 'session']);
        Route::post('classrooms/{classroom}/invitation-codes', [InvitationCodeController::class, 'store']);
        Route::post('sessions/{session}/keys', [SessionKeyController::class, 'store']);
        Route::post('sessions/{session}/close', [SessionController::class, 'close']);
        Route::post('cycles/{cycle}/close', [CycleController::class, 'close']);
        Route::get('reports/matrix/{classroom}', [ReportController::class, 'matrix']);
        Route::get('reports/monthly/{classroom}', [ReportController::class, 'monthly']);
        Route::post('reports/send/{classroom}', [ReportController::class, 'send']);
    });

    // Student
    Route::middleware('role:Student')->group(function () {
        Route::post('enrollments', [EnrollmentController::class, 'store']);
        Route::post('attendances', [AttendanceController::class, 'register']);
        Route::get('progress/{classroom}', [AttendanceController::class, 'progress']);
        Route::get('portal/{classroom}', [AttendanceController::class, 'portal']);
        Route::post('justifications', [JustificationController::class, 'store']);
    });

    // Teacher — justificantes
    Route::middleware('role:Teacher')->group(function () {
        Route::patch('justifications/{justification}/review', [JustificationController::class, 'review'])
            ->name('justifications.review');
    });

    // Teacher + Administrator — consulta de justificantes
    Route::middleware('role:Teacher,Administrator')->group(function () {
        Route::get('justifications/{justification}', [JustificationController::class, 'show'])
            ->name('justifications.show');
    });
});