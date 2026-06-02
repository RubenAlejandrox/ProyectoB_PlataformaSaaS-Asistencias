<?php

/**
 * @descripcion  Servicio de dominio Invoice: encapsula reglas de negocio reutilizables.
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

namespace App\Services;

use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Genera el folio único de factura para un pago.
     *
     * @param Payment $payment Pago completado o pendiente
     * @return string Folio con formato FACT-{año}-{sufijo}
     */
    public function buildFolio(Payment $payment): string
    {
        $year = $payment->paid_at?->format('Y') ?? $payment->created_at->format('Y');
        $suffix = strtoupper(substr(str_replace('-', '', $payment->id), 0, 8));

        return "FACT-{$year}-{$suffix}";
    }

    /**
     * Arma el arreglo de datos para la vista PDF o pantalla de factura.
     *
     * @param Payment $payment Pago con relaciones institution y subscription.plan
     * @return array<string, mixed> Datos del emisor, cliente, plan, montos y etiquetas de estado
     */
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

    /**
     * Renderiza la factura del pago como documento PDF binario.
     *
     * @param Payment $payment Pago a facturar
     * @return string Contenido binario del PDF generado con Dompdf
     */
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

    /**
     * Nombre de archivo sugerido para descargar la factura en PDF.
     *
     * @param Payment $payment Pago del cual se deriva el folio
     * @return string Nombre con extensión .pdf (slug del folio)
     */
    public function downloadFilename(Payment $payment): string
    {
        return Str::slug($this->buildFolio($payment)) . '.pdf';
    }
}
