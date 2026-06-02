<?php

/**
 * @descripcion  Reglas de negocio de suscripciones: una activa por institución, asignación y upgrade Free→PRO.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.1.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo
 */


declare(strict_types=1);

namespace App\Services;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Obtiene la suscripción activa vigente de una institución, si existe.
     *
     * @param Institution|string $institution Institución o UUID de institución
     * @return Subscription|null Suscripción con plan cargado, o null
     */
    public function currentlyActive(Institution|string $institution): ?Subscription
    {
        $institutionId = $institution instanceof Institution
            ? $institution->id
            : $institution;

        return Subscription::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->with('plan')
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * Indica si la institución tiene al menos una suscripción activa no vencida.
     *
     * @param Institution|string $institution Institución o UUID de institución
     * @return bool true si hay suscripción activa vigente
     */
    public function institutionHasActiveSubscription(Institution|string $institution): bool
    {
        return $this->currentlyActive($institution) !== null;
    }

    /**
     * Asigna la primera suscripción activa a una institución sin membresía vigente.
     *
     * @param Institution $institution            Institución beneficiaria
     * @param Plan        $plan                   Plan a contratar
     * @param string|null $paypalSubscriptionId   ID de suscripción PayPal, si aplica
     * @return Subscription Suscripción recién creada en estado active
     * @throws \InvalidArgumentException Si la institución ya tiene membresía activa
     */
    public function assignInitial(Institution $institution, Plan $plan, ?string $paypalSubscriptionId = null): Subscription
    {
        if ($this->institutionHasActiveSubscription($institution)) {
            throw new \InvalidArgumentException(
                'Esta institución ya tiene una membresía activa. Usa «Actualizar plan» para cambiar de Basic a Pro.'
            );
        }

        return $this->createActiveSubscription($institution, $plan, $paypalSubscriptionId);
    }

    /**
     * Cambia de plan (upgrade), renueva el mismo plan o asigna la inicial si no hay activa.
     *
     * @param Institution $institution            Institución beneficiaria
     * @param Plan        $plan                   Plan destino
     * @param string|null $paypalSubscriptionId   ID de suscripción PayPal, si aplica
     * @return Subscription Suscripción activa resultante
     * @throws \InvalidArgumentException Si se intenta un downgrade o plan no permitido
     */
    public function changeOrRenew(Institution $institution, Plan $plan, ?string $paypalSubscriptionId = null): Subscription
    {
        $active = $this->currentlyActive($institution);

        if (!$active) {
            return $this->assignInitial($institution, $plan, $paypalSubscriptionId);
        }

        if ($active->plan_id === $plan->id) {
            return $this->renewSamePlan($active, $plan, $paypalSubscriptionId);
        }

        if (!$this->isPlanUpgrade($active->plan, $plan)) {
            throw new \InvalidArgumentException(
                'Solo puedes actualizar a un plan superior (por ejemplo, de Basic a Pro). No se permiten suscripciones duplicadas.'
            );
        }

        return DB::transaction(function () use ($institution, $plan, $paypalSubscriptionId) {
            $this->deactivateActiveSubscriptions($institution);

            return $this->createActiveSubscription($institution, $plan, $paypalSubscriptionId);
        });
    }

    /**
     * Determina si el plan destino es un upgrade respecto al plan actual (por precio).
     *
     * @param Plan $current Plan vigente de la institución
     * @param Plan $target  Plan al que se desea migrar
     * @return bool true si el precio del destino es mayor
     */
    public function isPlanUpgrade(Plan $current, Plan $target): bool
    {
        return (float) $target->price > (float) $current->price;
    }

    private function renewSamePlan(Subscription $active, Plan $plan, ?string $paypalSubscriptionId = null): Subscription
    {
        $active->update([
            'end_date'               => $active->end_date->copy()->addMonths($plan->duration_months),
            'paypal_subscription_id' => $paypalSubscriptionId ?? $active->paypal_subscription_id,
        ]);

        return $active->fresh(['plan', 'institution']);
    }

    private function deactivateActiveSubscriptions(Institution $institution): void
    {
        Subscription::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->update(['status' => 'expired']);
    }

    private function createActiveSubscription(
        Institution $institution,
        Plan $plan,
        ?string $paypalSubscriptionId = null
    ): Subscription {
        return Subscription::create([
            'institution_id'         => $institution->id,
            'plan_id'                => $plan->id,
            'start_date'             => now(),
            'end_date'               => now()->addMonths($plan->duration_months),
            'status'                 => 'active',
            'paypal_subscription_id' => $paypalSubscriptionId,
        ]);
    }
}
