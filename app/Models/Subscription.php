<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'institution_id', 'plan_id', 'start_date',
        'end_date', 'status', 'paypal_subscription_id',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function institution() { return $this->belongsTo(Institution::class); }
    public function plan()        { return $this->belongsTo(Plan::class); }
    public function payments()    { return $this->hasMany(Payment::class); }
    public function scopeActive($q){ return $q->where('status', 'active'); }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }
}