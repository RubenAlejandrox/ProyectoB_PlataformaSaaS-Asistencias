<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'classroom_id',
        'session_date',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'is_active'    => 'boolean',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function sessionKeys()
    {
        return $this->hasMany(SessionKey::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}