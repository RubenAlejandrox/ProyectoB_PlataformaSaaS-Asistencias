<?php

namespace App\Http\Controllers;

use App\Events\AttendanceRegistered;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SessionKey;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

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

        $progressBefore = $this->progressService->calculate($studentId, $classroomId);
        $previousLight  = $progressBefore['light'];

        $attendance = Attendance::create([
            'session_id' => $session->id,
            'student_id' => $studentId,
            'status'     => 'present',
        ]);

        event(new AttendanceRegistered($attendance, $classroomId));
        $this->progressService->dispatchTrafficLightIfChanged($studentId, $classroomId, $previousLight);

        $progress = $this->progressService->calculate($studentId, $classroomId);

        return response()->json([
            'message' => 'Asistencia registrada.',
            'data'    => $attendance,
            'progress'=> $progress,
        ], 201);
    }

    // ── progress — progreso del alumno en un aula ─────────────────────────────
    public function progress(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        if (!$this->isEnrolled($studentId, $classroomId)) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        return response()->json(
            $this->progressService->calculate($studentId, $classroomId)
        );
    }

    // ── portal — historial de asistencias del alumno en un aula ───────────────
    public function portal(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        if (!$this->isEnrolled($studentId, $classroomId)) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        $attendances = Attendance::withoutGlobalScopes()
            ->with(['session:id,classroom_id,session_date', 'justification'])
            ->where('student_id', $studentId)
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->get();

        $progress = $this->progressService->calculate($studentId, $classroomId);

        return response()->json([
            'progress'    => $progress,
            'attendances' => $attendances,
        ]);
    }

    private function isEnrolled(string $studentId, string $classroomId): bool
    {
        return Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }
}
