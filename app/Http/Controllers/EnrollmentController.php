<?php

/**
 * @descripcion  Controlador HTTP del módulo Enrollment: expone acciones web/API del dominio.
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

use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * @param EnrollmentService $enrollmentService Servicio de inscripción por código
     */
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /**
     * Inscribe al alumno autenticado en un aula mediante código de invitación (web).
     *
     * @param Request $request invitation_code y opcional redirect_to
     * @return RedirectResponse Redirección con mensaje de éxito o errores
     */
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

        $redirectTo = $request->input('redirect_to', route('dashboard'));
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, url('/'))) {
            $redirectTo = route('dashboard');
        }

        return redirect()
            ->to($redirectTo)
            ->with('success', "Te inscribiste correctamente en {$aula}.");
    }

    /**
     * Inscribe al alumno autenticado en un aula mediante código de invitación (API).
     *
     * @param Request $request invitation_code
     * @return JsonResponse Inscripción creada con aula, o error 403/422
     */
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
