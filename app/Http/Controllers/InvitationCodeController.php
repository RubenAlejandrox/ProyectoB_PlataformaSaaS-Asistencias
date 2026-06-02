<?php

/**
 * @descripcion  Controlador HTTP del módulo InvitationCode: expone acciones web/API del dominio.
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

use App\Models\Classroom;
use App\Models\InvitationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class InvitationCodeController extends Controller
{
    /**
     * Genera un nuevo código de invitación para inscribir alumnos en el aula.
     *
     * @param Classroom $classroom Aula (docente dueño o admin de la institución)
     * @return \Illuminate\Http\RedirectResponse Vuelta atrás con el código en sesión flash
     */
    public function store(Classroom $classroom)
    {
        $user = auth()->user();

        $canGenerate = ((string) $user->id === (string) $classroom->teacher_id)
            || ($user->hasRole('Administrator') && (string) $user->institution_id === (string) $classroom->institution_id);

        if (!$canGenerate) {
            abort(403);
        }

        // Invalidar códigos activos anteriores al regenerar
        InvitationCode::where('classroom_id', $classroom->id)
            ->where('expires_at', '>', now())
            ->update([
                'expires_at' => now(),
                'is_used'    => true,
            ]);

        do {
            $code = strtoupper(Str::random(8));
        } while (InvitationCode::withoutGlobalScopes()->where('code', $code)->exists());

        $invitationCode = InvitationCode::create([
            'classroom_id' => $classroom->id,
            'code'         => $code,
            'expires_at'   => now()->addHours(48),
            'is_used'      => false,
        ]);

        return back()->with('invitation_code', [
            'code'       => $invitationCode->code,
            'expires_at' => $invitationCode->expires_at->format('d/m/Y H:i'),
            'classroom'  => $classroom->subject_name,
        ]);
    }

    /**
     * Devuelve el código de invitación vigente del aula, si existe (API).
     *
     * @param Classroom $classroom Aula consultada
     * @return JsonResponse code, expires_at y flag valid
     */
    public function active(Classroom $classroom): JsonResponse
    {
        $code = InvitationCode::where('classroom_id', $classroom->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return response()->json([
            'code'       => $code?->code,
            'expires_at' => $code?->expires_at?->format('d/m/Y H:i'),
            'valid'      => $code !== null,
        ]);
    }
}
