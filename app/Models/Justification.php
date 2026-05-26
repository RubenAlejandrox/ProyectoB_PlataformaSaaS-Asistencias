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

    public function attendance() { return $this->belongsTo(Attendance::class); }
    public function student()    { return $this->belongsTo(User::class, 'student_id'); }
    public function reviewer()   { return $this->belongsTo(User::class, 'reviewed_by'); }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('status') && $model->getOriginal('reviewed_at')) {
                throw new \Exception('Justification review is immutable.');
            }
        });
    }
}