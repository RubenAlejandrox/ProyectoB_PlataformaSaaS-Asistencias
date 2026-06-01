<?php

namespace App\Http\Controllers;

use App\Exports\ClassroomStudentsExport;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\InvitationCode;
use App\Models\Session;
use App\Services\AttendanceProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassroomController extends Controller
{
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    // ── index ─────────────────────────────────────────────────────────────────
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('Student')) {
            return $this->indexForStudent($user);
        }

        $institution = $user->institution;

        // Docente ve solo sus aulas — Admin ve todas de la institución
        $query = Classroom::with(['teacher', 'invitationCodes'])
            ->withCount(['enrollments' => fn ($q) => $q->where('is_active', true)])
            ->withCount('sessions');

        if ($user->hasRole('Teacher')) {
            $query->where('teacher_id', $user->id);
        }

        $classrooms = $query->orderBy('is_active', 'desc')
            ->orderBy('subject_name')
            ->orderBy('grupo')
            ->get();

        // Límite del plan
        $activePlan      = $institution?->activePlan();
        $totalClassrooms = Classroom::withoutGlobalScopes()
            ->where('institution_id', $institution?->id)
            ->where('is_active', true)
            ->count();

        $stats = [
            'total'          => $classrooms->count(),
            'active'         => $classrooms->where('is_active', true)->count(),
            'closed'         => $classrooms->where('is_active', false)->count(),
            'total_students' => $classrooms->sum('enrollments_count'),
            'plan_limit'     => $activePlan?->max_classrooms ?? 0,
            'plan_used'      => $totalClassrooms,
            'can_create'     => $activePlan && $totalClassrooms < $activePlan->max_classrooms,
        ];

        if (request()->expectsJson()) {
            return response()->json($classrooms);
        }

        return view('aulas.index', compact('classrooms', 'stats', 'activePlan'));
    }

    private function indexForStudent($user)
    {
        $enrolledIds = Enrollment::withoutGlobalScopes()
            ->where('student_id', $user->id)
            ->where('is_active', true)
            ->pluck('classroom_id');

        $classrooms = Classroom::with(['teacher'])
            ->withCount(['enrollments' => fn ($q) => $q->where('is_active', true)])
            ->withCount('sessions')
            ->whereIn('id', $enrolledIds)
            ->orderBy('is_active', 'desc')
            ->orderBy('subject_name')
            ->orderBy('grupo')
            ->get();

        $stats = [
            'total'          => $classrooms->count(),
            'active'         => $classrooms->where('is_active', true)->count(),
            'closed'         => $classrooms->where('is_active', false)->count(),
            'total_students' => 0,
            'plan_limit'     => 0,
            'plan_used'      => 0,
            'can_create'     => false,
        ];

        $activePlan            = null;
        $showEnrollmentModal   = $classrooms->isEmpty();
        $enrollmentRedirectUrl = route('aulas.index');

        return view('aulas.index', compact(
            'classrooms',
            'stats',
            'activePlan',
            'showEnrollmentModal',
            'enrollmentRedirectUrl'
        ));
    }

    // ── create ────────────────────────────────────────────────────────────────
    public function create()
    {
        if (auth()->user()->hasRole('Student')) {
            abort(403);
        }

        $user        = auth()->user();
        $institution = $user->institution;
        $activePlan  = $institution?->activePlan();

        // Verificar límite del plan antes de mostrar el form
        $totalClassrooms = Classroom::withoutGlobalScopes()
            ->where('institution_id', $institution?->id)
            ->where('is_active', true)
            ->count();

        if (!$activePlan || $totalClassrooms >= $activePlan->max_classrooms) {
            return redirect()->route('aulas.index')
                ->withErrors(['general' => "Has alcanzado el límite de {$activePlan?->max_classrooms} aulas de tu plan. Actualiza tu membresía para crear más."]);
        }

        return view('aulas.create', compact('activePlan', 'totalClassrooms'));
    }

    // ── show — detalle de aula (docente / administrador) ─────────────────────
    public function show(Classroom $classroom): View
    {
        $this->authorizeClassroomView($classroom);

        $classroom->load([
            'teacher:id,first_name,last_name,email',
            'invitationCodes' => fn ($q) => $q->orderByDesc('created_at')->limit(5),
        ]);

        $students = $this->progressService->rosterForClassroom($classroom->id);

        $sessions = Session::withoutGlobalScopes()
            ->where('classroom_id', $classroom->id)
            ->withCount([
                'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendances as absent_count'  => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get();

        $activeCode = $classroom->invitationCodes
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        $enrolledCount = $students->count();
        $atRiskCount   = $students->whereIn('light', ['amber', 'red'])->count();

        $stats = [
            'enrolled'         => $enrolledCount,
            'capacity'         => $classroom->max_capacity,
            'sessions'         => $sessions->count(),
            'at_risk'          => $atRiskCount,
            'min_attendance'   => $classroom->min_attendance_pct,
            'avg_attendance'   => $enrolledCount > 0
                ? round($students->avg('attendance_pct'), 1)
                : 0.0,
        ];

        return view('aulas.show', compact(
            'classroom',
            'students',
            'sessions',
            'activeCode',
            'stats',
        ));
    }

    public function exportStudents(Classroom $classroom): BinaryFileResponse
    {
        $this->authorizeClassroomView($classroom);

        $students = $this->progressService->rosterForClassroom($classroom->id);

        $rows = $students->map(fn ($s) => [
            $s['name'],
            $s['email'],
            $s['attendance_pct'],
            $s['light_label'],
            $s['present_count'],
            $s['approved_count'],
            $s['total_sessions'],
        ])->all();

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $classroom->subject_name);
        $filename = "alumnos_{$safeName}_{$classroom->grupo}_{$classroom->period}.xlsx";

        return Excel::download(new ClassroomStudentsExport($rows), $filename);
    }

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'subject_name'       => 'required|string|max:100',
            'period'             => 'required|string|max:20',
            'grupo'              => ['required', 'regex:/^\d{6}$/'],
            'max_capacity'       => 'required|integer|min:1|max:100',
            'min_attendance_pct' => 'required|integer|min:1|max:100',
        ], [
            'subject_name.required' => 'El nombre del aula es obligatorio.',
            'period.required'       => 'El ciclo escolar es obligatorio.',
            'grupo.required'        => 'El grupo es obligatorio.',
            'grupo.regex'           => 'El grupo debe ser numérico de exactamente 6 dígitos (ej. 189900).',
            'max_capacity.required' => 'La capacidad máxima es obligatoria.',
        ]);

        $user        = auth()->user();
        $institution = $user->institution;
        $activePlan  = $institution?->activePlan();

        try {
            // ── Bloqueo transaccional — race conditions del último cupo ───────
            DB::transaction(function () use ($request, $user, $institution, $activePlan) {

                // PostgreSQL no permite FOR UPDATE con COUNT(*); bloqueamos filas y contamos en PHP
                $totalClassrooms = Classroom::withoutGlobalScopes()
                    ->where('institution_id', $institution?->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->pluck('id')
                    ->count();

                if (!$activePlan || $totalClassrooms >= $activePlan->max_classrooms) {
                    throw new \RuntimeException('Límite de aulas alcanzado. Actualiza tu plan.');
                }

                // Unicidad: misma materia + período + grupo (permite dos Historia con grupos distintos)
                $exists = Classroom::withoutGlobalScopes()
                    ->where('teacher_id', $user->id)
                    ->where('subject_name', $request->subject_name)
                    ->where('period', $request->period)
                    ->where('grupo', $request->grupo)
                    ->exists();

                if ($exists) {
                    throw new \RuntimeException(
                        "Ya tienes un aula de {$request->subject_name} (grupo {$request->grupo}) para el período {$request->period}."
                    );
                }

                $classroom = Classroom::create([
                    'institution_id'     => $institution->id,
                    'teacher_id'         => $user->id,
                    'subject_name'       => $request->subject_name,
                    'period'             => $request->period,
                    'grupo'              => $request->grupo,
                    'max_capacity'       => $request->max_capacity,
                    'min_attendance_pct' => $request->min_attendance_pct,
                    'is_active'          => true,
                ]);

                // Generar código de invitación automáticamente al crear
                $this->generateInvitationCode($classroom);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('aulas.index')
            ->with('success', "Aula \"{$request->subject_name}\" (grupo {$request->grupo}) creada exitosamente.");
    }

    // ── generateCode — regenerar código de invitación ────────────────────────
    public function generateCode(Classroom $classroom)
    {
        // Solo el docente dueño puede regenerar
        if (auth()->id() !== $classroom->teacher_id) {
            abort(403);
        }

        // Invalidar códigos activos anteriores al regenerar
        $classroom->invitationCodes()
            ->where('expires_at', '>', now())
            ->update([
                'expires_at' => now(),
                'is_used'    => true,
            ]);

        $code = $this->generateInvitationCode($classroom);

        return back()->with('invitation_code', [
            'code'       => $code->code,
            'expires_at' => $code->expires_at->format('d/m/Y H:i'),
            'classroom'  => $classroom->subject_name,
        ]);
    }

    // ── toggleStatus ──────────────────────────────────────────────────────────
    public function toggleStatus(Classroom $classroom)
    {
        if (auth()->id() !== $classroom->teacher_id && !auth()->user()->hasRole('Administrator')) {
            abort(403);
        }

        $classroom->update(['is_active' => !$classroom->is_active]);

        $msg = $classroom->is_active
            ? "Aula \"{$classroom->subject_name}\" reactivada."
            : "Aula \"{$classroom->subject_name}\" cerrada.";

        return back()->with('success', $msg);
    }

    private function authorizeClassroomView(Classroom $classroom): void
    {
        $user = auth()->user();

        if ($user->hasRole('Student')) {
            abort(403);
        }

        if ($user->hasRole('Teacher') && (string) $classroom->teacher_id !== (string) $user->id) {
            abort(403);
        }

        if ($user->hasRole('Administrator') && (string) $classroom->institution_id !== (string) $user->institution_id) {
            abort(403);
        }
    }

    // ── Helper: generar código único ──────────────────────────────────────────
    private function generateInvitationCode(Classroom $classroom): InvitationCode
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (InvitationCode::withoutGlobalScopes()->where('code', $code)->exists());

        return InvitationCode::create([
            'classroom_id' => $classroom->id,
            'code'         => $code,
            'expires_at'   => now()->addHours(48),
            'is_used'      => false,
        ]);
    }
}
