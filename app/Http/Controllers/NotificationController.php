<?php

namespace App\Http\Controllers;

use App\Models\StudentNotification;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private StudentNotificationService $notificationService
    ) {}

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

    public function markRead(StudentNotification $studentNotification): RedirectResponse
    {
        if ($studentNotification->user_id !== auth()->user()->id) {
            abort(403);
        }

        $studentNotification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        StudentNotification::where('user_id', auth()->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
