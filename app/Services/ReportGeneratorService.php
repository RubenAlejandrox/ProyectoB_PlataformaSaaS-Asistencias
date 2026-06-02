<?php

/**
 * @descripcion  Servicio de dominio ReportGenerator: encapsula reglas de negocio reutilizables.
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

namespace App\Services;

use App\Exports\AttendanceMatrixExport;
use App\Exports\MonthlySummaryExport;
use App\Models\Attendance;
use App\Models\Classroom;
use Carbon\Carbon;

class ReportGeneratorService
{
    /**
     * Genera la exportación Excel de matriz de asistencia por sesión para un aula.
     *
     * @param Classroom $classroom Aula con sesiones e inscripciones activas
     * @return AttendanceMatrixExport Export Maatwebsite con encabezados y filas de la matriz
     */
    public function generateMatrix(Classroom $classroom): AttendanceMatrixExport
    {
        $payload = $this->buildMatrixPayload($classroom);

        return new AttendanceMatrixExport($payload['headings'], $payload['rows']);
    }

    /**
     * Genera la exportación Excel de resumen mensual de asistencia para un aula.
     *
     * @param Classroom $classroom Aula a reportar
     * @param string    $month     Mes en formato Y-m (ej. 2026-05)
     * @return MonthlySummaryExport Export con totales A/F/J y porcentaje del mes
     */
    public function generateMonthly(Classroom $classroom, string $month): MonthlySummaryExport
    {
        $payload = $this->buildMonthlyPayload($classroom, $month);

        return new MonthlySummaryExport($payload['headings'], $payload['rows']);
    }

    /**
     * Construye encabezados y filas de la matriz de asistencia (A/F/J por sesión).
     *
     * @param Classroom $classroom Aula con sesiones ordenadas por fecha
     * @return array{headings: array<int, string>, rows: array<int, array<int, string|int|float>>}
     */
    public function buildMatrixPayload(Classroom $classroom): array
    {
        $sessions = $classroom->sessions()
            ->orderBy('session_date')
            ->get(['id', 'session_date']);

        $headings = ['Alumno'];
        foreach ($sessions as $session) {
            $headings[] = Carbon::parse($session->session_date)->format('d/m/Y');
        }
        $headings[] = 'Total A';
        $headings[] = 'Total F';
        $headings[] = 'Total J';
        $headings[] = '%';

        $rows = [];
        $enrollments = $classroom->enrollments()
            ->where('is_active', true)
            ->with('student:id,first_name,last_name')
            ->get();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $row = [trim($student->first_name.' '.$student->last_name)];
            $counts = ['A' => 0, 'F' => 0, 'J' => 0];

            foreach ($sessions as $session) {
                $attendance = Attendance::withoutGlobalScopes()
                    ->with('justification')
                    ->where('session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->first();

                $mark = 'F';
                if ($attendance && $attendance->status === 'present') {
                    $mark = 'A';
                } elseif (
                    $attendance &&
                    $attendance->status === 'absent' &&
                    $attendance->justification?->status === 'approved'
                ) {
                    $mark = 'J';
                }

                $counts[$mark]++;
                $row[] = $mark;
            }

            $totalSessions = max($sessions->count(), 1);
            $pct = round((($counts['A'] + $counts['J']) / $totalSessions) * 100, 1);
            $row[] = $counts['A'];
            $row[] = $counts['F'];
            $row[] = $counts['J'];
            $row[] = $pct;

            $rows[] = $row;
        }

        return compact('headings', 'rows');
    }

    /**
     * Construye encabezados y filas del resumen mensual de asistencia por alumno.
     *
     * @param Classroom $classroom Aula a reportar
     * @param string    $month     Mes en formato Y-m
     * @return array{headings: array<int, string>, rows: array<int, array<int, string|int|float>>}
     */
    public function buildMonthlyPayload(Classroom $classroom, string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $sessions = $classroom->sessions()
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('session_date')
            ->get(['id']);

        $headings = ['Alumno', 'Mes', 'Total sesiones', 'Asistencias (A)', 'Faltas (F)', 'Justificadas (J)', '%'];
        $rows = [];

        $enrollments = $classroom->enrollments()
            ->where('is_active', true)
            ->with('student:id,first_name,last_name')
            ->get();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $counts = ['A' => 0, 'F' => 0, 'J' => 0];

            foreach ($sessions as $session) {
                $attendance = Attendance::withoutGlobalScopes()
                    ->with('justification')
                    ->where('session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($attendance && $attendance->status === 'present') {
                    $counts['A']++;
                } elseif (
                    $attendance &&
                    $attendance->status === 'absent' &&
                    $attendance->justification?->status === 'approved'
                ) {
                    $counts['J']++;
                } else {
                    $counts['F']++;
                }
            }

            $totalSessions = $sessions->count();
            $pct = $totalSessions > 0
                ? round((($counts['A'] + $counts['J']) / $totalSessions) * 100, 1)
                : 0.0;

            $rows[] = [
                trim($student->first_name.' '.$student->last_name),
                $monthDate->format('Y-m'),
                $totalSessions,
                $counts['A'],
                $counts['F'],
                $counts['J'],
                $pct,
            ];
        }

        return compact('headings', 'rows');
    }
}
