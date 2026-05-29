<?php

namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class StudentNotification extends Model
{
    use HasUuidKey;

    public const TYPE_TRAFFIC_LIGHT           = 'traffic_light';
    public const TYPE_JUSTIFICATION_APPROVED  = 'justification_approved';
    public const TYPE_JUSTIFICATION_REJECTED  = 'justification_rejected';
    public const TYPE_SESSION_REMINDER        = 'session_reminder';

    protected $fillable = [
        'user_id',
        'classroom_id',
        'type',
        'title',
        'message',
        'payload',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
