<?php

/**
 * @descripcion  Servicio de dominio StudentNotification: encapsula reglas de negocio reutilizables.
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
use App\Models\Justification;
use App\Models\Session;
use App\Models\StudentNotification;
use App\Models\User;

class StudentNotificationService
{
    /**
     * Crea una notificación in-app cuando cambia el semáforo de asistencia del alumno.
     *
     * @param string      $studentId     UUID del alumno
     * @param string      $classroomId   UUID del aula
     * @param string      $light         Nuevo estado ('green', 'amber' o 'red')
     * @param float       $percentage    Porcentaje actual de asistencia
     * @param string|null $previousLight Estado anterior del semáforo, si se conoce
     * @return void
     */
    public function notifyTrafficLightChange(
        string $studentId,
        string $classroomId,
        string $light,
        float $percentage,
        ?string $previousLight = null
    ): void {
        $classroom = Classroom::withoutGlobalScopes()->find($classroomId);
        if (! $classroom) {
            return;
        }

        $labels = [
            'green' => 'En regla',
            'amber' => 'En observación',
            'red'   => 'En riesgo',
        ];

        $label = $labels[$light] ?? $light;

        $this->create(
            $studentId,
            $classroomId,
            StudentNotification::TYPE_TRAFFIC_LIGHT,
            "Semáforo: {$label} — {$classroom->subject_name}",
            "Tu asistencia en {$classroom->subject_name} es {$percentage}% ({$label}).",
            [
                'light'          => $light,
                'previous_light' => $previousLight,
                'percentage'     => $percentage,
                'subject_name'   => $classroom->subject_name,
            ]
        );
    }

    /**
     * Notifica al alumno cuando su justificante fue aprobado o rechazado.
     *
     * @param Justification $justification Justificante con attendance.session.classroom y student
     * @return void
     */
    public function notifyJustificationReviewed(Justification $justification): void
    {
        $justification->loadMissing(['attendance.session.classroom', 'student']);

        $classroom = $justification->attendance?->session?->classroom;
        if (! $classroom) {
            return;
        }

        $date = $justification->attendance->session->session_date?->format('d/m/Y') ?? '';
        $approved = $justification->status === 'approved';

        $this->create(
            $justification->student_id,
            $classroom->id,
            $approved
                ? StudentNotification::TYPE_JUSTIFICATION_APPROVED
                : StudentNotification::TYPE_JUSTIFICATION_REJECTED,
            $approved ? 'Justificante aprobado' : 'Justificante rechazado',
            $approved
                ? "Tu justificante del {$date} en {$classroom->subject_name} fue aprobado."
                : "Tu justificante del {$date} en {$classroom->subject_name} fue rechazado.",
            [
                'justification_id' => $justification->id,
                'status'           => $justification->status,
                'session_date'     => $date,
                'subject_name'     => $classroom->subject_name,
            ]
        );
    }

    /**
     * Crea recordatorios para sesiones activas de aulas inscritas (idempotente por sesión, próximos 7 días).
     *
     * @param User $user Usuario alumno con rol Student
     * @return void
     */
    public function syncSessionRemindersForUser(User $user): void
    {
        if (! $user->hasRole('Student')) {
            return;
        }

        $classroomIds = Enrollment::withoutGlobalScopes()
            ->where('student_id', $user->id)
            ->where('is_active', true)
            ->pluck('classroom_id');

        if ($classroomIds->isEmpty()) {
            return;
        }

        $sessions = Session::withoutGlobalScopes()
            ->with('classroom:id,subject_name')
            ->whereIn('classroom_id', $classroomIds)
            ->where('is_active', true)
            ->whereDate('session_date', '>=', now()->toDateString())
            ->whereDate('session_date', '<=', now()->addDays(7)->toDateString())
            ->get();

        foreach ($sessions as $session) {
            $exists = StudentNotification::where('user_id', $user->id)
                ->where('type', StudentNotification::TYPE_SESSION_REMINDER)
                ->where('payload->session_id', $session->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $subject = $session->classroom?->subject_name ?? 'Aula';
            $date    = $session->session_date?->format('d/m/Y') ?? '';

            $this->create(
                $user->id,
                $session->classroom_id,
                StudentNotification::TYPE_SESSION_REMINDER,
                "Sesión activa: {$subject}",
                "Hay una sesión de {$subject} el {$date}. Registra tu asistencia con la clave del docente.",
                [
                    'session_id'   => $session->id,
                    'session_date' => $date,
                    'subject_name' => $subject,
                ]
            );
        }
    }

    private function create(
        string $userId,
        ?string $classroomId,
        string $type,
        string $title,
        string $message,
        array $payload = []
    ): StudentNotification {
        return StudentNotification::create([
            'user_id'      => $userId,
            'classroom_id' => $classroomId,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'payload'      => $payload,
        ]);
    }
}
