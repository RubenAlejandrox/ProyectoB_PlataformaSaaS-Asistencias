<?php

/**
 * @descripcion  Controlador HTTP del módulo AdminSession: expone acciones web/API del dominio.
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
use App\Models\Session;
use App\Models\SessionKey;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    /**
     * Listado administrativo de sesiones con filtros, estadísticas y sesiones activas.
     *
     * @param Request $request Filtros: classroom_id, status, from_date, to_date
     * @return View Vista admin.sesiones
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $query = Session::withoutGlobalScopes()
            ->with([
                'classroom:id,subject_name,period,teacher_id',
                'classroom.teacher:id,first_name,last_name',
            ])
            ->withCount([
                'attendances',
                'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendances as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at');

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->string('classroom_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('session_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('session_date', '<=', $request->date('to_date'));
        }

        $sessions = $query->paginate(20)->withQueryString();

        $classrooms = Classroom::withoutGlobalScopes()
            ->select('id', 'subject_name', 'period')
            ->orderBy('subject_name')
            ->get();

        $activeSessions = Session::withoutGlobalScopes()
            ->with('classroom:id,subject_name,period')
            ->where('is_active', true)
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = [
            'total' => Session::withoutGlobalScopes()->count(),
            'active' => Session::withoutGlobalScopes()->where('is_active', true)->count(),
            'closed' => Session::withoutGlobalScopes()->where('is_active', false)->count(),
        ];

        return view('admin.sesiones', compact('sessions', 'classrooms', 'stats', 'activeSessions'));
    }

    /**
     * Genera una clave de asistencia de 30 minutos para una sesión activa (admin).
     *
     * @param Session $session Sesión de clase activa
     * @return RedirectResponse Vuelta atrás con datos de la clave o error
     */
    public function generateAttendanceKey(Session $session): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        if (!$session->is_active) {
            return back()->withErrors(['general' => 'La sesión está cerrada. No se puede generar clave de asistencia.']);
        }

        $session->sessionKeys()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        do {
            $accessKey = strtoupper(Str::random(8));
        } while (SessionKey::withoutGlobalScopes()->where('access_key', $accessKey)->exists());

        $sessionKey = SessionKey::create([
            'session_id' => $session->id,
            'access_key' => $accessKey,
            'expires_at' => now()->addMinutes(30),
            'is_active'  => true,
        ]);

        return back()->with('attendance_access_key', [
            'code'       => $sessionKey->access_key,
            'expires_at' => $sessionKey->expires_at->format('d/m/Y H:i'),
            'classroom'  => $session->classroom?->subject_name ?? 'Aula',
            'session'    => $session->session_date?->format('d/m/Y'),
        ]);
    }
}
