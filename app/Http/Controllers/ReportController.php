<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceReportMail;
use App\Models\Classroom;
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

        return view('reportes.index', compact('classrooms', 'classroom', 'month', 'riskPreview'));
    }

    public function __construct(
        private ReportGeneratorService $reports
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
        $subject = $request->input('subject') ?: 'Reporte de asistencias';
        $message = $request->input('message') ?: 'Reporte generado desde la plataforma.';

        if ($type === 'matrix') {
            $export = $this->reports->generateMatrix($classroom);
            $filename = 'reporte_matriz_'.$classroom->subject_name.'.xlsx';
        } else {
            $month = $request->string('month')->toString();
            $export = $this->reports->generateMonthly($classroom, $month);
            $filename = 'reporte_mensual_'.$classroom->subject_name.'_'.$month.'.xlsx';
        }

        $bytes = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        Mail::to($request->email)->send(
            new AttendanceReportMail($subject, $message, $filename, $bytes)
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reporte enviado correctamente.']);
        }

        return back()->with('success', 'Reporte enviado correctamente.');
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
