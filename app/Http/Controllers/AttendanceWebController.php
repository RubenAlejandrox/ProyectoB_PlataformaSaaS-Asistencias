<?php

namespace App\Http\Controllers;

use App\Events\AttendanceRegistered;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Session;
use App\Models\SessionKey;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceWebController extends Controller
{
    private const KEY_DURATIONS = [15, 30, 60];

    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    public function teacherIndex(Request $request): View
    {
        $user = auth()->user();

        $classrooms = Classroom::withoutGlobalScopes()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->orderBy('subject_name')
            ->get();

        $selectedId = $request->query('classroom', $classrooms->first()?->id);
        $classroom  = $classrooms->firstWhere('id', $selectedId) ?? $classrooms->first();

        $activeSession = null;
        $activeKey     = null;
        $students      = collect();
        $stats         = [
            'total_sessions'   => 0,
            'present_today'    => 0,
            'at_risk'          => 0,
            'pending_justif'   => 0,
            'enrolled_count'   => 0,
        ];
        $sessionsHistory = collect();

        if ($classroom) {
            $stats['total_sessions'] = Session::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->count();

            $stats['enrolled_count'] = Enrollment::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->where('is_active', true)
                ->count();

            $activeSession = Session::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->where('is_active', true)
                ->whereDate('session_date', today())
                ->with(['sessionKeys' => fn ($q) => $q->where('is_active', true)->latest()])
                ->first();

            $activeKey = $activeSession?->sessionKeys->first();

            if ($activeSession) {
                $stats['present_today'] = Attendance::withoutGlobalScopes()
                    ->where('session_id', $activeSession->id)
                    ->where('status', 'present')
                    ->count();
            }

            $enrollments = Enrollment::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->where('is_active', true)
                ->with('student:id,first_name,last_name,email')
                ->get();

            $todayAttendances = $activeSession
                ? Attendance::withoutGlobalScopes()
                    ->where('session_id', $activeSession->id)
                    ->get()
                    ->keyBy('student_id')
                : collect();

            $students = $enrollments->map(function ($enrollment) use ($classroom, $todayAttendances, &$stats) {
                $student  = $enrollment->student;
                $progress = $this->progressService->calculate($student->id, $classroom->id);

                if (in_array($progress['light'], ['amber', 'red'], true)) {
                    $stats['at_risk']++;
                }

                $today = $todayAttendances->get($student->id);

                return [
                    'id'            => $student->id,
                    'name'          => trim($student->first_name.' '.$student->last_name),
                    'initials'      => strtoupper(substr($student->first_name, 0, 1).substr($student->last_name, 0, 1)),
                    'pct'           => $progress['attendance_pct'],
                    'light'         => $progress['light'],
                    'today_status'  => $today?->status,
                    'today_time'    => $today?->created_at?->format('H:i'),
                ];
            });

            $stats['pending_justif'] = Justification::withoutGlobalScopes()
                ->where('status', 'pending')
                ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $classroom->id))
                ->count();

            $sessionsHistory = Session::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->orderByDesc('session_date')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return view('asistencias.docente', [
            'classrooms'      => $classrooms,
            'classroom'       => $classroom,
            'activeSession'   => $activeSession,
            'activeKey'       => $activeKey,
            'students'        => $students,
            'stats'           => $stats,
            'sessionsHistory' => $sessionsHistory,
            'keyDurations'    => self::KEY_DURATIONS,
        ]);
    }

    public function studentIndex(Request $request): View
    {
        $user = auth()->user();

        $classroomId = $request->query('classroom');
        $subjects    = $this->progressService->calculateForStudent($user);

        $selected = collect($subjects)->firstWhere('classroom_id', $classroomId)
            ?? ($subjects[0] ?? null);

        $history = collect();
        $absencesWithoutJustification = collect();
        if ($selected) {
            $history = Attendance::withoutGlobalScopes()
                ->with(['session', 'justification'])
                ->where('student_id', $user->id)
                ->whereHas('session', fn ($q) => $q->where('classroom_id', $selected['classroom_id']))
                ->orderByDesc('created_at')
                ->get();

            $absencesWithoutJustification = Attendance::withoutGlobalScopes()
                ->where('student_id', $user->id)
                ->where('status', 'absent')
                ->where('created_at', '>=', now()->subHours(72))
                ->whereHas('session', fn ($q) => $q->where('classroom_id', $selected['classroom_id']))
                ->whereDoesntHave('justification')
                ->with('session')
                ->get();
        }

        $globalPct = count($subjects) > 0
            ? round(collect($subjects)->avg('attendance_pct'), 1)
            : 0;

        $totalPresent = collect($subjects)->sum('present_count');
        $totalAbsent  = $history->where('status', 'absent')->count();
        $totalJustif  = collect($subjects)->sum('approved_count');

        return view('asistencias.alumno', [
            'subjects'     => $subjects,
            'selected'     => $selected,
            'history'      => $history,
            'globalPct'    => $globalPct,
            'totalPresent' => $totalPresent,
            'totalAbsent'  => $totalAbsent,
            'totalJustif'  => $totalJustif,
            'absencesWithoutJustification' => $absencesWithoutJustification,
            'user'         => $user,
        ]);
    }

    public function openSession(Request $request): RedirectResponse
    {
        $request->validate([
            'classroom_id' => 'required|uuid|exists:classrooms,id',
        ]);

        $classroom = Classroom::withoutGlobalScopes()
            ->where('id', $request->classroom_id)
            ->where('teacher_id', auth()->user()->id)
            ->firstOrFail();

        $existing = Session::withoutGlobalScopes()
            ->where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->whereDate('session_date', today())
            ->first();

        if ($existing) {
            return redirect()
                ->route('asistencias.docente', ['classroom' => $classroom->id])
                ->with('success', 'Ya existe una sesión activa para hoy.');
        }

        Session::create([
            'classroom_id' => $classroom->id,
            'session_date' => today()->toDateString(),
            'started_at'   => now()->format('H:i:s'),
            'is_active'    => true,
        ]);

        return redirect()
            ->route('asistencias.docente', ['classroom' => $classroom->id])
            ->with('success', 'Sesión iniciada correctamente.');
    }

    public function generateKey(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403);
        }

        if (!$session->is_active) {
            return $request->expectsJson()
                ? response()->json(['message' => 'La sesión está cerrada.'], 422)
                : back()->withErrors(['general' => 'La sesión está cerrada.']);
        }

        $request->validate([
            'duration_minutes' => 'required|integer|in:'.implode(',', self::KEY_DURATIONS),
        ]);

        $duration = (int) $request->duration_minutes;

        $session->sessionKeys()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        do {
            $accessKey = strtoupper(Str::random(8));
        } while (SessionKey::withoutGlobalScopes()->where('access_key', $accessKey)->exists());

        $sessionKey = SessionKey::create([
            'session_id' => $session->id,
            'access_key' => $accessKey,
            'expires_at' => now()->addMinutes($duration),
            'is_active'  => true,
        ]);

        $payload = [
            'access_key'       => $sessionKey->access_key,
            'expires_at'       => $sessionKey->expires_at->toIso8601String(),
            'duration_minutes' => $duration,
        ];

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Clave generada.', 'data' => $payload], 201);
        }

        return redirect()
            ->route('asistencias.docente', ['classroom' => $session->classroom_id])
            ->with('success', 'Clave generada: '.$sessionKey->access_key);
    }

    public function closeSession(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403);
        }

        if (!$session->is_active) {
            $message = 'La sesión ya está cerrada.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['general' => $message]);
        }

        DB::transaction(function () use ($session) {
            $session->sessionKeys()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $enrolledIds = Enrollment::withoutGlobalScopes()
                ->where('classroom_id', $session->classroom_id)
                ->where('is_active', true)
                ->pluck('student_id');

            $registeredIds = Attendance::withoutGlobalScopes()
                ->where('session_id', $session->id)
                ->pluck('student_id');

            foreach ($enrolledIds->diff($registeredIds) as $studentId) {
                Attendance::create([
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                    'status'     => 'absent',
                ]);
            }

            $session->update([
                'is_active' => false,
                'ended_at'  => now()->format('H:i:s'),
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sesión cerrada.']);
        }

        return redirect()
            ->route('asistencias.docente', ['classroom' => $session->classroom_id])
            ->with('success', 'Sesión cerrada. Faltas registradas para alumnos sin asistencia.');
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'access_key' => 'required|string|size:8',
        ]);

        $accessKey  = strtoupper($request->access_key);
        $sessionKey = SessionKey::withoutGlobalScopes()
            ->with(['session.classroom'])
            ->where('access_key', $accessKey)
            ->first();

        if (!$sessionKey || !$sessionKey->isValid()) {
            $msg = 'Clave de asistencia inválida o expirada.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['access_key' => $msg]);
        }

        $session     = $sessionKey->session;
        $studentId   = auth()->user()->id;
        $classroomId = $session->classroom_id;

        if (!$session?->is_active) {
            $msg = 'La sesión no está activa.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['access_key' => $msg]);
        }

        $isEnrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$isEnrolled) {
            $msg = 'No estás inscrito en el aula de esta sesión.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : back()->withErrors(['access_key' => $msg]);
        }

        if (Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('student_id', $studentId)
            ->exists()) {
            $msg = 'Ya registraste asistencia en esta sesión.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 409)
                : back()->withErrors(['access_key' => $msg]);
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Asistencia registrada.',
                'data'    => $attendance,
            ], 201);
        }

        return redirect()
            ->route('asistencias.alumno', ['classroom' => $classroomId])
            ->with('success', '¡Asistencia registrada correctamente!');
    }
}
