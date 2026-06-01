{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Módulo de Membresías y Suscripciones — Gestión de planes,
 *                 asignación a instituciones y control de vencimientos (RF-03 / RF-12).
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 * @version        1.0.0
 * @creado         07/05/2026
 * @modificado     07/05/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 07/05/2026  | Rubén Alejandro   | Implementación inicial Membresías (RF-03/RF-12).
 */
--}}

@extends('layouts.app')

@section('title', 'Membresías - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/membresias.css') }}">
@endpush

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Membresías</h1>
                <p>Gestión de planes y suscripciones de instituciones</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline btn-md" onclick="abrirModal('modalPlanes')">
                    <i class="fas fa-tags"></i>
                    Ver planes
                </button>
                <button class="btn btn-primary btn-md" onclick="abrirModal('modalAsignar')">
                    <i class="fas fa-plus"></i>
                    Asignar membresía
                </button>
            </div>
        </div>
    </div>

    {{-- SUCCESS / ERROR --}}
    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('info'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#dbeafe;border-left:4px solid #3b82f6;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#1e40af;">
            <i class="fas fa-info-circle"></i><span>{{ session('info') }}</span>
        </div>
    @endif
    @if($errors->has('general'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#fee2e2;border-left:4px solid #DC3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#991b1b;">
            <i class="fas fa-exclamation-circle"></i><span>{{ $errors->first('general') }}</span>
        </div>
    @endif

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['active'] }}</span>
                <span class="kpi-label">Membresías activas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['expiring_soon'] }}</span>
                <span class="kpi-label">Por vencer (30 días)</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--danger">
            <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['expired'] }}</span>
                <span class="kpi-label">Expiradas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-tags"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_plans'] }}</span>
                <span class="kpi-label">Planes disponibles</span>
            </div>
        </div>
    </div>

    {{-- TABS --}}
    <div class="mod-tabs">
        <button class="mod-tab active" data-tab="todas">
            <i class="fas fa-list"></i> Todas
        </button>
        <button class="mod-tab" data-tab="active">
            <i class="fas fa-check-circle"></i> Activas
        </button>
        <button class="mod-tab" data-tab="expiring">
            <i class="fas fa-clock"></i> Por vencer
            @if($stats['expiring_soon'] > 0)
                <span class="tab-badge">{{ $stats['expiring_soon'] }}</span>
            @endif
        </button>
        <button class="mod-tab" data-tab="expired">
            <i class="fas fa-times-circle"></i> Expiradas
        </button>
    </div>

    {{-- TABLA --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-id-card"></i>
                Membresías registradas
            </h3>
            <div class="card-actions">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input"
                           placeholder="Buscar institución..."
                           id="buscarMembresia">
                </div>
                <select class="filter-select" id="filtroPlan">
                    <option value="">Todos los planes</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->name }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table" id="tablaMembresias">
                    <thead>
                        <tr>
                            <th>Institución</th>
                            <th>Plan</th>
                            <th>Inicio</th>
                            <th>Vencimiento</th>
                            <th>Máx. Alumnos</th>
                            <th>Máx. Aulas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                            @php
                                $daysLeft  = now()->diffInDays($sub->end_date, false);
                                $tabStatus = match(true) {
                                    $sub->status === 'active' && $daysLeft > 30  => 'active',
                                    $sub->status === 'active' && $daysLeft <= 30 => 'expiring',
                                    default                                       => 'expired',
                                };
                            @endphp
                            <tr data-tab="{{ $tabStatus }}" data-plan="{{ $sub->plan->name }}">
                                <td>
                                    <div class="inst-cell {{ $tabStatus === 'expired' ? 'inst-cell--inactive' : '' }}">
                                        <div class="inst-icon {{ $tabStatus === 'expired' ? 'inst-icon--inactive' : '' }}">
                                            @if($sub->institution->logo_url)
                                                <img src="{{ $sub->institution->logo_url }}"
                                                     style="width:28px;height:28px;object-fit:cover;border-radius:4px;">
                                            @else
                                                <i class="fas fa-university"></i>
                                            @endif
                                        </div>
                                        <span class="inst-nombre">{{ $sub->institution->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="plan-badge plan-{{ strtolower($sub->plan->name) }}">
                                        {{ $sub->plan->name }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($sub->start_date)->format('d/m/Y') }}</td>
                                <td class="{{ $tabStatus === 'expiring' ? 'fecha-alerta' : ($tabStatus === 'expired' ? 'fecha-vencida' : '') }}">
                                    {{ \Carbon\Carbon::parse($sub->end_date)->format('d/m/Y') }}
                                    @if($tabStatus === 'expiring')
                                        <span style="font-size:.75rem;color:#92400e;display:block;">
                                            {{ $daysLeft }} días restantes
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $sub->plan->max_students }}</td>
                                <td>{{ $sub->plan->max_classrooms }}</td>
                                <td>
                                    @if($tabStatus === 'active')
                                        <span class="status status-active">Activa</span>
                                    @elseif($tabStatus === 'expiring')
                                        <span class="status status-warning">Por vencer</span>
                                    @else
                                        <span class="status status-expired">Expirada</span>
                                    @endif
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn" title="Ver detalle"
                                        onclick="abrirDetalle(
                                            '{{ addslashes($sub->institution->name) }}',
                                            '{{ $sub->plan->name }}',
                                            '{{ \Carbon\Carbon::parse($sub->start_date)->format('d/m/Y') }}',
                                            '{{ \Carbon\Carbon::parse($sub->end_date)->format('d/m/Y') }}',
                                            {{ $sub->plan->max_students }},
                                            {{ $sub->plan->max_classrooms }},
                                            '{{ $tabStatus }}'
                                        )">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($tabStatus !== 'expired')
                                        <button class="action-btn {{ $sub->plan->isFree() ? 'warn' : ($tabStatus === 'expiring' ? 'warn' : '') }}"
                                                title="{{ $sub->plan->isFree() ? 'Actualizar a Pro' : 'Renovar o actualizar plan' }}"
                                                onclick="abrirActualizar('{{ $sub->institution_id }}', '{{ $sub->plan_id }}', '{{ addslashes($sub->institution->name) }}', '{{ $sub->plan->name }}', {{ $sub->plan->isFree() ? 'true' : 'false' }})">
                                            <i class="fas {{ $sub->plan->isFree() ? 'fa-arrow-up' : 'fa-redo' }}"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary)">
                                    <i class="fas fa-id-card" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                                    No hay membresías registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- MODAL: PLANES --}}
<div class="modal-overlay" id="modalPlanes">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Planes disponibles</h3>
                <p class="modal-subtitle">Catálogo de planes SaaS</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalPlanes')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="planes-grid">
                @foreach($plans as $plan)
                <div class="plan-card plan-card--{{ strtolower($plan->name) }}">
                    <div class="plan-header">
                        <span class="plan-nombre">{{ $plan->name }}</span>
                        @if(!$plan->isFree())
                            <span style="font-size:1.1rem;font-weight:700;color:var(--corp-orange)">
                                ${{ number_format($plan->price, 0) }}/mes
                            </span>
                        @else
                            <span style="font-size:1.1rem;font-weight:700;color:#28A745">Gratis</span>
                        @endif
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> {{ $plan->max_students }} alumnos máx.</li>
                        <li><i class="fas fa-check"></i> {{ $plan->max_classrooms }} aulas máx.</li>
                        <li><i class="fas fa-check"></i> Duración: {{ $plan->duration_months }} mes(es)</li>
                    </ul>
                </div>
                @endforeach
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalPlanes')">Cerrar</button>
        </div>
    </div>
</div>

{{-- MODAL: ASIGNAR --}}
<div class="modal-overlay" id="modalAsignar">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Asignar Membresía</h3>
                <p class="modal-subtitle">Selecciona institución y plan</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalAsignar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('membresias.upgrade') }}">
            @csrf
            <input type="hidden" name="intent" value="assign">
            <div class="modal-body">
                <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:1rem;">
                    Solo instituciones <strong>sin membresía activa</strong>. Para pasar de Basic a Pro, usa «Actualizar plan» en la tabla.
                </p>
                <div class="form-group">
                    <label class="form-label">Institución <span class="required">*</span></label>
                    @if($institutionsForAssign->isEmpty())
                        <p class="form-hint" style="color:var(--text-secondary);">
                            No hay instituciones disponibles: todas tienen una suscripción activa.
                        </p>
                    @else
                        <select name="institution_id" class="form-input" required>
                            <option value="">Selecciona una institución...</option>
                            @foreach($institutionsForAssign as $inst)
                                <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">Plan <span class="required">*</span></label>
                    <select name="plan_id" class="form-input" required>
                        <option value="">Selecciona un plan...</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->name }}
                                @if(!$plan->isFree()) — ${{ number_format($plan->price, 0) }}/mes @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalAsignar')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md" @disabled($institutionsForAssign->isEmpty())>
                    <i class="fas fa-save"></i>
                    Asignar membresía
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: ACTUALIZAR / RENOVAR --}}
<div class="modal-overlay" id="modalRenovar">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="renovarTitulo">Actualizar plan</h3>
                <p class="modal-subtitle" id="renovarSubtitulo">—</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalRenovar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('membresias.upgrade') }}">
            @csrf
            <input type="hidden" name="intent" value="change">
            <input type="hidden" name="institution_id" id="renovarInstId">
            <div class="modal-body">
                <p id="renovarAyuda" style="font-size:.85rem;color:var(--text-secondary);margin-bottom:1rem;"></p>
                <div class="form-group">
                    <label class="form-label">Plan</label>
                    <select name="plan_id" id="renovarPlanId" class="form-input" required>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}"
                                    data-free="{{ $plan->isFree() ? '1' : '0' }}"
                                    data-price="{{ $plan->price }}">
                                {{ $plan->name }}
                                @if(!$plan->isFree()) — ${{ number_format($plan->price, 0) }}/mes @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <p id="renovarPaypalNota" style="font-size:.85rem;color:var(--text-secondary);margin-top:.5rem;">
                    <i class="fab fa-paypal"></i>
                    Los planes de pago redirigen a PayPal.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalRenovar')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md" id="renovarSubmitBtn">
                    <i class="fas fa-arrow-up"></i>
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DETALLE --}}
<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="detalleTitulo">—</h3>
                <p class="modal-subtitle">Detalle de membresía</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalDetalle')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Plan</span>
                    <span class="detalle-value" id="detallePlan">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estado</span>
                    <span class="detalle-value" id="detalleEstado">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Inicio</span>
                    <span class="detalle-value" id="detalleInicio">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Vencimiento</span>
                    <span class="detalle-value" id="detalleVence">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Máx. alumnos</span>
                    <span class="detalle-value" id="detalleAlumnos">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Máx. aulas</span>
                    <span class="detalle-value" id="detalleAulas">—</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalDetalle')">Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ── Modales ───────────────────────────────────────────────────────────────
    function abrirModal(id) { document.getElementById(id).classList.add('active'); }
    function cerrarModal(id){ document.getElementById(id).classList.remove('active'); }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape')
            ['modalPlanes','modalAsignar','modalRenovar','modalDetalle'].forEach(cerrarModal);
    });

    // ── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.mod-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.mod-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const tabActiva = tab.dataset.tab;
            document.querySelectorAll('#tablaMembresias tbody tr').forEach(tr => {
                tr.style.display = (tabActiva === 'todas' || tr.dataset.tab === tabActiva) ? '' : 'none';
            });
        });
    });

    // ── Búsqueda ──────────────────────────────────────────────────────────────
    document.getElementById('buscarMembresia').addEventListener('input', function () {
        const texto = this.value.toLowerCase();
        document.querySelectorAll('#tablaMembresias tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    document.getElementById('filtroPlan').addEventListener('change', function () {
        const plan = this.value;
        document.querySelectorAll('#tablaMembresias tbody tr').forEach(tr => {
            tr.style.display = (!plan || tr.dataset.plan === plan) ? '' : 'none';
        });
    });

    // ── Detalle ───────────────────────────────────────────────────────────────
    function abrirDetalle(nombre, plan, inicio, vence, alumnos, aulas, status) {
        document.getElementById('detalleTitulo').textContent  = nombre;
        document.getElementById('detallePlan').textContent    = plan;
        document.getElementById('detalleInicio').textContent  = inicio;
        document.getElementById('detalleVence').textContent   = vence;
        document.getElementById('detalleAlumnos').textContent = alumnos;
        document.getElementById('detalleAulas').textContent   = aulas;
        const estados = { active: 'Activa', expiring: 'Por vencer', expired: 'Expirada' };
        document.getElementById('detalleEstado').textContent  = estados[status] ?? status;
        abrirModal('modalDetalle');
    }

    // ── Actualizar plan / renovar ─────────────────────────────────────────────
    function abrirActualizar(instId, planId, nombre, planNombre, esBasic) {
        document.getElementById('renovarTitulo').textContent    = esBasic ? 'Actualizar a Pro' : 'Renovar membresía';
        document.getElementById('renovarSubtitulo').textContent = `${nombre} — Plan actual: ${planNombre}`;
        document.getElementById('renovarInstId').value          = instId;
        document.getElementById('renovarAyuda').textContent     = esBasic
            ? 'Puedes actualizar de Basic a Pro en cualquier momento. Solo puede haber una suscripción activa por institución.'
            : 'Renueva el mismo plan o contacta soporte para cambios. No se permiten dos suscripciones Pro activas.';

        const select = document.getElementById('renovarPlanId');
        for (let opt of select.options) {
            const isFree = opt.dataset.free === '1';
            if (esBasic) {
                opt.hidden = isFree;
                opt.disabled = isFree;
            } else {
                opt.hidden = false;
                opt.disabled = false;
            }
            if (opt.value === planId && !esBasic) { opt.selected = true; }
            if (esBasic && opt.dataset.free !== '1') { opt.selected = true; }
        }

        const selected = select.options[select.selectedIndex];
        const submitBtn = document.getElementById('renovarSubmitBtn');
        const isPaid = selected && selected.dataset.free !== '1';
        submitBtn.innerHTML = isPaid
            ? '<i class="fab fa-paypal"></i> Pagar con PayPal'
            : '<i class="fas fa-check"></i> Confirmar';
        document.getElementById('renovarPaypalNota').style.display = isPaid ? '' : 'none';

        select.onchange = () => {
            const opt = select.options[select.selectedIndex];
            const paid = opt.dataset.free !== '1';
            submitBtn.innerHTML = paid
                ? '<i class="fab fa-paypal"></i> Pagar con PayPal'
                : '<i class="fas fa-check"></i> Confirmar';
            document.getElementById('renovarPaypalNota').style.display = paid ? '' : 'none';
        };

        abrirModal('modalRenovar');
    }
</script>
@endpush

@endsection