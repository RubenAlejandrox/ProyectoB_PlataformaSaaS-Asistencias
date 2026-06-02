<?php

/**
 * @descripcion  Modelo Eloquent Justification: representa entidad y relaciones del dominio.
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

class Justification extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'attendance_id', 'student_id', 'file_url',
        'reason', 'status', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /**
     * Registro de asistencia que se justifica.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Estudiante que envió la justificación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Usuario que revisó la justificación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Indica si la justificación está pendiente de revisión.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Indica si la justificación ya fue revisada.
     *
     * @return bool
     */
    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            if (!$model->getOriginal('reviewed_at')) {
                return;
            }

            $immutableFields = ['status', 'reviewed_at', 'reviewed_by'];

            foreach ($immutableFields as $field) {
                if ($model->isDirty($field)) {
                    throw new \RuntimeException('Justification review is immutable.');
                }
            }
        });
    }
}
