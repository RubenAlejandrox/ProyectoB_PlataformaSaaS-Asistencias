<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $folio }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.45;
        }
        .page { padding: 36px 42px; }
        .header {
            border-bottom: 3px solid #134474;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .issuer-name {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #134474;
            text-transform: uppercase;
        }
        .issuer-tagline {
            font-size: 10px;
            color: #5f86a6;
            margin-top: 4px;
        }
        .issuer-meta {
            margin-top: 10px;
            font-size: 10px;
            color: #444;
        }
        .invoice-title-row {
            display: table;
            width: 100%;
            margin-bottom: 22px;
        }
        .invoice-title-row > div {
            display: table-cell;
            vertical-align: top;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #134474;
            text-align: right;
        }
        .folio {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
            margin-top: 6px;
        }
        .dates {
            font-size: 10px;
            text-align: right;
            color: #555;
            margin-top: 4px;
        }
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 22px;
        }
        .party-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
        }
        .party-box + .party-box { border-left: none; }
        .party-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .party-name {
            font-size: 12px;
            font-weight: bold;
            color: #134474;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #134474;
            color: #fff;
            font-size: 10px;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
        }
        table.items td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            font-size: 10px;
        }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .totals {
            width: 280px;
            margin-left: auto;
            border: 1px solid #134474;
        }
        .totals-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row:last-child {
            border-bottom: none;
            background: #134474;
            color: #fff;
            font-weight: bold;
        }
        .totals-row span {
            display: table-cell;
            padding: 10px 12px;
        }
        .payment-ref {
            margin-top: 20px;
            padding: 12px 14px;
            background: #f2f7fb;
            border-left: 4px solid #134474;
            font-size: 10px;
        }
        .payment-ref strong { color: #134474; }
        .footer {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #d1fae5;
            color: #065f46;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="issuer-name">{{ $issuer_name }}</div>
        <div class="issuer-tagline">Plataforma de Control de Asistencias — Servicios SaaS</div>
        <div class="issuer-meta">
            @if($issuer_rfc)<strong>RFC:</strong> {{ $issuer_rfc }} · @endif
            {{ $issuer_address }}
            @if($issuer_email) · {{ $issuer_email }}@endif
        </div>
    </div>

    <div class="invoice-title-row">
        <div></div>
        <div>
            <div class="invoice-title">COMPROBANTE DE PAGO / FACTURA</div>
            <div class="folio">Folio: {{ $folio }}</div>
            <div class="dates">
                Fecha de emisión: {{ $issued_at->format('d/m/Y H:i') }}<br>
                Fecha de registro: {{ $payment->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="parties">
        <div class="party-box">
            <div class="party-label">Emisor</div>
            <div class="party-name">{{ $issuer_name }}</div>
            @if($issuer_rfc)<div style="margin-top:4px;">RFC: {{ $issuer_rfc }}</div>@endif
        </div>
        <div class="party-box">
            <div class="party-label">Receptor (institución)</div>
            <div class="party-name">{{ $client_name }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:8%;">Cant.</th>
                <th style="width:52%;">Descripción</th>
                <th style="width:20%;" class="text-right">Precio unitario</th>
                <th style="width:20%;" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    Membresía — Plan {{ $plan_name }}<br>
                    <span style="color:#6b7280;">Suscripción a plataforma GAMA SOLUTIONS</span>
                </td>
                <td class="text-right">${{ number_format($subtotal, 2) }} {{ $currency }}</td>
                <td class="text-right">${{ number_format($total, 2) }} {{ $currency }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal</span>
            <span class="text-right">${{ number_format($subtotal, 2) }} {{ $currency }}</span>
        </div>
        <div class="totals-row">
            <span>IVA (incluido en precio)</span>
            <span class="text-right">—</span>
        </div>
        <div class="totals-row">
            <span>TOTAL</span>
            <span class="text-right">${{ number_format($total, 2) }} {{ $currency }}</span>
        </div>
    </div>

    <div class="payment-ref">
        <strong>Datos del pago</strong><br>
        Estado: <span class="status-badge">{{ $status_label }}</span><br>
        Método: {{ strtoupper($payment->payment_method ?? 'PAYPAL') }}<br>
        @if($payment->paypal_capture_id)
            PayPal Capture ID: {{ $payment->paypal_capture_id }}<br>
        @endif
        @if($payment->paypal_order_id)
            PayPal Order ID: {{ $payment->paypal_order_id }}<br>
        @endif
        @if($payment->paid_at)
            Fecha de pago: {{ $payment->paid_at->format('d/m/Y H:i') }}
        @endif
    </div>

    <div class="footer">
        Documento generado electrónicamente por {{ $issuer_name }}.<br>
        Este comprobante respalda la transacción registrada en el sistema de pagos de la plataforma.
    </div>
</div>
</body>
</html>
