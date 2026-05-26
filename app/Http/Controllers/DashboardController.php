<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

        $stats = [
            'total_classrooms'    => Classroom::count(),
            'total_teachers'      => User::role('Teacher')->count(),
            'total_students'      => User::role('Student')->count(),
            'pending_justifications' => Justification::where('status', 'pending')->count(),
            'active_subscription' => $institution->subscriptions()
                                        ->where('status', 'active')
                                        ->with('plan')
                                        ->latest()
                                        ->first(),
        ];

        return view('dashboard.admin', compact('user', 'stats'));
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