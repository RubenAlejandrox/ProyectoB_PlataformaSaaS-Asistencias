<?php

/**
 * @descripcion  Controlador HTTP del módulo Attendance: expone acciones web/API del dominio.
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

use App\Events\AttendanceRegistered;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SessionKey;
use App\Services\AttendanceProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * @param AttendanceProgressService $progressService Servicio de progreso y semáforo de asistencia
     */
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    /**
     * Registra la asistencia del alumno autenticado mediante clave de sesión (API).
     *
     * @param Request $request Clave de acceso de 8 caracteres (access_key)
     * @return JsonResponse Asistencia creada con progreso, o error 403/409/422
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'access_key' => 'required|string|size:8',
        ]);

        $accessKey = strtoupper($request->access_key);

        $sessionKey = SessionKey::withoutGlobalScopes()
            ->with(['session.classroom'])
            ->where('access_key', $accessKey)
            ->first();

        if (!$sessionKey || !$sessionKey->isValid()) {
            return response()->json([
                'message' => 'Clave de asistencia inválida o expirada.',
            ], 422);
        }

        $session = $sessionKey->session;

        if (!$session || !$session->is_active) {
            return response()->json([
                'message' => 'La sesión no está activa.',
            ], 422);
        }

        $studentId   = auth()->user()->id;
        $classroomId = $session->classroom_id;

        $isEnrolled = Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'message' => 'No estás inscrito en el aula de esta sesión.',
            ], 403);
        }

        $alreadyRegistered = Attendance::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'message' => 'Ya registraste asistencia en esta sesión.',
            ], 409);
        }

        $progressBefore = $this->progressService->calculate($studentId, $classroomId);
        $previousLight  = $progressBefore['light'];

        $attendance = Attendance::create([
            'session_id' => $session->id,
            'student_id' => $studentId,
            'status'     => 'present',
        ]);

        event(new AttendanceRegistered($attendance, $classroomId));
        $this->progressService->dispatchTrafficLightIfChanged($studentId, $classroomId, $previousLight);

        $progress = $this->progressService->calculate($studentId, $classroomId);

        return response()->json([
            'message' => 'Asistencia registrada.',
            'data'    => $attendance,
            'progress'=> $progress,
        ], 201);
    }

    /**
     * Obtiene el progreso de asistencia del alumno autenticado en un aula (API).
     *
     * @param string $classroomId UUID del aula
     * @return JsonResponse Porcentaje, semáforo y contadores, o error 403
     */
    public function progress(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        if (!$this->isEnrolled($studentId, $classroomId)) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        return response()->json(
            $this->progressService->calculate($studentId, $classroomId)
        );
    }

    /**
     * Devuelve historial de asistencias y progreso del alumno en un aula (API).
     *
     * @param string $classroomId UUID del aula
     * @return JsonResponse Progreso y listado de asistencias, o error 403
     */
    public function portal(string $classroomId): JsonResponse
    {
        $studentId = auth()->user()->id;

        if (!$this->isEnrolled($studentId, $classroomId)) {
            return response()->json([
                'message' => 'No estás inscrito en esta aula.',
            ], 403);
        }

        $attendances = Attendance::withoutGlobalScopes()
            ->with(['session:id,classroom_id,session_date', 'justification'])
            ->where('student_id', $studentId)
            ->whereHas('session', fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->get();

        $progress = $this->progressService->calculate($studentId, $classroomId);

        return response()->json([
            'progress'    => $progress,
            'attendances' => $attendances,
        ]);
    }

    private function isEnrolled(string $studentId, string $classroomId): bool
    {
        return Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }
}
