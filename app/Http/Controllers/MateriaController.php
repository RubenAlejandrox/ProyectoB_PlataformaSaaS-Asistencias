<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Services\AttendanceProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriaController extends Controller
{
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    public function show(Request $request, Classroom $classroom): View
    {
        $user = $request->user();

        if (! $user->hasRole('Student')) {
            abort(403);
        }

        $enrolled = Enrollment::withoutGlobalScopes()
            ->where('student_id', $user->id)
            ->where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->exists();

        if (! $enrolled) {
            abort(403, 'No estás inscrito en esta materia.');
        }

        $classroom->load('teacher:id,first_name,last_name');

        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $progress       = $this->progressService->calculate($user->id, $classroom->id);
        $projection     = $this->progressService->projectRemainingAbsences($progress);
        $calendar       = $this->progressService->buildMonthlyCalendar($user->id, $classroom->id, $year, $month);
        $justifications = $this->progressService->justificationsForClassroom($user->id, $classroom->id);

        $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
        $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();

        return view('materias.show', [
            'classroom'      => $classroom,
            'progress'       => $progress,
            'projection'     => $projection,
            'calendar'       => $calendar,
            'justifications' => $justifications,
            'prevMonth'      => ['year' => $prev->year, 'month' => $prev->month],
            'nextMonth'      => ['year' => $next->year, 'month' => $next->month],
        ]);
    }
}
