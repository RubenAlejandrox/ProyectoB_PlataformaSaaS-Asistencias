<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationCode extends Model
{
    protected $fillable = ['classroom_id', 'code', 'expires_at', 'is_used'];

    /**
     * Relación con el salón de clases
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Verifica si el código ya expiró
     */
    public function isExpired(): bool
    {
        // Si no hay fecha de expiración, asumimos que nunca expira
        if (!$this->expires_at) {
            return false;
        }

        return now()->gt($this->expires_at);
    }


}
