<?php
namespace App\Models;

use App\Traits\HasUuidKey;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasUuidKey;

    protected $fillable = ['name', 'logo_url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users()        { return $this->hasMany(User::class); }
    public function classrooms()   { return $this->hasMany(Classroom::class); }
    public function subscriptions(){ return $this->hasMany(Subscription::class); }
    public function payments()     { return $this->hasMany(Payment::class); }
    public function academicCycles(){ return $this->hasMany(AcademicCycle::class); }

    public function activePlan(): ?Plan
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->latest()->first()?->plan;
    }
}