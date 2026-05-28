<?php

namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Justification extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'attendance_id', 'student_id', 'file_url',
        'reason', 'status', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            if (!$model->getOriginal('reviewed_at')) {
                return;
            }

            $immutableFields = ['status', 'reviewed_at', 'reviewed_by'];

            foreach ($immutableFields as $field) {
                if ($model->isDirty($field)) {
                    throw new \RuntimeException('Justification review is immutable.');
                }
            }
        });
    }
}
