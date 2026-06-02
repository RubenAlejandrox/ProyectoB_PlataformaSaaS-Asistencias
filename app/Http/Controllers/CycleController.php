<?php

/**
 * @descripcion  Controlador HTTP del módulo Cycle: expone acciones web/API del dominio.
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

use App\Models\AcademicCycle;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Session;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CycleController extends Controller
{
    /**
     * @param AttendanceProgressService $progressService Cálculo de aprobados/reprobados al cierre
     */
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    /**
     * Vista de cierre de ciclo académico con estadísticas del ciclo seleccionado.
     *
     * @param Request $request Parámetro opcional cycle (UUID)
     * @return View Vista ciclo.cierre
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $cycles = AcademicCycle::withoutGlobalScopes()
            ->with('classroom')
            ->when($user->hasRole('Teacher'), fn ($q) => $q->whereHas('classroom', fn ($c) => $c->where('teacher_id', $user->id)))
            ->orderByDesc('created_at')
            ->get();

        $selectedId = $request->query('cycle', $cycles->first()?->id);
        $cycle = $cycles->firstWhere('id', $selectedId) ?? $cycles->first();

        $stats = [
            'sessions_count' => 0,
            'approved_count' => 0,
            'failed_count' => 0,
            'pending_justifications' => 0,
        ];

        if ($cycle) {
            $classroomId = $cycle->classroom_id;
            $stats['sessions_count'] = Session::withoutGlobalScopes()->where('classroom_id', $classroomId)->count();
            $stats['pending_justifications'] = Justification::withoutGlobalScopes()
                ->where('status', 'pending')
                ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $classroomId))
                ->count();

            $enrollments = $cycle->classroom->enrollments()->where('is_active', true)->get();
            foreach ($enrollments as $enrollment) {
                $progress = $this->progressService->calculate($enrollment->student_id, $classroomId);
                if ($progress['attendance_pct'] >= $progress['threshold']) {
                    $stats['approved_count']++;
                } else {
                    $stats['failed_count']++;
                }
            }
        }

        return view('ciclo.cierre', compact('cycles', 'cycle', 'stats'));
    }

    /**
     * Cierra un ciclo académico tras validar clave, justificantes pendientes e intentos.
     *
     * @param Request $request closure_key (clave de cierre)
     * @param AcademicCycle $cycle Ciclo a cerrar
     * @return JsonResponse|RedirectResponse Confirmación o error 403/422/423
     */
    public function close(Request $request, AcademicCycle $cycle): JsonResponse|RedirectResponse
    {
        if (!$this->canManageCycle($cycle)) {
            abort(403);
        }

        if ($cycle->is_closed) {
            return $this->errorResponse($request, 'El ciclo ya está cerrado.', 422);
        }

        if ($cycle->isClosureLocked()) {
            return $this->errorResponse(
                $request,
                'Cierre bloqueado temporalmente hasta '.$cycle->closure_locked_until?->format('d/m/Y H:i'),
                423
            );
        }

        $pendingJustifications = Justification::withoutGlobalScopes()
            ->where('status', 'pending')
            ->whereHas('attendance.session', fn ($q) => $q->where('classroom_id', $cycle->classroom_id))
            ->count();

        if ($pendingJustifications > 0) {
            return $this->errorResponse(
                $request,
                'No se puede cerrar el ciclo: existen justificantes pendientes.',
                422
            );
        }

        $request->validate([
            'closure_key' => 'required|string|min:4|max:120',
        ]);

        if (!Hash::check($request->closure_key, (string) $cycle->closure_key_hash)) {
            $cycle->registerFailedClosureAttempt();
            $cycle->refresh();

            $remaining = max(0, 3 - $cycle->closure_attempts);
            $message = $remaining > 0
                ? "Clave de cierre inválida. Intentos restantes: {$remaining}."
                : 'Cierre bloqueado 24 horas por exceso de intentos.';

            return $this->errorResponse($request, $message, 422);
        }

        $cycle->update([
            'is_closed'            => true,
            'closed_at'            => now(),
            'closure_attempts'     => 0,
            'closure_locked_until' => null,
        ]);

        $cycle->classroom()->update(['is_active' => false]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Ciclo cerrado correctamente.']);
        }

        return redirect()
            ->route('ciclo.cierre', ['cycle' => $cycle->id])
            ->with('success', 'Ciclo cerrado correctamente.');
    }

    private function canManageCycle(AcademicCycle $cycle): bool
    {
        $user = auth()->user();
        if ($user->hasRole('Administrator')) {
            return (string) $cycle->institution_id === (string) $user->institution_id;
        }

        return $cycle->classroom && (string) $cycle->classroom->teacher_id === (string) $user->id;
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->withErrors(['general' => $message]);
    }
}
