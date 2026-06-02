<?php

/**
 * @descripcion  Controlador HTTP del módulo Dashboard: expone acciones web/API del dominio.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirige al panel correspondiente según el rol del usuario autenticado.
     *
     * @param Request $request Solicitud HTTP (sin parámetros de negocio)
     * @return \Illuminate\View\View Vista del dashboard por rol
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return match(true) {
            $user->hasRole('Administrator') => $this->adminDashboard($user),
            $user->hasRole('Teacher')       => $this->teacherDashboard($user),
            $user->hasRole('Student')       => $this->studentDashboard($user),
            default => abort(403, 'Role not recognized.')
        };
    }

    // ── Administrator ─────────────────────────────────────────────────────────
    private function adminDashboard($user)
    {
        $institution = $user->institution;
        $institutionId = $institution?->id;

        $activeSubscription = $institution
            ? $institution->subscriptions()
                ->where('status', 'active')
                ->where('end_date', '>=', now()->toDateString())
                ->with('plan')
                ->latest('end_date')
                ->first()
            : null;

        $activeClassrooms = $institutionId
            ? Classroom::withoutGlobalScopes()
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->count()
            : 0;

        $enrolledStudents = $institutionId
            ? Enrollment::withoutGlobalScopes()
                ->where('is_active', true)
                ->whereHas('classroom', fn ($q) => $q->where('institution_id', $institutionId))
                ->distinct('student_id')
                ->count('student_id')
            : 0;

        $stats = [
            'institution_name'       => $institution?->name,
            'total_classrooms'       => $activeClassrooms,
            'total_teachers'         => $institutionId
                ? User::role('Teacher')->where('institution_id', $institutionId)->where('is_active', true)->count()
                : 0,
            'total_students'         => $enrolledStudents,
            'pending_justifications' => $institutionId
                ? Justification::withoutGlobalScopes()
                    ->where('status', 'pending')
                    ->whereHas('attendance.session.classroom', fn ($q) => $q->where('institution_id', $institutionId))
                    ->count()
                : 0,
            'sessions_this_month'    => $institutionId
                ? Session::withoutGlobalScopes()
                    ->whereHas('classroom', fn ($q) => $q->where('institution_id', $institutionId))
                    ->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count()
                : 0,
            'active_subscription'    => $activeSubscription,
            'plan_used_classrooms'   => $activeClassrooms,
            'plan_used_students'     => $enrolledStudents,
            'plan_max_classrooms'    => $activeSubscription?->plan?->max_classrooms ?? 0,
            'plan_max_students'      => $activeSubscription?->plan?->max_students ?? 0,
        ];

        $recentActivity = $institutionId
            ? AuditLog::withoutGlobalScopes()
                ->with('user:id,first_name,last_name,email,institution_id')
                ->whereHas('user', fn ($q) => $q->where('institution_id', $institutionId))
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
            : collect();

        return view('dashboard.admin', compact('user', 'stats', 'recentActivity'));
    }

    // ── Teacher ───────────────────────────────────────────────────────────────
    private function teacherDashboard($user)
    {
        $classrooms = Classroom::where('teacher_id', $user->id)
                        ->where('is_active', true)
                        ->withCount('enrollments')
                        ->get();

        $stats = [
            'total_classrooms'       => $classrooms->count(),
            'total_students'         => $classrooms->sum('enrollments_count'),
            'pending_justifications' => Justification::whereHas('attendance.session',
                fn($q) => $q->whereIn('classroom_id', $classrooms->pluck('id'))
            )->where('status', 'pending')->count(),
            'at_risk_students' => Attendance::whereHas('session',
                fn($q) => $q->whereIn('classroom_id', $classrooms->pluck('id'))
            )->where('status', 'absent')->count(),
        ];

        return view('dashboard.teacher', compact('user', 'classrooms', 'stats'));
    }

    // ── Student ───────────────────────────────────────────────────────────────
    private function studentDashboard($user)
    {
        $enrollments = $user->enrollments()
                        ->where('is_active', true)
                        ->with('classroom')
                        ->get();

        $progress = $enrollments->map(function ($enrollment) use ($user) {
            $classroom   = $enrollment->classroom;
            $total       = $classroom->sessions()->count();
            $present     = Attendance::where('student_id', $user->id)
                            ->whereHas('session', fn($q) => $q->where('classroom_id', $classroom->id))
                            ->where('status', 'present')->count();
            $approved    = Justification::where('student_id', $user->id)
                            ->whereHas('attendance', fn($q) => $q->where('student_id', $user->id))
                            ->where('status', 'approved')->count();
            $percentage  = $total > 0 ? round(($present + $approved) / $total * 100, 1) : 0;
            $threshold   = $classroom->min_attendance_pct;
            $light       = $percentage >= $threshold ? 'green'
                         : ($percentage >= ($threshold - 10) ? 'amber' : 'red');

            return [
                'classroom'   => $classroom,
                'percentage'  => $percentage,
                'light'       => $light,
                'total'       => $total,
                'present'     => $present,
                'threshold'   => $threshold,
            ];
        });

        $stats = [
            'total_subjects'         => $enrollments->count(),
            'avg_attendance'         => $progress->avg('percentage') ?? 0,
            'pending_justifications' => Justification::where('student_id', $user->id)
                                            ->where('status', 'pending')->count(),
            'at_risk'                => $progress->where('light', 'red')->count(),
        ];

        return view('dashboard.student', compact('user', 'progress', 'stats'));
    }
}