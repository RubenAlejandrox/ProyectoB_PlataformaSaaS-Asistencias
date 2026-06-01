<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\SessionKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionKeyController extends Controller
{
    private const ALLOWED_DURATION_SECONDS = [45, 60, 180];

    // ── store — generar clave de asistencia para una sesión ───────────────────
    public function store(Request $request, Session $session): JsonResponse
    {
        $request->validate([
            'duration_seconds' => 'sometimes|integer|in:'.implode(',', self::ALLOWED_DURATION_SECONDS),
        ]);
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

        $durationSeconds = (int) $request->input('duration_seconds', 60);
        if (! in_array($durationSeconds, self::ALLOWED_DURATION_SECONDS, true)) {
            $durationSeconds = 60;
        }

        do {
            $accessKey = strtoupper(Str::random(8));
        } while (SessionKey::withoutGlobalScopes()->where('access_key', $accessKey)->exists());

        $sessionKey = SessionKey::create([
            'session_id' => $session->id,
            'access_key' => $accessKey,
            'expires_at' => now()->addSeconds($durationSeconds),
            'is_active'  => true,
        ]);

        return response()->json([
            'message' => 'Clave de asistencia generada.',
            'data'    => [
                'id'               => $sessionKey->id,
                'access_key'       => $sessionKey->access_key,
                'expires_at'       => $sessionKey->expires_at->toIso8601String(),
                'duration_seconds' => $durationSeconds,
            ],
        ], 201);
    }
}
