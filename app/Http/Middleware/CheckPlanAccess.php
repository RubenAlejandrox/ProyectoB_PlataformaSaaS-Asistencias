<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $institution = auth()->user()->institution;

        if (!$institution) {
            return response()->json(['message' => 'Institution not found.'], 404);
        }

        $subscription = $institution->subscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();

        // Sin suscripción activa — solo lectura
        if (!$subscription || $subscription->isExpired()) {
            // Permitir GET (lectura) pero bloquear POST/PUT/DELETE
            if (!$request->isMethod('GET')) {
                return response()->json([
                    'message'   => 'Plan expired. Upgrade to continue.',
                    'plan_status' => 'expired',
                ], 403);
            }
        }

        // Pasar el plan al request para usarlo en controladores
        $request->merge(['active_plan' => $subscription?->plan]);

        return $next($request);
    }
}