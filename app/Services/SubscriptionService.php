<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
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

    public function institutionHasActiveSubscription(Institution|string $institution): bool
    {
        return $this->currentlyActive($institution) !== null;
    }

    /**
     * Primera suscripción: institución sin membresía activa.
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
     * Cambio de plan (p. ej. Basic → Pro) o renovación del mismo plan al vencer.
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
