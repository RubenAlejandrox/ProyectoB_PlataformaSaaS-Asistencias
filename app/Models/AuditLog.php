<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuidKey;

    // Forzar el nombre exacto de la tabla en PostgreSQL (Supabase)
    protected $table = 'audit_log';

    // Un log de auditoría no se actualiza, por lo que desactivamos la columna updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'entity', 'entity_id',
        'action', 'old_value', 'new_value',
    ];

    protected function casts(): array
    {
        return ['old_value' => 'array', 'new_value' => 'array'];
    }

    public function user() { return $this->belongsTo(User::class); }

    protected static function booted(): void
    {
        static::updating(fn() => throw new \Exception('AuditLog is immutable.'));
        static::deleting(fn() => throw new \Exception('AuditLog is immutable.'));
    }
}