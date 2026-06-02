<?php

/**
 * @descripcion  Modelo Eloquent StudentNotification: representa entidad y relaciones del dominio.
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

class StudentNotification extends Model
{
    use HasUuidKey;

    public const TYPE_TRAFFIC_LIGHT           = 'traffic_light';
    public const TYPE_JUSTIFICATION_APPROVED  = 'justification_approved';
    public const TYPE_JUSTIFICATION_REJECTED  = 'justification_rejected';
    public const TYPE_SESSION_REMINDER        = 'session_reminder';

    protected $fillable = [
        'user_id',
        'classroom_id',
        'type',
        'title',
        'message',
        'payload',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Estudiante destinatario de la notificación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Aula relacionada con la notificación, si aplica.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Indica si la notificación no ha sido leída.
     *
     * @return bool
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
