<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use App\Traits\HasInstitutionScope;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasUuidKey, HasInstitutionScope;

    protected $fillable = [
        'institution_id', 'teacher_id', 'subject_name',
        'period', 'min_attendance_pct', 'max_capacity', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teacher()        { return $this->belongsTo(User::class, 'teacher_id'); }
    public function enrollments()    { return $this->hasMany(Enrollment::class); }
    public function sessions()       { return $this->hasMany(Session::class); }
    public function invitationCodes(){ return $this->hasMany(InvitationCode::class); }
    public function academicCycles() { return $this->hasMany(AcademicCycle::class); }
    public function alerts()         { return $this->hasMany(Alert::class); }

    public function isFull(): bool
    {
        return $this->enrollments()
            ->where('is_active', true)
            ->count() >= $this->max_capacity;
    }
}