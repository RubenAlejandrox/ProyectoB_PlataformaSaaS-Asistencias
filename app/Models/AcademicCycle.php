<?php

/**
 * @descripcion  Modelo Eloquent AcademicCycle: representa entidad y relaciones del dominio.
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
use App\Traits\HasInstitutionScope;
use Illuminate\Database\Eloquent\Model;

class AcademicCycle extends Model
{
    use HasUuidKey, HasInstitutionScope;

    protected $fillable = [
        'institution_id', 'classroom_id', 'name', 'start_date', 'end_date',
        'is_closed', 'closed_at', 'closure_key_hash',
        'closure_attempts', 'closure_locked_until',
    ];

    protected function casts(): array
    {
        return [
            'start_date'           => 'date',
            'end_date'             => 'date',
            'is_closed'            => 'boolean',
            'closed_at'            => 'datetime',
            'closure_locked_until' => 'datetime',
        ];
    }

    /**
     * Aula del ciclo académico.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom()   { return $this->belongsTo(Classroom::class); }

    /**
     * Institución propietaria del ciclo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution() { return $this->belongsTo(Institution::class); }

    /**
     * Indica si el cierre del ciclo está bloqueado por intentos fallidos.
     *
     * @return bool
     */
    public function isClosureLocked(): bool
    {
        return $this->closure_locked_until && now()->lt($this->closure_locked_until);
    }

    /**
     * Registra un intento fallido de cierre y bloquea tras tres fallos.
     *
     * @return void
     */
    public function registerFailedClosureAttempt(): void
    {
        $this->increment('closure_attempts');
        if ($this->closure_attempts >= 3) {
            $this->update(['closure_locked_until' => now()->addHours(24)]);
        }
    }
}
