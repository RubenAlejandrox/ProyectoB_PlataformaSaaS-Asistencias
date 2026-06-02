<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEnrolled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $classroomId,
        public string $classroomName,
        public string $studentName,
        public int $enrollmentsCount,
        public int $maxCapacity,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('classroom.'.$this->classroomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'student.enrolled';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'classroom_id'      => $this->classroomId,
            'classroom_name'    => $this->classroomName,
            'student_name'      => $this->studentName,
            'enrollments_count' => $this->enrollmentsCount,
            'max_capacity'      => $this->maxCapacity,
        ];
    }
}
