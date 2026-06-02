<?php

/**
 * @descripcion  Modelo Eloquent Plan: representa entidad y relaciones del dominio.
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

class Plan extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'name', 'price', 'max_students',
        'max_classrooms', 'duration_months', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price' => 'decimal:2'];
    }

    /**
     * Suscripciones que utilizan este plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions() { return $this->hasMany(Subscription::class); }

    /**
     * Restringe la consulta a planes activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($q) { return $q->where('is_active', true); }

    /**
     * Indica si el plan no tiene costo (precio cero).
     *
     * @return bool
     */
    public function isFree(): bool  { return $this->price == 0; }
}
