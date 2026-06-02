<?php

/**
 * @descripcion  Controlador web de asistencias (docente/alumno): sesiones, claves, registro y roster en tiempo real.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.1.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios      2026-06-02 - Optimización roster/polling y marcado masivo de faltas
 *               2026-06-02 - Incorporación de cabecera de prólogo
 */


declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\AttendanceRegistered;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Session;
use App\Models\SessionKey;
use App\Models\User;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceWebController extends Controller
{
    /** Duraciones de la ventana de registro (segundos). */
    private const KEY_DURATION_SECONDS = [45, 60, 180];

    /**
     * @param AttendanceProgressService $progressService Servicio de progreso y roster de asistencia
     */
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    /**
     * Panel web del docente: aulas, sesión del día, alumnos y estadísticas.
     *
     * @param Request $request Parámetro opcional classroom (UUID del aula seleccionada)
     * @return View Vista asistencias.docente
     */
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

        $todaySession  = null;
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

            $todaySession = Session::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->whereDate('session_date', today())
                ->with(['sessionKeys' => fn ($q) => $q->where('is_active', true)->latest()])
                ->orderByDesc('created_at')
                ->first();

            $activeSession = ($todaySession && $todaySession->is_active) ? $todaySession : null;
            $activeKey     = $activeSession?->sessionKeys->first();

            if ($todaySession) {
                $stats['present_today'] = Attendance::withoutGlobalScopes()
                    ->where('session_id', $todaySession->id)
                    ->where('status', 'present')
                    ->count();
            }

            $enrollments = Enrollment::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->where('is_active', true)
                ->with('student:id,first_name,last_name,email')
                ->get();

            $todayAttendances = $todaySession
                ? Attendance::withoutGlobalScopes()
                    ->with('justification:id,attendance_id,status')
                    ->where('session_id', $todaySession->id)
                    ->get()
                    ->keyBy('student_id')
                : collect();

            $progressMap = $this->progressService->calculateBulk(
                $classroom->id,
                $enrollments->pluck('student.id')
            );

            $students = $enrollments->map(function ($enrollment) use ($classroom, $todayAttendances, $progressMap, &$stats) {
                $student  = $enrollment->student;
                $progress = $progressMap[$student->id]
                    ?? $this->progressService->calculate($student->id, $classroom->id);

                if (in_array($progress['light'], ['amber', 'red'], true)) {
                    $stats['at_risk']++;
                }

                $today = $todayAttendances->get($student->id);

                $justStatus = $today?->justification?->status;

                return [
                    'id'                       => $student->id,
                    'name'                     => trim($student->first_name.' '.$student->last_name),
                    'initials'                 => strtoupper(substr($student->first_name, 0, 1).substr($student->last_name, 0, 1)),
                    'pct'                      => $progress['attendance_pct'],
                    'light'                    => $progress['light'],
                    'today_status'             => $today?->status,
                    'today_time'               => $today?->created_at?->format('H:i'),
                    'attendance_id'            => $today?->id,
                    'has_approved_justification' => $justStatus === 'approved',
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
            'todaySession'    => $todaySession ?? null,
            'activeSession'   => $activeSession,
            'activeKey'       => $activeKey,
            'students'        => $students,
            'stats'           => $stats,
            'sessionsHistory' => $sessionsHistory,
            'keyDurations'    => self::keyDurationOptions(),
        ]);
    }

    /**
     * Opciones de duración disponibles para la ventana de registro de asistencia.
     *
     * @return array<int, array{seconds: int, label: string}> Lista de segundos y etiqueta legible
     */
    public static function keyDurationOptions(): array
    {
        return [
            ['seconds' => 45, 'label' => '45 seg'],
            ['seconds' => 60, 'label' => '1 min'],
            ['seconds' => 180, 'label' => '3 min'],
        ];
    }

    /**
     * Panel web del alumno: materias inscritas, historial y faltas sin justificar.
     *
     * @param Request $request Parámetro opcional classroom (UUID del aula seleccionada)
     * @return View Vista asistencias.alumno
     */
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

    /**
     * Abre una sesión de asistencia para el día actual en el aula del docente.
     *
     * @param Request $request classroom_id (UUID del aula)
     * @return RedirectResponse Redirección al panel docente con mensaje flash
     */
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

    /**
     * Genera una clave temporal de asistencia para la sesión activa.
     *
     * @param Request $request duration_seconds (45, 60 o 180)
     * @param Session $session Sesión de clase del docente
     * @return JsonResponse|RedirectResponse Clave y expiración (JSON) o redirección con mensaje
     */
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
            'duration_seconds' => 'required|integer|in:'.implode(',', self::KEY_DURATION_SECONDS),
        ]);

        $duration = (int) $request->duration_seconds;

        $session->sessionKeys()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        do {
            $accessKey = strtoupper(Str::random(8));
        } while (SessionKey::withoutGlobalScopes()->where('access_key', $accessKey)->exists());

        $sessionKey = SessionKey::create([
            'session_id' => $session->id,
            'access_key' => $accessKey,
            'expires_at' => now()->addSeconds($duration),
            'is_active'  => true,
        ]);

        $payload = [
            'access_key'        => $sessionKey->access_key,
            'expires_at'        => $sessionKey->expires_at->toIso8601String(),
            'duration_seconds'  => $duration,
        ];

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Clave generada.', 'data' => $payload], 201);
        }

        return redirect()
            ->route('asistencias.docente', ['classroom' => $session->classroom_id])
            ->with('success', 'Clave generada: '.$sessionKey->access_key);
    }

    /**
     * Detiene la clave activa, marca faltas a no registrados y devuelve el roster actualizado.
     *
     * @param Request $request Solicitud HTTP (acepta JSON o formulario web)
     * @param Session $session Sesión de clase del docente
     * @return JsonResponse|RedirectResponse Roster y mensaje, o error 403/422
     */
    public function stopKey(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403);
        }

        if (! $session->is_active) {
            $message = 'La sesión ya está cerrada.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['general' => $message]);
        }

        $hadActiveKey = $session->sessionKeys()->where('is_active', true)->exists();

        $session->sessionKeys()
            ->where('is_active', true)
            ->update([
                'is_active'  => false,
                'expires_at' => now(),
            ]);

        if (! $hadActiveKey) {
            $message = 'No hay una clave activa para detener.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['general' => $message]);
        }

        $marked = $this->markAbsentForUnregisteredStudents($session);

        $message = $marked['count'] > 0
            ? "Clave detenida. Se registraron {$marked['count']} falta(s); los alumnos pueden enviar justificante (72 h)."
            : 'Clave de asistencia detenida. Los alumnos ya no pueden registrar con esta clave.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data'    => $this->sessionRosterPayload($session, $marked['updates'], includeProgress: true),
            ]);
        }

        return redirect()
            ->route('asistencias.docente', ['classroom' => $session->classroom_id])
            ->with('success', $message);
    }

    /**
     * Cierra la sesión, desactiva claves y registra faltas para alumnos sin asistencia.
     *
     * @param Request $request Solicitud HTTP (acepta JSON o formulario web)
     * @param Session $session Sesión de clase del docente
     * @return JsonResponse|RedirectResponse Confirmación con roster (JSON) o redirección
     */
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
                ->update([
                    'is_active'  => false,
                    'expires_at' => now(),
                ]);

            $this->markAbsentForUnregisteredStudents($session);

            $session->update([
                'is_active' => false,
                'ended_at'  => now()->format('H:i:s'),
            ]);
        });

        $session->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesión cerrada. Faltas registradas para alumnos sin asistencia.',
                'data'    => $this->sessionRosterPayload($session, includeProgress: true),
            ]);
        }

        return redirect()
            ->route('asistencias.docente', ['classroom' => $session->classroom_id])
            ->with('success', 'Sesión cerrada. Faltas registradas para alumnos sin asistencia.');
    }

    /**
     * Devuelve el roster en tiempo real de la sesión (presentes, inscritos, clave activa).
     *
     * @param Session $session Sesión de clase del docente
     * @return JsonResponse Payload con alumnos y contadores
     */
    public function sessionRoster(Session $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403);
        }

        return response()->json([
            'data' => $this->sessionRosterPayload($session),
        ]);
    }

    /**
     * Actualiza manualmente la asistencia de un alumno en la sesión de hoy.
     *
     * @param Request $request status: present, absent o pending
     * @param Session $session Sesión de clase del docente
     * @param User $student Alumno inscrito en el aula
     * @return JsonResponse Estado actualizado o error 403/422
     * @throws \RuntimeException Si se intenta dejar pendiente con justificante aprobado
     */
    public function updateStudentAttendance(Request $request, Session $session, User $student): JsonResponse
    {
        $session->loadMissing('classroom');

        if ($session->classroom->teacher_id !== auth()->user()->id) {
            abort(403);
        }

        if (! $session->session_date->isToday()) {
            return response()->json([
                'message' => 'Solo puedes modificar asistencias de la sesión de hoy.',
            ], 422);
        }

        $request->validate([
            'status' => 'required|in:present,absent,pending',
        ]);

        $enrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $session->classroom_id)
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->exists();

        if (! $enrolled) {
            return response()->json([
                'message' => 'El alumno no está inscrito en esta aula.',
            ], 403);
        }

        $classroomId   = $session->classroom_id;
        $previousLight = $this->progressService->calculate($student->id, $classroomId)['light'];
        $targetStatus  = $request->string('status')->toString();
        $wasPresent    = false;

        try {
            DB::transaction(function () use ($session, $student, $targetStatus, &$wasPresent) {
                $attendance = Attendance::withoutGlobalScopes()
                    ->where('session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->lockForUpdate()
                    ->first();

                if ($targetStatus === 'pending') {
                    if (! $attendance) {
                        return;
                    }

                    if ($attendance->justification()->where('status', 'approved')->exists()) {
                        throw new \RuntimeException(
                            'No se puede dejar pendiente: el justificante ya fue aprobado.'
                        );
                    }

                    $attendance->justification()?->delete();
                    $attendance->delete();

                    return;
                }

                if ($attendance) {
                    if ($attendance->justification()->where('status', 'pending')->exists() && $targetStatus === 'present') {
                        $attendance->justification()->delete();
                    }

                    $previousStatus = $attendance->status;
                    $attendance->update(['status' => $targetStatus]);

                    if ($targetStatus === 'absent' && $previousStatus === 'present') {
                        Attendance::withoutGlobalScopes()
                            ->where('id', $attendance->id)
                            ->update([
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    $wasPresent = $targetStatus === 'present';
                } else {
                    $attendance = Attendance::create([
                        'session_id' => $session->id,
                        'student_id' => $student->id,
                        'status'     => $targetStatus,
                    ]);
                    $wasPresent = $targetStatus === 'present';
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $attendance = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($wasPresent && $attendance) {
            $progress = $this->progressService->calculate($student->id, $classroomId);
            event(new AttendanceRegistered($attendance, $classroomId, $progress));
        }

        $this->progressService->dispatchTrafficLightIfChanged($student->id, $classroomId, $previousLight);

        $presentCount = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('status', 'present')
            ->count();

        $labels = [
            'present' => 'Asistencia registrada manualmente.',
            'absent'  => 'Falta registrada. El alumno puede enviar justificante (72 h).',
            'pending' => 'Estado restablecido a pendiente.',
        ];

        return response()->json([
            'message' => $labels[$targetStatus] ?? 'Estado actualizado.',
            'data'    => [
                'student_id'    => $student->id,
                'status'        => $targetStatus === 'pending' ? 'pending' : $attendance?->status,
                'registered_at' => $attendance?->created_at?->format('H:i'),
                'present_count' => $presentCount,
            ],
        ]);
    }

    /**
     * Registra asistencia del alumno autenticado con clave (web o JSON según Accept).
     *
     * @param Request $request Clave de acceso de 8 caracteres (access_key)
     * @return RedirectResponse|JsonResponse Confirmación o errores de validación
     */
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

        $progressAfter = $this->progressService->calculate($studentId, $classroomId);
        event(new AttendanceRegistered($attendance, $classroomId, $progressAfter));
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

    /**
     * Marca falta a inscritos sin registro en la sesión (habilita justificante 72 h).
     *
     * @return array{count: int, updates: list<array{student_id: string, status: string, registered_at: ?string}>}
     */
    private function markAbsentForUnregisteredStudents(Session $session): array
    {
        $session->loadMissing('classroom');
        $classroomId = $session->classroom_id;

        $enrolledIds = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->pluck('student_id');

        $existingAttendances = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->get()
            ->keyBy('student_id');

        $missingIds = $enrolledIds->filter(
            fn ($studentId) => ! $existingAttendances->has($studentId)
        )->values();

        if ($missingIds->isEmpty()) {
            return ['count' => 0, 'updates' => []];
        }

        $now     = now();
        $updates = [];

        DB::transaction(function () use ($session, $missingIds, $now, &$updates) {
            $rows = $missingIds->map(fn ($studentId) => [
                'id'         => (string) Str::uuid(),
                'session_id' => $session->id,
                'student_id' => $studentId,
                'status'     => 'absent',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            Attendance::insert($rows);

            foreach ($missingIds as $studentId) {
                $updates[] = [
                    'student_id'    => $studentId,
                    'status'        => 'absent',
                    'registered_at' => $now->format('H:i'),
                ];
            }
        });

        $newAttendances = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->whereIn('student_id', $missingIds)
            ->get()
            ->keyBy('student_id');

        $progressMap = $this->progressService->calculateBulk($classroomId, $missingIds);

        foreach ($missingIds as $studentId) {
            $attendance = $newAttendances->get($studentId);
            if (! $attendance) {
                continue;
            }

            event(new AttendanceRegistered(
                $attendance,
                $classroomId,
                $progressMap[$studentId] ?? null
            ));
        }

        return ['count' => count($updates), 'updates' => $updates];
    }

    /**
     * @param  list<array{student_id: string, status: string, registered_at: ?string}>|null  $extraUpdates
     * @return array{
     *     present_count: int,
     *     enrolled_count: int,
     *     session_active: bool,
     *     has_active_key: bool,
     *     students: list<array<string, mixed>>,
     *     updates: list<array<string, string|null>>
     * }
     */
    private function sessionRosterPayload(
        Session $session,
        ?array $extraUpdates = null,
        bool $includeProgress = false
    ): array {
        $session->loadMissing('classroom');
        $classroomId = $session->classroom_id;
        $hasActiveKey = $session->sessionKeys()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->exists();

        $enrollments = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->with('student:id,first_name,last_name')
            ->get();

        $attendances = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->get()
            ->keyBy('student_id');

        $studentIds = $enrollments->pluck('student.id');
        $progressMap = $includeProgress
            ? $this->progressService->calculateBulk($classroomId, $studentIds)
            : [];

        $students = $enrollments->map(function ($enrollment) use ($attendances, $progressMap) {
            $student  = $enrollment->student;
            $today    = $attendances->get($student->id);
            $progress = $progressMap[$student->id] ?? null;

            return [
                'student_id'    => $student->id,
                'name'          => trim($student->first_name.' '.$student->last_name),
                'initials'      => strtoupper(substr($student->first_name, 0, 1).substr($student->last_name, 0, 1)),
                'today_status'  => $today?->status,
                'today_time'    => $today?->created_at?->format('H:i'),
                'pct'           => $progress['attendance_pct'] ?? null,
                'light'         => $progress['light'] ?? null,
            ];
        })->values()->all();

        $presentCount = $attendances->where('status', 'present')->count();

        return [
            'present_count'  => $presentCount,
            'enrolled_count' => $enrollments->count(),
            'session_active' => (bool) $session->is_active,
            'has_active_key' => $hasActiveKey,
            'students'       => $students,
            'updates'        => $extraUpdates ?? [],
        ];
    }
}
