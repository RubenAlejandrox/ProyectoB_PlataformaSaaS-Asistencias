<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use App\Traits\HasInstitutionScope;
use Illuminate\Database\Eloquent\Model;

class AcademicCycle extends Model
{
    use HasUuidKey, HasInstitutionScope;

    protected $fillable = [
        'institution_id', 'classroom_id', 'name', 'start_date', 'end_date',
        'is_closed', 'closed_at', 'closure_key_hash',
        'closure_attempts', 'closure_locked_until',
    ];

    protected function casts(): array
    {
        return [
            'start_date'           => 'date',
            'end_date'             => 'date',
            'is_closed'            => 'boolean',
            'closed_at'            => 'datetime',
            'closure_locked_until' => 'datetime',
        ];
    }

    public function classroom()   { return $this->belongsTo(Classroom::class); }
    public function institution() { return $this->belongsTo(Institution::class); }

    public function isClosureLocked(): bool
    {
        return $this->closure_locked_until && now()->lt($this->closure_locked_until);
    }

    public function registerFailedClosureAttempt(): void
    {
        $this->increment('closure_attempts');
        if ($this->closure_attempts >= 3) {
            $this->update(['closure_locked_until' => now()->addHours(24)]);
        }
    }
}