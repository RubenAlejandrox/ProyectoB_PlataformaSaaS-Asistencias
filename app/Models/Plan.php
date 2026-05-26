<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'name', 'price', 'max_students',
        'max_classrooms', 'duration_months', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price' => 'decimal:2'];
    }

    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function isFree(): bool  { return $this->price == 0; }
}