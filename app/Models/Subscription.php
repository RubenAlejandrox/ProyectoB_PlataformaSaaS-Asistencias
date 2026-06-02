<?php

/**
 * @descripcion  Modelo Eloquent Subscription: representa entidad y relaciones del dominio.
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

class Subscription extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'institution_id', 'plan_id', 'start_date',
        'end_date', 'status', 'paypal_subscription_id',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    /**
     * Institución titular de la suscripción.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution() { return $this->belongsTo(Institution::class); }

    /**
     * Plan contratado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()        { return $this->belongsTo(Plan::class); }

    /**
     * Pagos asociados a esta suscripción.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()    { return $this->hasMany(Payment::class); }

    /**
     * Restringe la consulta a suscripciones con estado activo.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($q){ return $q->where('status', 'active'); }

    /**
     * Indica si la suscripción ya venció o fue marcada como expirada.
     *
     * @return bool
     */
    public function isExpired(): bool
{
    return $this->end_date->isPast() || $this->status === 'expired';
}

    /**
     * Días restantes hasta el fin de la suscripción (cero si ya venció).
     *
     * @return int
     */
    public function getDaysRemainingAttribute(): int
    {
        return (int) max(0, now()->diffInDays($this->end_date, false));
    }
}
