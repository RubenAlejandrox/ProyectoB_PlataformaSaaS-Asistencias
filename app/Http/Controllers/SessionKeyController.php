<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\SessionKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SessionKeyController extends Controller
{
    // ── store — generar clave de asistencia para una sesión ───────────────────
    public function store(Session $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403, 'No tienes permiso para generar claves en esta sesión.');
        }

        if (!$session->is_active) {
            return response()->json([
                'message' => 'La sesión está cerrada. No se pueden generar claves.',
            ], 422);
        }

        // Desactivar claves anteriores de la misma sesión
        $session->sessionKeys()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        do {
            $accessKey = strtoupper(Str::random(8));
        } while (SessionKey::withoutGlobalScopes()->where('access_key', $accessKey)->exists());

        $sessionKey = SessionKey::create([
            'session_id' => $session->id,
            'access_key' => $accessKey,
            'expires_at' => now()->addHours(2),
            'is_active'  => true,
        ]);

        return response()->json([
            'message' => 'Clave de asistencia generada.',
            'data'    => [
                'access_key' => $sessionKey->access_key,
                'expires_at' => $sessionKey->expires_at->toIso8601String(),
            ],
        ], 201);
    }
}
