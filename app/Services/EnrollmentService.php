<?php

/**
 * @descripcion  Servicio de dominio Enrollment: encapsula reglas de negocio reutilizables.
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

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Inscribe a un alumno en el aula del código de invitación (reutilizable hasta su expiración).
     *
     * @param InvitationCode $invitationCode Código de invitación válido con aula asociada
     * @param User           $student        Usuario con rol Student
     * @return Enrollment Inscripción creada o reactivada
     * @throws \RuntimeException Si el rol, código, aula o capacidad no permiten la inscripción
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

            return $enrollment;
        });
    }

    /**
     * Busca un código de invitación vigente (no expirado) por su valor.
     *
     * @param string $code Código ingresado por el alumno (se normaliza a mayúsculas)
     * @return InvitationCode|null Código con classroom cargado, o null si no existe o expiró
     */
    public function findValidInvitationCode(string $code): ?InvitationCode
    {
        return InvitationCode::withoutGlobalScopes()
            ->with('classroom')
            ->where('code', strtoupper(trim($code)))
            ->where('expires_at', '>', now())
            ->first();
    }
}
