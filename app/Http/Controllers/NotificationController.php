<?php

/**
 * @descripcion  Controlador HTTP del módulo Notification: expone acciones web/API del dominio.
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

use App\Models\StudentNotification;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * @param StudentNotificationService $notificationService Sincronización de recordatorios
     */
    public function __construct(
        private StudentNotificationService $notificationService
    ) {}

    /**
     * Lista las notificaciones del alumno con filtro de no leídas.
     *
     * @param Request $request Filtro opcional filter=all|unread
     * @return View Vista notificaciones.index
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user->hasRole('Student')) {
            abort(403);
        }

        $this->notificationService->syncSessionRemindersForUser($user);

        $filter = $request->query('filter', 'all');

        $query = StudentNotification::where('user_id', $user->id)
            ->with('classroom:id,subject_name,period')
            ->orderByDesc('created_at');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();

        $stats = [
            'total'  => StudentNotification::where('user_id', $user->id)->count(),
            'unread' => StudentNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ];

        return view('notificaciones.index', compact('notifications', 'stats', 'filter'));
    }

    /**
     * Marca una notificación como leída.
     *
     * @param StudentNotification $studentNotification Notificación del alumno autenticado
     * @return RedirectResponse Vuelta atrás
     */
    public function markRead(StudentNotification $studentNotification): RedirectResponse
    {
        if ($studentNotification->user_id !== auth()->user()->id) {
            abort(403);
        }

        $studentNotification->update(['read_at' => now()]);

        return back();
    }

    /**
     * Marca todas las notificaciones del alumno como leídas.
     *
     * @return RedirectResponse Vuelta atrás con mensaje de éxito
     */
    public function markAllRead(): RedirectResponse
    {
        StudentNotification::where('user_id', auth()->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
