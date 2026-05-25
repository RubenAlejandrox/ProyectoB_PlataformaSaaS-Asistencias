<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionKey extends Model
{
    /**
     * Atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = ['session_id', 'access_key', 'expires_at', 'is_active'];

    /**
     * Obtiene la sesión a la que pertenece esta clave de acceso.
     * Relación inversa de uno a muchos (BelongsTo).
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Determina si la clave de sesión ya ha expirado.
     * Compara la fecha actual con la fecha de expiración.
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }
}