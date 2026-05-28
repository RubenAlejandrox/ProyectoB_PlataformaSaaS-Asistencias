<?php

namespace App\Http\Controllers;

use App\Models\AcademicCycle;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Session;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminEditController extends Controller
{
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $classrooms = \App\Models\Classroom::withoutGlobalScopes()
            ->where('institution_id', auth()->user()->institution_id)
            ->orderBy('subject_name')
            ->get();

        $attendances = Attendance::withoutGlobalScopes()
            ->with(['student', 'session.classroom'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $enrollments = Enrollment::withoutGlobalScopes()
            ->with(['student', 'classroom'])
            ->where('is_active', true)
            ->whereHas('classroom', fn ($q) => $q->where('institution_id', auth()->user()->institution_id))
            ->limit(50)
            ->get();

        $sessions = Session::withoutGlobalScopes()
            ->with('classroom')
            ->whereHas('classroom', fn ($q) => $q->where('institution_id', auth()->user()->institution_id))
            ->orderByDesc('session_date')
            ->limit(50)
            ->get();

        $recentLogs = AuditLog::withoutGlobalScopes()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.edicion', compact('classrooms', 'attendances', 'enrollments', 'sessions', 'recentLogs'));
    }

    public function correctAttendance(Request $request, ?Attendance $attendance = null): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $request->validate([
            'attendance_id' => 'sometimes|uuid|exists:attendances,id',
            'status'        => 'required|in:present,absent',
            'reason'        => 'required|string|max:255',
        ]);

        $attendance = $attendance ?: Attendance::withoutGlobalScopes()->findOrFail($request->attendance_id);
        $session = $attendance->session()->with('classroom')->firstOrFail();
        $studentId = $attendance->student_id;
        $classroomId = $session->classroom_id;
        $previousLight = $this->progressService->calculate($studentId, $classroomId)['light'];
        $old = $attendance->toArray();

        DB::transaction(function () use ($attendance, $request, $old) {
            $this->createAudit(
                entity: 'attendances',
                entityId: (string) $attendance->id,
                action: 'correction',
                oldValue: $old,
                newValue: [
                    'status' => $request->status,
                    'reason' => $request->reason,
                ],
            );

            $attendance->update(['status' => $request->status]);
        });

        $this->progressService->dispatchTrafficLightIfChanged($studentId, $classroomId, $previousLight);

        return $this->okResponse($request, 'Asistencia corregida correctamente.');
    }

    public function dropStudent(Request $request, Enrollment $enrollment): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if (!$enrollment->is_active) {
            return $this->errorResponse($request, 'El alumno ya está dado de baja.', 422);
        }

        $old = $enrollment->toArray();
        DB::transaction(function () use ($request, $enrollment, $old) {
            $this->createAudit(
                entity: 'enrollments',
                entityId: (string) $enrollment->id,
                action: 'drop_student',
                oldValue: $old,
                newValue: ['is_active' => false, 'reason' => $request->reason],
            );

            $enrollment->update(['is_active' => false]);
        });

        return $this->okResponse($request, 'Alumno dado de baja correctamente.');
    }

    public function deleteSession(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $session->load('classroom');

        $studentIds = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->pluck('student_id')
            ->unique()
            ->values();

        $previousLights = [];
        foreach ($studentIds as $studentId) {
            $previousLights[(string) $studentId] = $this->progressService->calculate((string) $studentId, $session->classroom_id)['light'];
        }

        $old = $session->toArray();
        DB::transaction(function () use ($request, $session, $old) {
            $this->createAudit(
                entity: 'class_sessions',
                entityId: (string) $session->id,
                action: 'delete_session',
                oldValue: $old,
                newValue: ['deleted' => true, 'reason' => $request->reason],
            );

            $session->delete();
        });

        foreach ($studentIds as $studentId) {
            $this->progressService->dispatchTrafficLightIfChanged(
                (string) $studentId,
                $old['classroom_id'],
                $previousLights[(string) $studentId] ?? 'green'
            );
        }

        return $this->okResponse($request, 'Sesión eliminada correctamente.');
    }

    private function createAudit(
        string $entity,
        string $entityId,
        string $action,
        array $oldValue,
        array $newValue
    ): void {
        AuditLog::create([
            'user_id'   => auth()->user()->id,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'action'    => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    private function okResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->withErrors(['general' => $message]);
    }
}
