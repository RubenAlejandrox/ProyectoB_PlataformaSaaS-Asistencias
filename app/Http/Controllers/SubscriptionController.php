<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayPalService;
use App\Models\Institution;
use App\Models\Payment;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private PayPalService $paypal
    ) {}

    public function index()
{
    $plans = Plan::withoutGlobalScopes()->active()->get();

    $subscriptions = Subscription::withoutGlobalScopes()
        ->with(['institution', 'plan'])
        ->orderBy('end_date', 'asc')
        ->paginate(15);

    $institutions = \App\Models\Institution::withoutGlobalScopes()
        ->where('is_active', true)
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
        'plans', 'subscriptions', 'institutions', 'stats'
    ));
}

   public function upgrade(Request $request)
{
    $request->validate([
        'plan_id'        => 'required|exists:plans,id',
        'institution_id' => 'nullable|exists:institutions,id', // ← agregar
    ]);

    // Si viene institution_id del modal admin, usarla
    // Si no, usar la institución del usuario autenticado
    if ($request->filled('institution_id')) {
        $institution = \App\Models\Institution::withoutGlobalScopes()
            ->findOrFail($request->institution_id);
    } else {
        $institution = auth()->user()->institution;
    }

    $plan = Plan::withoutGlobalScopes()->findOrFail($request->plan_id);

    // Plan gratuito — activar directo sin PayPal
    if ($plan->isFree()) {
        Subscription::create([
            'institution_id' => $institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonths($plan->duration_months),
            'status'         => 'active',
        ]);
        return redirect()->route('membresias.index')
            ->with('success', "Plan {$plan->name} activado para {$institution->name}.");
    }

    // Plan de pago — crear orden PayPal
    $order = $this->paypal->createOrder($plan, $institution);

    if (!$order) {
        return back()->withErrors(['general' => 'Error al conectar con PayPal. Intenta de nuevo.']);
    }

    // Guardar en sesión para el callback
    session([
        'pending_plan_id'        => $plan->id,
        'pending_institution_id' => $institution->id,
    ]);

    return redirect($order['approve_url']);
}
    public function paypalSuccess(Request $request)
{
    $orderId       = $request->get('token');
    $planId        = session('pending_plan_id');
    $institutionId = session('pending_institution_id');

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
        ? \App\Models\Institution::withoutGlobalScopes()->findOrFail($institutionId)
        : auth()->user()->institution;

    $subscription = Subscription::create([
        'institution_id'         => $institution->id,
        'plan_id'                => $plan->id,
        'start_date'             => now(),
        'end_date'               => now()->addMonths($plan->duration_months),
        'status'                 => 'active',
        'paypal_subscription_id' => $orderId,
    ]);

    \App\Models\Payment::create([
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

    session()->forget(['pending_plan_id', 'pending_institution_id']);

    return redirect()->route('membresias.index')
        ->with('success', "Plan {$plan->name} activado para {$institution->name}.");
}

    public function paypalCancel()
    {
        session()->forget('pending_plan_id');
        return redirect()->route('membresias.index')
            ->with('info', 'Pago cancelado. Puedes intentarlo de nuevo cuando quieras.');
    }
}