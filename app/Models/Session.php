<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasUuidKey;

    // Forzar el nuevo nombre para evitar el conflicto con Laravel
    protected $table = 'class_sessions';

    protected $fillable = [
        'classroom_id', 'session_date', 'started_at', 'ended_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date', 'is_active' => 'boolean'];
    }

    public function classroom()   { return $this->belongsTo(Classroom::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function sessionKeys() { return $this->hasMany(SessionKey::class); }
    public function scopeActive($q){ return $q->where('is_active', true); }
}