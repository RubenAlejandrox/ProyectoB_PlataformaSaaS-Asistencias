<?php

namespace App\Services;

use App\Events\TrafficLightAlert;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceProgressService
{
    public function __construct(
        private StudentNotificationService $notifications
    ) {}
    /**
     * Calcula P = (present + approved) / total × 100 y determina semáforo.
     */
    public function calculate(string $studentId, string $classroomId): array
    {
        $classroom = Classroom::withoutGlobalScopes()->findOrFail($classroomId);
        $threshold = (int) $classroom->min_attendance_pct;

        $totalSessions = Session::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->count();

        $presentCount = Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('status', 'present')
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->count();

        $approvedCount = Justification::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('status', 'approved')
            ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->count();

        $numerator = $presentCount + $approvedCount;
        $percentage = $totalSessions > 0
            ? round(($numerator / $totalSessions) * 100, 1)
            : 0.0;

        $light = $this->determineLight($percentage, $threshold);

        return [
            'student_id'      => $studentId,
            'classroom_id'    => $classroomId,
            'total_sessions'  => $totalSessions,
            'present_count'   => $presentCount,
            'approved_count'  => $approvedCount,
            'attendance_pct'  => $percentage,
            'threshold'       => $threshold,
            'light'           => $light,
        ];
    }

    public function determineLight(float $percentage, int $threshold): string
    {
        if ($percentage >= $threshold) {
            return 'green';
        }

        if ($percentage >= ($threshold - 10)) {
            return 'amber';
        }

        return 'red';
    }

    /**
     * Dispara TrafficLightAlert si el semáforo cambió de estado.
     */
    public function dispatchTrafficLightIfChanged(
        string $studentId,
        string $classroomId,
        string $previousLight
    ): ?string {
        $current = $this->calculate($studentId, $classroomId);
        $newLight = $current['light'];

        if ($newLight === $previousLight) {
            return null;
        }

        event(new TrafficLightAlert(
            studentId: $studentId,
            classroomId: $classroomId,
            light: $newLight,
            percentage: $current['attendance_pct'],
        ));

        $this->notifications->notifyTrafficLightChange(
            $studentId,
            $classroomId,
            $newLight,
            $current['attendance_pct'],
            $previousLight
        );

        return $newLight;
    }

    /**
     * Cuántas faltas más (sin justificar) puede tener y seguir en o por encima del umbral.
     */
    public function projectRemainingAbsences(array $progress): array
    {
        $total     = (int) $progress['total_sessions'];
        $credits   = (int) $progress['present_count'] + (int) $progress['approved_count'];
        $threshold = (int) $progress['threshold'];
        $pct       = (float) $progress['attendance_pct'];

        if ($total === 0) {
            return [
                'remaining' => null,
                'message'   => 'Aún no hay sesiones registradas en esta materia.',
            ];
        }

        if ($threshold <= 0) {
            return [
                'remaining' => null,
                'message'   => 'No hay umbral mínimo configurado para esta aula.',
            ];
        }

        $minTotalSessions = (int) ceil($credits / ($threshold / 100));
        $remaining        = max(0, $minTotalSessions - $total);

        if ($pct < $threshold) {
            return [
                'remaining' => 0,
                'message'   => 'Ya estás por debajo del umbral mínimo. Considera justificar faltas o asistir a las próximas sesiones.',
            ];
        }

        if ($remaining === 0) {
            return [
                'remaining' => 0,
                'message'   => 'No puedes faltar en las próximas sesiones sin bajar del umbral mínimo.',
            ];
        }

        $word = $remaining === 1 ? 'falta' : 'faltas';

        return [
            'remaining' => $remaining,
            'message'   => "Puedes tener hasta {$remaining} {$word} más (sin justificar) y mantener el umbral del {$threshold}%.",
        ];
    }

    /**
     * Calendario mensual con estado por día de sesión.
     *
     * @return array{year:int,month:int,month_label:string,weeks:array,legend:array}
     */
    public function buildMonthlyCalendar(
        string $studentId,
        string $classroomId,
        ?int $year = null,
        ?int $month = null
    ): array {
        $cursor = Carbon::create($year ?? now()->year, $month ?? now()->month, 1);
        $start  = $cursor->copy()->startOfMonth();
        $end    = $cursor->copy()->endOfMonth();

        $sessions = Session::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('session_date')
            ->get();

        $attendances = Attendance::withoutGlobalScopes()
            ->with('justification')
            ->where('student_id', $studentId)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get()
            ->keyBy('session_id');

        $dayMap = [];
        foreach ($sessions as $session) {
            $key        = $session->session_date->format('Y-m-d');
            $attendance = $attendances->get($session->id);
            $dayMap[$key] = $this->calendarDayStatus($attendance);
        }

        $weeks      = [];
        $dayPointer = $start->copy()->startOfWeek(Carbon::MONDAY);
        $lastWeek   = $end->copy()->endOfWeek(Carbon::SUNDAY);

        while ($dayPointer->lte($lastWeek)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $dayPointer->format('Y-m-d');
                $week[]  = [
                    'date'          => $dateKey,
                    'day'           => (int) $dayPointer->format('j'),
                    'in_month'      => $dayPointer->month === $cursor->month,
                    'status'        => $dayMap[$dateKey] ?? 'none',
                    'has_session'   => isset($dayMap[$dateKey]),
                ];
                $dayPointer->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'year'        => $cursor->year,
            'month'       => $cursor->month,
            'month_label' => $cursor->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
            'weeks'       => $weeks,
            'legend'      => [
                'present'  => 'Asistencia',
                'absent'   => 'Falta',
                'justified'=> 'Justificado',
                'pending'  => 'Justificante en revisión',
            ],
        ];
    }

    private function calendarDayStatus(?Attendance $attendance): string
    {
        if (! $attendance) {
            return 'none';
        }

        if ($attendance->status === 'present') {
            return 'present';
        }

        $just = $attendance->justification;
        if ($just?->status === 'approved') {
            return 'justified';
        }
        if ($just?->status === 'pending') {
            return 'pending';
        }

        return 'absent';
    }

    public function justificationsForClassroom(string $studentId, string $classroomId): Collection
    {
        return Justification::withoutGlobalScopes()
            ->with(['attendance.session', 'reviewer:id,first_name,last_name'])
            ->where('student_id', $studentId)
            ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->get();
    }

    public function calculateForStudent(User $student): array
    {
        $enrollments = $student->enrollments()
            ->where('is_active', true)
            ->with('classroom')
            ->get();

        return $enrollments->map(function ($enrollment) use ($student) {
            $progress = $this->calculate($student->id, $enrollment->classroom_id);

            return array_merge($progress, [
                'classroom' => $enrollment->classroom,
            ]);
        })->values()->all();
    }
}
