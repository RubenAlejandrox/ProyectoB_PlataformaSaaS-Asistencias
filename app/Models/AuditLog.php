<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * Atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'entity',
        'entity_id',
        'action',
        'old_value',
        'new_value'
    ];

    /**
     * Desactiva la columna updated_at.
     * Este modelo solo permite inserciones (INSERT), no actualizaciones.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * El método de arranque (booted) del modelo.
     * Protege la inmutabilidad de los registros bloqueando cambios y eliminaciones.
     */
    protected static function booted(): void
    {
        // Lanza una excepción si se intenta actualizar el registro
        static::updating(fn() => throw new \Exception('AuditLog is immutable.'));
        
        // Lanza una excepción si se intenta eliminar el registro
        static::deleting(fn() => throw new \Exception('AuditLog is immutable.'));
    }

    /**
     * Obtiene el usuario que generó el registro de auditoría.
     * Relación inversa de uno a muchos (BelongsTo).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}