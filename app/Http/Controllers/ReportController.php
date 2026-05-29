<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceReportMail;
use App\Models\Classroom;
use App\Services\MailDeliveryService;
use App\Services\ReportGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $classrooms = Classroom::withoutGlobalScopes()
            ->when($user->hasRole('Teacher'), fn ($q) => $q->where('teacher_id', $user->id))
            ->when($user->hasRole('Administrator'), fn ($q) => $q->where('institution_id', $user->institution_id))
            ->orderBy('subject_name')
            ->get();

        $selectedClassroomId = $request->query('classroom', $classrooms->first()?->id);
        $classroom = $classrooms->firstWhere('id', $selectedClassroomId) ?? $classrooms->first();
        $month = $request->query('month', now()->format('Y-m'));

        $riskPreview = collect();
        if ($classroom) {
            $monthly = $this->reports->buildMonthlyPayload($classroom, $month);
            $riskPreview = collect($monthly['rows'])
                ->map(fn ($r) => [
                    'student' => $r[0],
                    'pct' => $r[6],
                    'status' => $r[6] < $classroom->min_attendance_pct ? 'Riesgo' : 'OK',
                ])
                ->sortBy('pct')
                ->values();
        }

        $mailConfigured = ! in_array(config('mail.default'), ['log', 'array'], true)
            && ! str_contains((string) config('mail.from.address'), 'example.com');

        return view('reportes.index', compact('classrooms', 'classroom', 'month', 'riskPreview', 'mailConfigured'));
    }

    public function __construct(
        private ReportGeneratorService $reports,
        private MailDeliveryService $mailDelivery,
    ) {}

    public function matrix(Classroom $classroom): BinaryFileResponse
    {
        $this->authorizeClassroom($classroom);

        $filename = 'reporte_matriz_'.$classroom->subject_name.'_'.now()->format('Ymd_His').'.xlsx';
        return Excel::download($this->reports->generateMatrix($classroom), $filename);
    }

    public function monthly(Request $request, Classroom $classroom): BinaryFileResponse|JsonResponse
    {
        $this->authorizeClassroom($classroom);

        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month = $request->string('month')->toString();
        $filename = 'reporte_mensual_'.$classroom->subject_name.'_'.$month.'.xlsx';

        return Excel::download($this->reports->generateMonthly($classroom, $month), $filename);
    }

    public function send(Request $request, Classroom $classroom): JsonResponse|RedirectResponse
    {
        $this->authorizeClassroom($classroom);

        $request->validate([
            'email'   => 'required|email',
            'type'    => 'required|in:matrix,monthly',
            'month'   => 'required_if:type,monthly|date_format:Y-m',
            'subject' => 'nullable|string|max:180',
            'message' => 'nullable|string|max:1000',
        ]);

        $type = $request->string('type')->toString();
        $month = $type === 'monthly' ? $request->string('month')->toString() : null;

        $reportTypeLabel = $type === 'matrix'
            ? 'Matriz de asistencias (A / F / J)'
            : 'Resumen mensual';

        $defaultSubject = $type === 'matrix'
            ? "Reporte de asistencias — {$classroom->subject_name}"
            : "Reporte mensual {$month} — {$classroom->subject_name}";

        $subject = $request->input('subject') ?: $defaultSubject;

        $defaultMessage = $type === 'matrix'
            ? "Adjuntamos el reporte de asistencias del aula {$classroom->subject_name} ({$classroom->period}). "
                . 'El archivo Excel incluye el detalle por sesión y alumno (asistencia, falta y justificante).'
            : "Adjuntamos el resumen mensual de asistencias del aula {$classroom->subject_name} para el período {$month}. "
                . 'Revise el archivo adjunto para identificar alumnos en riesgo según el umbral del aula.';

        $message = $request->input('message') ?: $defaultMessage;

        $user = $request->user();
        $senderName = trim("{$user->first_name} {$user->last_name}");
        $reportTitle = $type === 'matrix'
            ? 'Matriz de asistencias'
            : 'Resumen mensual de asistencias';

        if ($type === 'matrix') {
            $export = $this->reports->generateMatrix($classroom);
            $filename = 'reporte_matriz_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $classroom->subject_name) . '.xlsx';
        } else {
            $export = $this->reports->generateMonthly($classroom, $month);
            $filename = 'reporte_mensual_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $classroom->subject_name) . '_' . $month . '.xlsx';
        }

        $bytes = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        try {
            $this->mailDelivery->send(function () use (
                $request,
                $subject,
                $message,
                $filename,
                $bytes,
                $classroom,
                $reportTypeLabel,
                $month,
                $senderName,
                $user,
                $reportTitle
            ) {
                Mail::to($request->email)->send(
                    new AttendanceReportMail(
                        subjectLine: $subject,
                        messageBody: $message,
                        attachmentName: $filename,
                        attachmentData: $bytes,
                        classroomName: $classroom->subject_name . ' — ' . $classroom->period,
                        reportTypeLabel: $reportTypeLabel,
                        periodLabel: $month ? $this->formatMonthLabel($month) : null,
                        senderName: $senderName,
                        senderEmail: $user->email,
                        reportTitle: $reportTitle,
                    )
                );
            });
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['email' => $e->getMessage()]);
        }

        $successMsg = "Reporte enviado a {$request->email}. Revise la bandeja de entrada y la carpeta de spam si no aparece en unos minutos.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $successMsg]);
        }

        return back()->with('success', $successMsg);
    }

    private function formatMonthLabel(string $month): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)
                ->locale('es')
                ->isoFormat('MMMM YYYY');
        } catch (\Exception) {
            return $month;
        }
    }

    private function authorizeClassroom(Classroom $classroom): void
    {
        $user = auth()->user();
        if ($user->hasRole('Teacher') && (string) $classroom->teacher_id !== (string) $user->id) {
            abort(403);
        }
        if ($user->hasRole('Administrator') && (string) $classroom->institution_id !== (string) $user->institution_id) {
            abort(403);
        }
    }
}
