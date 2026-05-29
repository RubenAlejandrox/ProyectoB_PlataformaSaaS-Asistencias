<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}
    public function index(Request $request): View
    {
        if (! $request->user()->hasRole('Administrator')) {
            abort(403);
        }

        $data = $this->buildPaymentsQuery($request);

        return view('admin.pagos.index', [
            'payments'    => $data['payments'],
            'stats'       => $data['stats'],
            'institutions'=> $data['institutions'],
        ]);
    }

    public function invoice(Payment $payment): Response
    {
        if (! auth()->user()->hasRole('Administrator')) {
            abort(403);
        }

        if ($payment->status !== 'completed') {
            abort(422, 'Solo se puede emitir factura para pagos completados.');
        }

        $pdf = $this->invoiceService->renderPdf($payment);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->invoiceService->downloadFilename($payment) . '"',
        ]);
    }

    public function indexApi(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('Administrator')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $this->buildPaymentsQuery($request);

        return response()->json([
            'data'  => $data['payments']->items(),
            'stats' => $data['stats'],
            'meta'  => [
                'current_page' => $data['payments']->currentPage(),
                'last_page'    => $data['payments']->lastPage(),
                'total'        => $data['payments']->total(),
            ],
        ]);
    }

    private function buildPaymentsQuery(Request $request): array
    {
        $query = Payment::withoutGlobalScopes()
            ->with([
                'institution:id,name',
                'subscription.plan:id,name',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('paypal_capture_id', 'ilike', $term)
                    ->orWhere('paypal_order_id', 'ilike', $term)
                    ->orWhereHas('institution', fn ($iq) => $iq->where('name', 'ilike', $term));
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->paginate(20)->withQueryString();

        $base = Payment::withoutGlobalScopes();

        $stats = [
            'total'     => (clone $base)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'pending'   => (clone $base)->where('status', 'pending')->count(),
            'failed'    => (clone $base)->where('status', 'failed')->count(),
            'refunded'  => (clone $base)->where('status', 'refunded')->count(),
            'revenue'   => (clone $base)->where('status', 'completed')->sum('amount'),
        ];

        $institutions = \App\Models\Institution::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('payments', 'stats', 'institutions');
    }
}
