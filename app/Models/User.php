<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected $fillable = [
        'institution_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'is_active',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'locked_until'           => 'datetime',
            'failed_login_attempts'  => 'integer',
        ];
    }

    // ── Auth ──────────────────────────────────────────────────────────────────
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    // ── Relaciones ────────────────────────────────────────────────────────────
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function justifications()
    {
        return $this->hasMany(Justification::class, 'student_id');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'student_id');
    }
}
