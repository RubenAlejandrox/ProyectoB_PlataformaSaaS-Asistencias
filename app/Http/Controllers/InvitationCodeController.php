<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\InvitationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class InvitationCodeController extends Controller
{
    // ── store — generar nuevo código para un aula ─────────────────────────────
    public function store(Classroom $classroom)
    {
        if (auth()->id() !== $classroom->teacher_id) {
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

    // ── active — obtener código activo de un aula ─────────────────────────────
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
