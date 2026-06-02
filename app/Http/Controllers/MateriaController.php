<?php

/**
 * @descripcion  Controlador HTTP del módulo Materia: expone acciones web/API del dominio.
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
use App\Models\Enrollment;
use App\Services\AttendanceProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriaController extends Controller
{
    /**
     * @param AttendanceProgressService $progressService Progreso, calendario y justificantes
     */
    public function __construct(
        private AttendanceProgressService $progressService
    ) {}

    /**
     * Vista de detalle de materia para el alumno: progreso, calendario mensual y justificantes.
     *
     * @param Request $request Parámetros opcionales year y month
     * @param Classroom $classroom Aula en la que el alumno está inscrito
     * @return View Vista materias.show
     */
    public function show(Request $request, Classroom $classroom): View
    {
        $user = $request->user();

        if (! $user->hasRole('Student')) {
            abort(403);
        }

        $enrolled = Enrollment::withoutGlobalScopes()
            ->where('student_id', $user->id)
            ->where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->exists();

        if (! $enrolled) {
            abort(403, 'No estás inscrito en esta materia.');
        }

        $classroom->load('teacher:id,first_name,last_name');

        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $progress       = $this->progressService->calculate($user->id, $classroom->id);
        $projection     = $this->progressService->projectRemainingAbsences($progress);
        $calendar       = $this->progressService->buildMonthlyCalendar($user->id, $classroom->id, $year, $month);
        $justifications = $this->progressService->justificationsForClassroom($user->id, $classroom->id);

        $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
        $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();

        return view('materias.show', [
            'classroom'      => $classroom,
            'progress'       => $progress,
            'projection'     => $projection,
            'calendar'       => $calendar,
            'justifications' => $justifications,
            'prevMonth'      => ['year' => $prev->year, 'month' => $prev->month],
            'nextMonth'      => ['year' => $next->year, 'month' => $next->month],
        ]);
    }
}
