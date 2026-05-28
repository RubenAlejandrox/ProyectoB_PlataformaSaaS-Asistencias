<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Inscribe a un alumno en el aula del código de invitación.
     * Marca el código como usado y sincroniza institution_id del alumno.
     *
     * @throws \RuntimeException
     */
    public function enrollFromInvitationCode(InvitationCode $invitationCode, User $student): Enrollment
    {
        if (!$student->hasRole('Student')) {
            throw new \RuntimeException('Solo los alumnos pueden inscribirse en un aula.');
        }

        if (!$invitationCode->isValid()) {
            throw new \RuntimeException('Código de aula inválido o expirado.');
        }

        return DB::transaction(function () use ($invitationCode, $student) {
            $classroom = Classroom::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($invitationCode->classroom_id);

            if (!$classroom->is_active) {
                throw new \RuntimeException('El aula no está activa.');
            }

            $existing = Enrollment::withoutGlobalScopes()
                ->where('classroom_id', $classroom->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existing?->is_active) {
                throw new \RuntimeException('Ya estás inscrito en esta aula.');
            }

            if (!$existing && $classroom->isFull()) {
                throw new \RuntimeException('El aula ha alcanzado su capacidad máxima.');
            }

            if ($existing) {
                $existing->update([
                    'is_active'   => true,
                    'enrolled_at' => now(),
                ]);
                $enrollment = $existing->fresh();
            } else {
                $enrollment = Enrollment::create([
                    'classroom_id' => $classroom->id,
                    'student_id'   => $student->id,
                    'enrolled_at'  => now(),
                    'is_active'    => true,
                ]);
            }

            if ($student->institution_id !== $classroom->institution_id) {
                $student->update(['institution_id' => $classroom->institution_id]);
            }

            $invitationCode->update(['is_used' => true]);

            return $enrollment;
        });
    }

    public function findValidInvitationCode(string $code): ?InvitationCode
    {
        return InvitationCode::withoutGlobalScopes()
            ->with('classroom')
            ->where('code', strtoupper(trim($code)))
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
