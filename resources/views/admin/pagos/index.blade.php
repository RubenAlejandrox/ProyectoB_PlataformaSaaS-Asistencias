@extends('layouts.app')

@section('title', 'Historial de Pagos - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/membresias.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Historial de Pagos</h1>
                <p>Transacciones PayPal registradas en la plataforma</p>
            </div>
            <div class="header-actions" style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <a id="btnDescargarFactura"
                   href="#"
                   class="btn btn-primary btn-md"
                   style="opacity:.45;pointer-events:none;"
                   title="Selecciona un pago completado de la tabla">
                    <i class="fas fa-file-pdf"></i> Descargar factura
                </a>
                <a href="{{ route('membresias.index') }}" class="btn btn-outline btn-md">
                    <i class="fas fa-id-card"></i> Membresías
                </a>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total transacciones</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['completed'] }}</span>
                <span class="kpi-label">Completados</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending'] }}</span>
                <span class="kpi-label">Pendientes</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--danger">
            <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['failed'] }}</span>
                <span class="kpi-label">Fallidos</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">${{ number_format($stats['revenue'], 2) }}</span>
                <span class="kpi-label">Ingresos (completados)</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <h3 class="card-title"><i class="fas fa-credit-card"></i> Transacciones</h3>
                <p style="margin:.35rem 0 0;font-size:.8rem;color:#6b7280;">
                    Haz clic en una fila con estado <strong>Completado</strong> para seleccionarla y descargar su factura.
                </p>
            </div>
            <form method="GET" action="{{ route('admin.pagos.index') }}" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Buscar</label>
                    <input type="text" name="search" class="search-input" value="{{ request('search') }}"
                           placeholder="Institución, capture ID...">
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Institución</label>
                    <select name="institution_id" class="filter-select">
                        <option value="">Todas</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}" @selected(request('institution_id') === $inst->id)>
                                {{ $inst->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Estado</label>
                    <select name="status" class="filter-select">
                        <option value="">Todos</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completado</option>
                        <option value="failed" @selected(request('status') === 'failed')>Fallido</option>
                        <option value="refunded" @selected(request('status') === 'refunded')>Reembolsado</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Desde</label>
                    <input type="date" name="from_date" class="filter-select" value="{{ request('from_date') }}">
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Hasta</label>
                    <input type="date" name="to_date" class="filter-select" value="{{ request('to_date') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline btn-sm">Limpiar</a>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Institución</th>
                            <th>Plan</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Método</th>
                            <th>PayPal Capture ID</th>
                            <th>PayPal Order ID</th>
                            <th>Pagado el</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $statusClass = match($payment->status) {
                                    'completed' => 'status-active',
                                    'pending'   => 'status-justified',
                                    'failed'    => 'status-absent',
                                    'refunded'  => 'status-justified',
                                    default     => '',
                                };
                                $statusLabel = match($payment->status) {
                                    'completed' => 'Completado',
                                    'pending'   => 'Pendiente',
                                    'failed'    => 'Fallido',
                                    'refunded'  => 'Reembolsado',
                                    default     => $payment->status,
                                };
                            @endphp
                            <tr class="payment-row {{ $payment->status === 'completed' ? 'payment-row--selectable' : '' }}"
                                data-payment-id="{{ $payment->id }}"
                                data-invoice-url="{{ $payment->status === 'completed' ? route('admin.pagos.factura', $payment) : '' }}"
                                data-status="{{ $payment->status }}">
                                <td>{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $payment->institution?->name ?? '—' }}</td>
                                <td>{{ $payment->subscription?->plan?->name ?? '—' }}</td>
                                <td>
                                    <strong>${{ number_format($payment->amount, 2) }}</strong>
                                    <span style="font-size:.75rem;color:#6b7280;"> {{ $payment->currency }}</span>
                                </td>
                                <td>
                                    <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>{{ strtoupper($payment->payment_method ?? '—') }}</td>
                                <td>
                                    @if($payment->paypal_capture_id)
                                        <code style="font-size:.75rem;word-break:break-all;">{{ $payment->paypal_capture_id }}</code>
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->paypal_order_id)
                                        <code style="font-size:.75rem;word-break:break-all;">{{ $payment->paypal_order_id }}</code>
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>
                                <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:2rem;color:#6b7280;">
                                    No hay pagos registrados{{ request()->any() ? ' con los filtros aplicados' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div style="padding:1rem;">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .payment-row--selectable { cursor: pointer; transition: background .15s; }
    .payment-row--selectable:hover { background: #f2f7fb !important; }
    .payment-row--selected { background: #eaf3fb !important; outline: 2px solid #134474; outline-offset: -2px; }
    .payment-row--selectable td:first-child { position: relative; }
    .payment-row--selected td:first-child::before {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        left: 6px;
        top: 50%;
        transform: translateY(-50%);
        color: #134474;
        font-size: .75rem;
    }
    .payment-row--selected td:first-child { padding-left: 1.6rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const btn = document.getElementById('btnDescargarFactura');
    const rows = document.querySelectorAll('.payment-row--selectable');
    let selectedRow = null;

    function clearSelection() {
        rows.forEach(r => r.classList.remove('payment-row--selected'));
        selectedRow = null;
        btn.href = '#';
        btn.style.opacity = '0.45';
        btn.style.pointerEvents = 'none';
        btn.title = 'Selecciona un pago completado de la tabla';
    }

    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;

            if (selectedRow === row) {
                clearSelection();
                return;
            }

            rows.forEach(r => r.classList.remove('payment-row--selected'));
            row.classList.add('payment-row--selected');
            selectedRow = row;

            const url = row.dataset.invoiceUrl;
            if (url) {
                btn.href = url;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                btn.title = 'Descargar factura PDF del pago seleccionado';
            }
        });
    });

    btn.addEventListener('click', function (e) {
        if (!selectedRow || btn.href === '#') {
            e.preventDefault();
            alert('Selecciona un pago con estado Completado en la tabla.');
        }
    });
})();
</script>
@endpush
@endsection
