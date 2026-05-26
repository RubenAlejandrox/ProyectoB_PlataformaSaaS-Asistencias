<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API (API Routes)
|--------------------------------------------------------------------------
| Estas rutas son cargadas por el RouteServiceProvider dentro de un grupo
| al que se le asigna el grupo de middleware "api"
|--------------------------------------------------------------------------
*/

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// ── Protected routes (coming soon) ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Dashboard, classrooms, sessions, etc.
});