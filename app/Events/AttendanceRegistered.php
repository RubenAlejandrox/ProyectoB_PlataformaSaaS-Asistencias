<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRegistered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array{attendance_pct?: float, light?: string}|null  $progress
     */
    public function __construct(
        public Attendance $attendance,
        public string $classroomId,
        public ?array $progress = null,
    ) {
        $this->attendance->loadMissing('student:id,first_name,last_name');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('attendance.'.$this->classroomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.registered';
    }

    public function broadcastWith(): array
    {
        $student = $this->attendance->student;

        return [
            'attendance_id' => $this->attendance->id,
            'student_id'    => $this->attendance->student_id,
            'student_name'  => trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
            'status'        => $this->attendance->status,
            'registered_at' => $this->attendance->created_at?->toIso8601String(),
            'session_id'    => $this->attendance->session_id,
            'pct'           => $this->progress['attendance_pct'] ?? null,
            'light'         => $this->progress['light'] ?? null,
        ];
    }
}
