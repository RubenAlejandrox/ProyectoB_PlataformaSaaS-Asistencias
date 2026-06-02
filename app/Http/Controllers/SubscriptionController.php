<?php

/**
 * @descripcion  Controlador HTTP del módulo Subscription: expone acciones web/API del dominio.
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

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayPalService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SubscriptionController extends Controller
{
    /**
     * @param PayPalService $paypal Integración de pagos PayPal
     * @param SubscriptionService $subscriptions Alta y cambio de membresías
     */
    public function __construct(
        private PayPalService $paypal,
        private SubscriptionService $subscriptions,
    ) {}

    /**
     * Panel de membresías: planes, suscripciones e instituciones sin plan activo.
     *
     * @return \Illuminate\View\View Vista membresias.index
     */
    public function index()
    {
        $plans = Plan::withoutGlobalScopes()->active()->get();

        $subscriptions = Subscription::withoutGlobalScopes()
            ->with(['institution', 'plan'])
            ->orderBy('end_date', 'asc')
            ->paginate(15);

        $institutionsForAssign = Institution::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereDoesntHave('subscriptions', function ($q) {
                $q->where('status', 'active')
                    ->where('end_date', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        $stats = [
            'active'        => Subscription::withoutGlobalScopes()->where('status', 'active')->count(),
            'expiring_soon' => Subscription::withoutGlobalScopes()
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->count(),
            'expired'       => Subscription::withoutGlobalScopes()
                ->where('status', '!=', 'active')
                ->count(),
            'total_plans'   => Plan::withoutGlobalScopes()->active()->count(),
        ];

        return view('membresias.index', compact(
            'plans',
            'subscriptions',
            'institutionsForAssign',
            'stats'
        ));
    }

    /**
     * Inicia checkout PayPal o activa plan gratuito (asignar o cambiar membresía).
     *
     * @param Request $request plan_id, institution_id e intent opcionales (assign|change)
     * @return \Illuminate\Http\RedirectResponse Redirección a PayPal, membresías o errores
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'intent'         => 'nullable|in:assign,change',
        ]);

        if ($request->filled('institution_id')) {
            $institution = Institution::withoutGlobalScopes()
                ->findOrFail($request->institution_id);
        } else {
            $institution = auth()->user()->institution;
        }

        if (!$institution) {
            return back()->withErrors(['general' => 'Institución no encontrada.']);
        }

        $plan = Plan::withoutGlobalScopes()->findOrFail($request->plan_id);
        $intent = $request->input('intent', 'assign');
        $hasActive = $this->subscriptions->institutionHasActiveSubscription($institution);

        if ($intent === 'assign' && $hasActive) {
            return back()->withErrors([
                'general' => 'Esta institución ya tiene una membresía activa. Usa «Actualizar plan» en la tabla para pasar de Basic a Pro.',
            ]);
        }

        if ($intent === 'assign' && !$hasActive) {
            return $this->checkoutOrActivate($institution, $plan, assign: true);
        }

        return $this->checkoutOrActivate($institution, $plan, assign: false);
    }

    /**
     * Callback de PayPal tras pago exitoso: captura orden y activa suscripción.
     *
     * @param Request $request token (order ID) en query string
     * @return \Illuminate\Http\RedirectResponse Redirección a membresías con éxito o error
     * @throws InvalidArgumentException Si la asignación o renovación falla en dominio
     */
    public function paypalSuccess(Request $request)
    {
        $orderId       = $request->get('token');
        $planId        = session('pending_plan_id');
        $institutionId = session('pending_institution_id');
        $assign        = session('pending_assign', true);

        if (!$orderId || !$planId) {
            return redirect()->route('membresias.index')
                ->withErrors(['general' => 'Sesión de pago inválida.']);
        }

        $capture = $this->paypal->captureOrder($orderId);

        if (!$capture || $capture->status !== 'COMPLETED') {
            return redirect()->route('membresias.index')
                ->withErrors(['general' => 'El pago no pudo completarse.']);
        }

        $plan        = Plan::withoutGlobalScopes()->findOrFail($planId);
        $institution = $institutionId
            ? Institution::withoutGlobalScopes()->findOrFail($institutionId)
            : auth()->user()->institution;

        try {
            $subscription = $assign
                ? $this->subscriptions->assignInitial($institution, $plan, $orderId)
                : $this->subscriptions->changeOrRenew($institution, $plan, $orderId);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('membresias.index')
                ->withErrors(['general' => $e->getMessage()]);
        }

        Payment::create([
            'institution_id'    => $institution->id,
            'subscription_id'   => $subscription->id,
            'amount'            => $plan->price,
            'currency'          => 'MXN',
            'status'            => 'completed',
            'paypal_order_id'   => $orderId,
            'paypal_capture_id' => $capture->purchase_units[0]->payments->captures[0]->id ?? null,
            'payment_method'    => 'paypal',
            'paid_at'           => now(),
        ]);

        session()->forget(['pending_plan_id', 'pending_institution_id', 'pending_assign']);

        return redirect()->route('membresias.index')
            ->with('success', "Plan {$plan->name} activado para {$institution->name}.");
    }

    /**
     * Callback cuando el usuario cancela el pago en PayPal.
     *
     * @return \Illuminate\Http\RedirectResponse Redirección a membresías con aviso informativo
     */
    public function paypalCancel()
    {
        session()->forget(['pending_plan_id', 'pending_institution_id', 'pending_assign']);

        return redirect()->route('membresias.index')
            ->with('info', 'Pago cancelado. Puedes intentarlo de nuevo cuando quieras.');
    }

    private function checkoutOrActivate(Institution $institution, Plan $plan, bool $assign)
    {
        if ($plan->isFree()) {
            try {
                $assign
                    ? $this->subscriptions->assignInitial($institution, $plan)
                    : $this->subscriptions->changeOrRenew($institution, $plan);
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['general' => $e->getMessage()]);
            }

            return redirect()->route('membresias.index')
                ->with('success', "Plan {$plan->name} activado para {$institution->name}.");
        }

        $order = $this->paypal->createOrder($plan, $institution);

        if (!$order) {
            return back()->withErrors(['general' => 'Error al conectar con PayPal. Intenta de nuevo.']);
        }

        session([
            'pending_plan_id'        => $plan->id,
            'pending_institution_id' => $institution->id,
            'pending_assign'         => $assign,
        ]);

        return redirect($order['approve_url']);
    }
}
