<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\InvitationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
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

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'subject_name'       => 'required|string|max:100',
            'period'             => 'required|string|max:20',
            'max_capacity'       => 'required|integer|min:1|max:100',
            'min_attendance_pct' => 'required|integer|min:1|max:100',
        ], [
            'subject_name.required' => 'El nombre del aula es obligatorio.',
            'period.required'       => 'El ciclo escolar es obligatorio.',
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

                // Unicidad teacher + subject + period
                $exists = Classroom::withoutGlobalScopes()
                    ->where('teacher_id', $user->id)
                    ->where('subject_name', $request->subject_name)
                    ->where('period', $request->period)
                    ->exists();

                if ($exists) {
                    throw new \RuntimeException("Ya tienes un aula de {$request->subject_name} para el período {$request->period}.");
                }

                $classroom = Classroom::create([
                    'institution_id'     => $institution->id,
                    'teacher_id'         => $user->id,
                    'subject_name'       => $request->subject_name,
                    'period'             => $request->period,
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
            ->with('success', "Aula \"{$request->subject_name}\" creada exitosamente.");
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
