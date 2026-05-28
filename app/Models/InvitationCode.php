<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class InvitationCode extends Model
{
    use HasUuidKey;

    protected $fillable = ['classroom_id', 'code', 'expires_at', 'is_used'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_used' => 'boolean'];
    }

    public function classroom() { return $this->belongsTo(Classroom::class); }

    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}