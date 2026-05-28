<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SessionKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // ── register — alumno registra asistencia con clave de sesión ─────────────
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'access_key' => 'required|string|size:8',
        ]);

        $accessKey = strtoupper($request->access_key);

        $sessionKey = SessionKey::withoutGlobalScopes()
            ->with(['session.classroom'])
            ->where('access_key', $accessKey)
            ->first();

        if (!$sessionKey || !$sessionKey->isValid()) {
            return response()->json([
                'message' => 'Clave de asistencia inválida o expirada.',
            ], 422);
        }

        $session = $sessionKey->session;

        if (!$session || !$session->is_active) {
            return response()->json([
                'message' => 'La sesión no está activa.',
            ], 422);
        }

        $studentId   = auth()->user()->id;
        $classroomId = $session->classroom_id;

        $isEnrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'message' => 'No estás inscrito en el aula de esta sesión.',
            ], 403);
        }

        $alreadyRegistered = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'message' => 'Ya registraste asistencia en esta sesión.',
            ], 409);
        }

        $attendance = Attendance::create([
            'session_id'  => $session->id,
            'student_id'  => $studentId,
            'status'      => 'present',
        ]);

        return response()->json([
            'message' => 'Asistencia registrada.',
            'data'    => $attendance,
        ], 201);
    }

    // ── progress — progreso del alumno en un aula ─────────────────────────────
    public function progress(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        $enrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        $totalSessions = \App\Models\Session::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->count();

        $presentCount = Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('status', 'present')
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->count();

        $pct = $totalSessions > 0
            ? round(($presentCount / $totalSessions) * 100, 1)
            : 0;

        return response()->json([
            'classroom_id'     => $classroomId,
            'total_sessions'   => $totalSessions,
            'present_count'    => $presentCount,
            'attendance_pct'   => $pct,
        ]);
    }

    // ── portal — historial de asistencias del alumno en un aula ───────────────
    public function portal(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        $enrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        $attendances = Attendance::withoutGlobalScopes()
            ->with('session:id,classroom_id,session_date')
            ->where('student_id', $studentId)
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $attendances]);
    }
}
