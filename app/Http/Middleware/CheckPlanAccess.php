<?php

/**
 * @descripcion  Middleware HTTP CheckPlanAccess: filtra o enriquece solicitudes entrantes.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlanAccess
{
    /**
     * Verifica suscripción activa; bloquea escritura si el plan expiró y expone el plan en la solicitud.
     *
     * @param Request $request Solicitud HTTP entrante
     * @param Closure $next Siguiente middleware o controlador
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Respuesta JSON 401/403/404 o continúa la cadena
     */
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