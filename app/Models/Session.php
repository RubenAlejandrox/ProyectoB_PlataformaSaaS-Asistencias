<?php

/**
 * @descripcion  Modelo Eloquent Session: representa entidad y relaciones del dominio.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasUuidKey;

    // Forzar el nuevo nombre para evitar el conflicto con Laravel
    protected $table = 'class_sessions';

    protected $fillable = [
        'classroom_id', 'session_date', 'started_at', 'ended_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date', 'is_active' => 'boolean'];
    }

    /**
     * Aula a la que pertenece la sesión.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom()   { return $this->belongsTo(Classroom::class); }

    /**
     * Registros de asistencia de la sesión.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances() { return $this->hasMany(Attendance::class); }

    /**
     * Claves de acceso temporales para marcar asistencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sessionKeys() { return $this->hasMany(SessionKey::class); }

    /**
     * Restringe la consulta a sesiones activas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($q){ return $q->where('is_active', true); }
}
