<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class SessionKey extends Model
{
    use HasUuidKey;

    protected $fillable = ['session_id', 'access_key', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function session() { return $this->belongsTo(Session::class); }

    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}