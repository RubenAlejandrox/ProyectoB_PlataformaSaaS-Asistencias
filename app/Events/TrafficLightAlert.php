<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrafficLightAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $studentId,
        public string $classroomId,
        public string $light,
        public float $percentage,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('progress.'.$this->studentId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'traffic.light';
    }

    public function broadcastWith(): array
    {
        return [
            'student_id'   => $this->studentId,
            'classroom_id' => $this->classroomId,
            'light'        => $this->light,
            'percentage'   => $this->percentage,
        ];
    }
}
