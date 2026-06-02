<?php

/**
 * @descripcion  Cálculo de porcentaje de asistencia, semáforo y proyección; incluye cálculo bulk para roster.
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
 * @cambios      2026-06-02 - Método calculateBulk para evitar N+1 en roster
 *               2026-06-02 - Incorporación de cabecera de prólogo
 */


declare(strict_types=1);

namespace App\Services;

use App\Events\TrafficLightAlert;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceProgressService
{
    /**
     * Inyecta el servicio de notificaciones para cambios de semáforo.
     *
     * @param StudentNotificationService $notifications Servicio de notificaciones al alumno
     * @return void
     */
    public function __construct(
        private StudentNotificationService $notifications
    ) {}

    /**
     * Calcula P = (presentes + justificados aprobados) / total × 100 y determina el semáforo.
     *
     * @param string $studentId   UUID del alumno
     * @param string $classroomId UUID del aula
     * @return array<string, mixed> Progreso con total_sessions, present_count, approved_count, attendance_pct, threshold y light
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el aula no existe
     */
    public function calculate(string $studentId, string $classroomId): array
    {
        $bulk = $this->calculateBulk($classroomId, [$studentId]);

        return $bulk[$studentId] ?? $this->emptyProgress($studentId, $classroomId);
    }

    /**
     * Calcula el progreso de varios alumnos en pocas consultas (evita N+1 en roster y polling).
     *
     * @param string $classroomId UUID del aula
     * @param array<int, string>|Collection<int, string> $studentIds Lista de UUID de alumnos
     * @return array<string, array<string, mixed>> Mapa student_id => progreso (misma estructura que calculate)
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el aula no existe
     */
    public function calculateBulk(string $classroomId, array|Collection $studentIds): array
    {
        $studentIds = collect($studentIds)->filter()->unique()->values();

        if ($studentIds->isEmpty()) {
            return [];
        }

        $classroom = Classroom::withoutGlobalScopes()->findOrFail($classroomId);
        $threshold = (int) $classroom->min_attendance_pct;

        $totalSessions = Session::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->count();

        $presentCounts = Attendance::withoutGlobalScopes()
            ->join('class_sessions', 'attendances.session_id', '=', 'class_sessions.id')
            ->where('class_sessions.classroom_id', $classroomId)
            ->where('attendances.status', 'present')
            ->whereIn('attendances.student_id', $studentIds)
            ->groupBy('attendances.student_id')
            ->selectRaw('attendances.student_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'student_id');

        $approvedCounts = Justification::withoutGlobalScopes()
            ->join('attendances', 'justifications.attendance_id', '=', 'attendances.id')
            ->join('class_sessions', 'attendances.session_id', '=', 'class_sessions.id')
            ->where('class_sessions.classroom_id', $classroomId)
            ->where('justifications.status', 'approved')
            ->whereIn('justifications.student_id', $studentIds)
            ->groupBy('justifications.student_id')
            ->selectRaw('justifications.student_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'student_id');

        $result = [];

        foreach ($studentIds as $studentId) {
            $presentCount  = (int) ($presentCounts[$studentId] ?? 0);
            $approvedCount = (int) ($approvedCounts[$studentId] ?? 0);
            $numerator     = $presentCount + $approvedCount;
            $percentage    = $totalSessions > 0
                ? round(($numerator / $totalSessions) * 100, 1)
                : 0.0;

            $result[$studentId] = [
                'student_id'      => $studentId,
                'classroom_id'    => $classroomId,
                'total_sessions'  => $totalSessions,
                'present_count'   => $presentCount,
                'approved_count'  => $approvedCount,
                'attendance_pct'  => $percentage,
                'threshold'       => $threshold,
                'light'           => $this->determineLight($percentage, $threshold),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyProgress(string $studentId, string $classroomId): array
    {
        $classroom = Classroom::withoutGlobalScopes()->findOrFail($classroomId);
        $threshold = (int) $classroom->min_attendance_pct;

        return [
            'student_id'      => $studentId,
            'classroom_id'    => $classroomId,
            'total_sessions'  => 0,
            'present_count'   => 0,
            'approved_count'  => 0,
            'attendance_pct'  => 0.0,
            'threshold'       => $threshold,
            'light'           => $this->determineLight(0.0, $threshold),
        ];
    }

    /**
     * Determina el color del semáforo según el porcentaje y el umbral mínimo del aula.
     *
     * @param float $percentage Porcentaje de asistencia (0–100)
     * @param int   $threshold  Umbral mínimo configurado en el aula
     * @return string 'green' | 'amber' | 'red'
     */
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
     * Dispara TrafficLightAlert y notifica al alumno si el semáforo cambió de estado.
     *
     * @param string $studentId     UUID del alumno
     * @param string $classroomId   UUID del aula
     * @param string $previousLight Estado anterior del semáforo ('green', 'amber' o 'red')
     * @return string|null Nuevo valor de light si hubo cambio; null si permanece igual
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
     * Proyecta cuántas faltas sin justificar puede tener el alumno y seguir en o por encima del umbral.
     *
     * @param array<string, mixed> $progress Datos de progreso (total_sessions, present_count, approved_count, threshold, attendance_pct)
     * @return array{remaining: int|null, message: string} remaining es null si no aplica; mensaje descriptivo en español
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
     * Construye un calendario mensual con el estado de asistencia por día de sesión.
     *
     * @param string   $studentId   UUID del alumno
     * @param string   $classroomId UUID del aula
     * @param int|null $year        Año (por defecto el actual)
     * @param int|null $month       Mes 1–12 (por defecto el actual)
     * @return array{year: int, month: int, month_label: string, weeks: array<int, array<int, array<string, mixed>>>, legend: array<string, string>}
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

    /**
     * Obtiene los justificantes del alumno para sesiones del aula indicada.
     *
     * @param string $studentId   UUID del alumno
     * @param string $classroomId UUID del aula
     * @return Collection<int, Justification>
     */
    public function justificationsForClassroom(string $studentId, string $classroomId): Collection
    {
        return Justification::withoutGlobalScopes()
            ->with(['attendance.session', 'reviewer:id,first_name,last_name'])
            ->where('student_id', $studentId)
            ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Lista alumnos inscritos activos con porcentaje de asistencia y semáforo del ciclo.
     *
     * @param string $classroomId UUID del aula
     * @return Collection<int, array<string, mixed>> Filas con id, name, email, attendance_pct, light, etc.
     */
    public function rosterForClassroom(string $classroomId): Collection
    {
        $enrollments = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->with('student:id,first_name,last_name,email')
            ->get()
            ->sortBy(fn ($e) => strtolower(trim($e->student->last_name.' '.$e->student->first_name)));

        return $enrollments->map(function ($enrollment) use ($classroomId) {
            $student  = $enrollment->student;
            $progress = $this->calculate($student->id, $classroomId);

            return [
                'id'             => $student->id,
                'name'           => trim($student->first_name.' '.$student->last_name),
                'email'          => $student->email,
                'initials'       => strtoupper(substr($student->first_name, 0, 1).substr($student->last_name, 0, 1)),
                'attendance_pct' => $progress['attendance_pct'],
                'light'          => $progress['light'],
                'light_label'    => $this->lightLabel($progress['light']),
                'present_count'  => $progress['present_count'],
                'approved_count' => $progress['approved_count'],
                'total_sessions' => $progress['total_sessions'],
            ];
        })->values();
    }

    /**
     * Devuelve la etiqueta en español del estado del semáforo.
     *
     * @param string $light Valor del semáforo ('green', 'amber' o 'red')
     * @return string Etiqueta legible ('En regla', 'En observación' o 'En riesgo')
     */
    public function lightLabel(string $light): string
    {
        return match ($light) {
            'green' => 'En regla',
            'amber' => 'En observación',
            default => 'En riesgo',
        };
    }

    /**
     * Calcula el progreso de asistencia del alumno en todas sus aulas activas.
     *
     * @param User $student Usuario alumno con inscripciones activas
     * @return array<int, array<string, mixed>> Lista de progresos con datos del classroom
     */
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
