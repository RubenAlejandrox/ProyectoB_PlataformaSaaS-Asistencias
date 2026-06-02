<?php

/**
 * @descripcion  Modelo Eloquent Payment: representa entidad y relaciones del dominio.
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

class Payment extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'institution_id', 'subscription_id', 'amount', 'currency',
        'status', 'paypal_order_id', 'paypal_capture_id',
        'payment_method', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    /**
     * Institución que realizó el pago.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution()  { return $this->belongsTo(Institution::class); }

    /**
     * Suscripción cubierta por el pago.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription() { return $this->belongsTo(Subscription::class); }

    /**
     * Indica si el pago fue completado con éxito.
     *
     * @return bool
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }
}
