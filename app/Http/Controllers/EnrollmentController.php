<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /** Alumno autenticado se inscribe con código de aula (web). */
    public function storeWeb(Request $request): RedirectResponse
    {
        if (!auth()->user()->hasRole('Student')) {
            abort(403);
        }

        $request->validate([
            'invitation_code' => 'required|string',
        ], [
            'invitation_code.required' => 'Ingresa el código de invitación del aula.',
        ]);

        $code = $this->enrollmentService->findValidInvitationCode($request->invitation_code);

        if (!$code) {
            return back()
                ->withInput()
                ->withErrors(['invitation_code' => 'Código de aula inválido o expirado.']);
        }

        try {
            $enrollment = $this->enrollmentService->enrollFromInvitationCode($code, auth()->user());
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['invitation_code' => $e->getMessage()]);
        }

        $aula = $enrollment->classroom->subject_name . ' — ' . $enrollment->classroom->period;

        return redirect()
            ->route('dashboard')
            ->with('success', "Te inscribiste correctamente en {$aula}.");
    }

    /** Alumno autenticado se inscribe con código de aula (API). */
    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('Student')) {
            return response()->json(['message' => 'Solo los alumnos pueden inscribirse.'], 403);
        }

        $request->validate([
            'invitation_code' => 'required|string',
        ]);

        $code = $this->enrollmentService->findValidInvitationCode($request->invitation_code);

        if (!$code) {
            return response()->json([
                'message' => 'Código de aula inválido o expirado.',
            ], 422);
        }

        try {
            $enrollment = $this->enrollmentService->enrollFromInvitationCode($code, auth()->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Inscripción realizada.',
            'data'    => $enrollment->load('classroom'),
        ], 201);
    }
}
