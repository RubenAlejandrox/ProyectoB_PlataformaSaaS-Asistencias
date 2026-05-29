<?php

namespace App\Services;

use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

class InvoiceService
{
    public function buildFolio(Payment $payment): string
    {
        $year = $payment->paid_at?->format('Y') ?? $payment->created_at->format('Y');
        $suffix = strtoupper(substr(str_replace('-', '', $payment->id), 0, 8));

        return "FACT-{$year}-{$suffix}";
    }

    public function invoiceData(Payment $payment): array
    {
        $payment->loadMissing(['institution', 'subscription.plan']);

        $statusLabel = match ($payment->status) {
            'completed' => 'Pagado',
            'pending'   => 'Pendiente',
            'failed'    => 'Fallido',
            'refunded'  => 'Reembolsado',
            default     => $payment->status,
        };

        return [
            'payment'       => $payment,
            'folio'         => $this->buildFolio($payment),
            'issued_at'     => $payment->paid_at ?? $payment->created_at,
            'issuer_name'   => config('invoice.issuer_name', 'GAMA SOLUTIONS'),
            'issuer_rfc'    => config('invoice.issuer_rfc', ''),
            'issuer_address'=> config('invoice.issuer_address', 'México'),
            'issuer_email'  => config('invoice.issuer_email', 'contacto@gamasolutions.com'),
            'client_name'   => $payment->institution?->name ?? 'Cliente',
            'plan_name'     => $payment->subscription?->plan?->name ?? 'Suscripción',
            'status_label'  => $statusLabel,
            'subtotal'      => $payment->amount,
            'total'         => $payment->amount,
            'currency'      => $payment->currency,
        ];
    }

    public function renderPdf(Payment $payment): string
    {
        $html = view('admin.pagos.factura-pdf', $this->invoiceData($payment))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function downloadFilename(Payment $payment): string
    {
        return Str::slug($this->buildFolio($payment)) . '.pdf';
    }
}
