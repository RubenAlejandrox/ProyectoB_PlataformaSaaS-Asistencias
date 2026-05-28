<?php

namespace App\Services;

use App\Events\TrafficLightAlert;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;

class AttendanceProgressService
{
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

        return $newLight;
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
