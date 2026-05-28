<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Justification;
use App\Services\AttendanceProgressService;
use App\Services\SupabaseStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JustificationController extends Controller
{
    private const BUCKET = 'justification-files';

    public function __construct(
        private SupabaseStorageService $storage,
        private AttendanceProgressService $progressService
    ) {}

    // ── WEB: listado por rol ──────────────────────────────────────────────────
    public function index(): View
    {
        $user = auth()->user();

        $query = Justification::withoutGlobalScopes()
            ->with([
                'student:id,first_name,last_name,email',
                'attendance.session.classroom',
                'reviewer:id,first_name,last_name',
            ])
            ->orderByDesc('created_at');

        if ($user->hasRole('Student')) {
            $query->where('student_id', $user->id);
        } elseif ($user->hasRole('Teacher')) {
            $query->whereHas('attendance.session.classroom', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        } elseif ($user->hasRole('Administrator')) {
            $query->whereHas('attendance.session.classroom', function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id);
            });
        }

        $justifications = $query->get();

        $stats = [
            'total'    => $justifications->count(),
            'pending'  => $justifications->where('status', 'pending')->count(),
            'approved' => $justifications->where('status', 'approved')->count(),
            'rejected' => $justifications->where('status', 'rejected')->count(),
        ];

        $classrooms = $this->classroomsForFilter($user);

        $absencesWithoutJustification = collect();
        if ($user->hasRole('Student')) {
            $absencesWithoutJustification = Attendance::withoutGlobalScopes()
                ->where('student_id', $user->id)
                ->where('status', 'absent')
                ->where('created_at', '>=', now()->subHours(72))
                ->whereDoesntHave('justification')
                ->whereHas('session.classroom.enrollments', function ($q) use ($user) {
                    $q->where('student_id', $user->id)->where('is_active', true);
                })
                ->with(['session.classroom'])
                ->orderByDesc('created_at')
                ->get();
        }

        return view('justificantes.index', [
            'justifications'               => $justifications,
            'stats'                        => $stats,
            'classrooms'                   => $classrooms,
            'absencesWithoutJustification' => $absencesWithoutJustification,
            'role'                         => $user->getRoleNames()->first(),
            'canReview'                    => $user->hasRole('Teacher') || $user->hasRole('Administrator'),
            'canCreate'                    => $user->hasRole('Student'),
        ]);
    }

    // ── WEB: alumno envía solicitud ───────────────────────────────────────────
    public function storeWeb(Request $request): RedirectResponse
    {
        if (!auth()->user()->hasRole('Student')) {
            abort(403);
        }

        $result = $this->submitJustification($request);

        if ($result->getStatusCode() === 201) {
            return redirect()
                ->route('justificantes.index')
                ->with('success', 'Justificante enviado. Pendiente de revisión por tu docente.');
        }

        $payload = $result->getData(true);

        return back()
            ->withInput()
            ->withErrors(['general' => $payload['message'] ?? 'No se pudo enviar el justificante.']);
    }

    // ── WEB: docente/admin dictamina ──────────────────────────────────────────
    public function reviewWeb(Request $request, Justification $justification): RedirectResponse
    {
        if (!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('Administrator')) {
            abort(403);
        }

        $result = $this->reviewJustification($request, $justification);

        if ($result->getStatusCode() >= 400) {
            $payload = $result->getData(true);

            return back()->withErrors(['general' => $payload['message'] ?? 'No se pudo actualizar el justificante.']);
        }

        $status = $request->input('status') === 'approved' ? 'aprobado' : 'rechazado';

        return redirect()
            ->route('justificantes.index')
            ->with('success', "Justificante {$status} correctamente.");
    }

    // ── API: store ────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        return $this->submitJustification($request);
    }

    // ── API: review ─────────────────────────────────────────────────────────────
    public function review(Request $request, Justification $justification): JsonResponse
    {
        return $this->reviewJustification($request, $justification);
    }

    // ── API: show ───────────────────────────────────────────────────────────────
    public function show(Justification $justification): JsonResponse
    {
        $justification->load([
            'student:id,first_name,last_name,email',
            'attendance.session.classroom',
            'reviewer:id,first_name,last_name',
        ]);

        if (!$this->canAccessJustification($justification)) {
            return response()->json([
                'message' => 'No tienes permiso para ver este justificante.',
            ], 403);
        }

        return response()->json(['data' => $justification]);
    }

    // ── Lógica compartida: crear justificante ─────────────────────────────────
    private function submitJustification(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_id' => 'required|uuid|exists:attendances,id',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'reason'        => 'nullable|string|max:500',
        ], [
            'attendance_id.required' => 'Selecciona la falta a justificar.',
            'file.required'          => 'Debes adjuntar un documento de respaldo.',
            'file.mimes'             => 'El archivo debe ser PDF, JPG o PNG.',
            'file.max'               => 'El archivo no debe superar 5MB.',
        ]);

        $attendance = Attendance::withoutGlobalScopes()
            ->with('session.classroom')
            ->findOrFail($request->attendance_id);

        if ($attendance->student_id !== auth()->user()->id) {
            return response()->json([
                'message' => 'No puedes justificar una asistencia que no te pertenece.',
            ], 403);
        }

        if ($attendance->justification()->exists()) {
            return response()->json([
                'message' => 'Ya existe un justificante para esta asistencia.',
            ], 409);
        }

        if ($attendance->status !== 'absent') {
            return response()->json([
                'message' => 'Solo puedes justificar una falta.',
            ], 422);
        }

        if ($attendance->created_at->lt(now()->subHours(72))) {
            return response()->json([
                'message' => 'La ventana de 72 horas para justificar esta falta ya expiró.',
            ], 422);
        }

        if (!$this->storage->isAllowedMime($request->file('file'), self::BUCKET)) {
            return response()->json([
                'message' => 'Tipo de archivo no permitido. Solo PDF, JPG o PNG.',
            ], 422);
        }

        $fileUrl = $this->storage->upload(
            $request->file('file'),
            self::BUCKET,
            (string) auth()->user()->id
        );

        if (!$fileUrl) {
            return response()->json([
                'message' => 'Error al subir el archivo. Intenta de nuevo.',
            ], 422);
        }

        $justification = Justification::create([
            'attendance_id' => $attendance->id,
            'student_id'    => auth()->user()->id,
            'file_url'      => $fileUrl,
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        return response()->json([
            'message' => 'Justificante enviado. Pendiente de revisión.',
            'data'    => $justification,
        ], 201);
    }

    // ── Lógica compartida: revisar justificante ───────────────────────────────
    private function reviewJustification(Request $request, Justification $justification): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $justification->load(['attendance.session.classroom']);

        if (!$this->canReviewJustification($justification)) {
            return response()->json([
                'message' => 'No tienes permiso para revisar este justificante.',
            ], 403);
        }

        if (!$justification->isPending()) {
            return response()->json([
                'message' => 'Este justificante ya fue revisado.',
            ], 422);
        }

        try {
            $studentId = $justification->student_id;
            $classroomId = $justification->attendance->session->classroom_id;
            $previousLight = $this->progressService->calculate($studentId, $classroomId)['light'];

            $justification->update([
                'status'      => $request->status,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->user()->id,
            ]);

            if ($request->status === 'approved') {
                $this->progressService->dispatchTrafficLightIfChanged(
                    $studentId,
                    $classroomId,
                    $previousLight
                );
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $request->status === 'approved'
                ? 'Justificante aprobado.'
                : 'Justificante rechazado.',
            'data'    => $justification->fresh(['student', 'attendance']),
        ]);
    }

    private function classroomsForFilter($user)
    {
        if ($user->hasRole('Teacher')) {
            return Classroom::withoutGlobalScopes()
                ->where('teacher_id', $user->id)
                ->orderBy('subject_name')
                ->get();
        }

        if ($user->hasRole('Administrator')) {
            return Classroom::withoutGlobalScopes()
                ->where('institution_id', $user->institution_id)
                ->orderBy('subject_name')
                ->get();
        }

        if ($user->hasRole('Student')) {
            return Classroom::withoutGlobalScopes()
                ->whereHas('enrollments', function ($q) use ($user) {
                    $q->where('student_id', $user->id)->where('is_active', true);
                })
                ->orderBy('subject_name')
                ->get();
        }

        return collect();
    }

    private function canAccessJustification(Justification $justification): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Administrator')) {
            return $justification->attendance?->session?->classroom?->institution_id === $user->institution_id;
        }

        if ($user->hasRole('Teacher')) {
            return $this->canReviewJustification($justification);
        }

        if ($user->hasRole('Student')) {
            return $justification->student_id === $user->id;
        }

        return false;
    }

    private function canReviewJustification(Justification $justification): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Administrator')) {
            return $justification->attendance?->session?->classroom?->institution_id === $user->institution_id;
        }

        $classroom = $justification->attendance?->session?->classroom;

        return $classroom && $classroom->teacher_id === $user->id;
    }
}
