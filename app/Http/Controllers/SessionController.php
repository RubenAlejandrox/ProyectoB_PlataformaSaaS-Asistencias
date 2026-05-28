<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    // ── index ─────────────────────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $sessions = Session::with(['classroom', 'sessionKeys'])
            ->whereHas('classroom', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'classroom_id' => 'required|uuid|exists:classrooms,id',
            'session_date' => 'required|date',
        ]);

        $classroom = Classroom::where('id', $request->classroom_id)
            ->where('teacher_id', auth()->user()->id)
            ->first();

        if (!$classroom) {
            return response()->json([
                'message' => 'Aula no encontrada o no tienes permiso para abrir sesión.',
            ], 403);
        }

        if (!$classroom->is_active) {
            return response()->json([
                'message' => 'No se puede abrir sesión en un aula con ciclo cerrado.',
            ], 422);
        }

        $session = Session::create([
            'classroom_id' => $classroom->id,
            'session_date' => $request->session_date,
            'started_at'   => now()->format('H:i:s'),
            'is_active'    => true,
        ]);

        $session->load('classroom');

        return response()->json([
            'message' => 'Sesión iniciada.',
            'data'    => $session,
        ], 201);
    }

    // ── show ──────────────────────────────────────────────────────────────────
    public function show(Session $session): JsonResponse
    {
        $this->authorizeTeacherSession($session);

        $session->load(['classroom', 'sessionKeys', 'attendances']);

        return response()->json(['data' => $session]);
    }

    // ── update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Session $session): JsonResponse
    {
        $this->authorizeTeacherSession($session);

        $request->validate([
            'is_active' => 'sometimes|boolean',
            'ended_at'  => 'nullable|date_format:H:i:s',
        ]);

        $payload = $request->only(['is_active', 'ended_at']);

        if ($request->boolean('is_active') === false && !isset($payload['ended_at'])) {
            $payload['ended_at'] = now()->format('H:i:s');
        }

        $session->update($payload);

        return response()->json([
            'message' => 'Sesión actualizada.',
            'data'    => $session->fresh(),
        ]);
    }

    // ── destroy ───────────────────────────────────────────────────────────────
    public function destroy(Session $session): JsonResponse
    {
        $this->authorizeTeacherSession($session);

        $session->delete();

        return response()->json(['message' => 'Sesión eliminada.']);
    }

    private function authorizeTeacherSession(Session $session): void
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403, 'No tienes permiso sobre esta sesión.');
        }
    }
}
