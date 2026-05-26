{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Dashboard Administrador — KPIs institucionales, membresías,
 *                 estado del sistema SaaS y actividad reciente global.
 * @autor          G.A.M.A. Solutions
 * @version        1.0.0
 * @creado         26/05/2026
 */
--}}

@extends('layouts.app')

@section('title', 'Dashboard Administrador - GAMA Solutions')

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Dashboard</h1>
                <p>Bienvenido, {{ $user->first_name }} &nbsp;·&nbsp; Panel de Administración Global</p>
            </div>
        </div>
        <div class="header-actions">
            <span class="header-date">
                <i class="fas fa-calendar-alt"></i>
                <span id="fechaHoy"></span>
            </span>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">
                <i class="fas fa-chalkboard"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_classrooms'] }}</span>
                <span class="kpi-label">Aulas activas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_teachers'] }}</span>
                <span class="kpi-label">Docentes registrados</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_students'] }}</span>
                <span class="kpi-label">Alumnos inscritos</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--orange">
            <div class="kpi-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending_justifications'] }}</span>
                <span class="kpi-label">Justificantes pendientes</span>
            </div>
        </div>
    </div>

    {{-- CHARTS GRID --}}
    <div class="charts-grid">

        {{-- Plan activo --}}
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-id-card"></i>
                    Plan de membresía activo
                </h3>
            </div>
            <div class="card-body">
                @if($stats['active_subscription'])
                    @php $sub = $stats['active_subscription']; @endphp
                    <div class="kpi-grid" style="grid-template-columns: repeat(3,1fr); gap:1rem;">
                        <div class="kpi-card">
                            <div class="kpi-icon"><i class="fas fa-tag"></i></div>
                            <div class="kpi-content">
                                <span class="kpi-value">{{ $sub->plan->name }}</span>
                                <span class="kpi-label">Plan actual</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-card--success">
                            <div class="kpi-icon"><i class="fas fa-users"></i></div>
                            <div class="kpi-content">
                                <span class="kpi-value">{{ $sub->plan->max_students }}</span>
                                <span class="kpi-label">Máx. alumnos</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-card--warning">
                            <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="kpi-content">
                                <span class="kpi-value">{{ $sub->days_remaining }} días</span>
                                <span class="kpi-label">Vigencia restante</span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:1rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:.4rem;">
                            <span style="font-size:.85rem; color:var(--text-secondary)">Vence el {{ \Carbon\Carbon::parse($sub->end_date)->format('d/m/Y') }}</span>
                            <a href="{{ route('membresias.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-up"></i> Gestionar plan
                            </a>
                        </div>
                    </div>
                @else
                    <div style="text-align:center; padding:2rem; color:var(--text-secondary)">
                        <i class="fas fa-exclamation-circle" style="font-size:2rem; color:#DC3545; margin-bottom:.5rem;"></i>
                        <p>Sin suscripción activa</p>
                        <a href="{{ route('membresias.index') }}" class="btn btn-primary btn-sm">Activar plan</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Distribución A/F/J --}}
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    Distribución A / F / J (global)
                </h3>
            </div>
            <div class="card-body">
                <div class="pie-chart-placeholder">
                    <div class="pie-donut">
                        <svg viewBox="0 0 120 120" class="donut-svg">
                            <circle class="donut-ring" cx="60" cy="60" r="45"/>
                            <circle class="donut-seg seg-a" cx="60" cy="60" r="45"
                                stroke-dasharray="212 283" stroke-dashoffset="0"/>
                            <circle class="donut-seg seg-f" cx="60" cy="60" r="45"
                                stroke-dasharray="57 283" stroke-dashoffset="-212"/>
                            <circle class="donut-seg seg-j" cx="60" cy="60" r="45"
                                stroke-dasharray="14 283" stroke-dashoffset="-269"/>
                        </svg>
                        <div class="donut-centro">
                            <span class="donut-pct">75%</span>
                            <span class="donut-sub">asist.</span>
                        </div>
                    </div>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background:#28A745"></span>
                            <span>Asistencias (75%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background:#DC3545"></span>
                            <span>Faltas (20%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background:#F28B2C"></span>
                            <span>Justificantes (5%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PANEL: ACCESOS RÁPIDOS + ALERTAS --}}
    <div class="panel-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Accesos rápidos</h3>
            </div>
            <div class="card-body accesos-body">
                <a href="{{ route('instituciones.index') }}" class="acceso-btn">
                    <div class="acceso-icon"><i class="fas fa-building"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Instituciones</span>
                        <span class="acceso-desc">Gestionar instituciones registradas</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('membresias.index') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--b"><i class="fas fa-id-card"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Membresías</span>
                        <span class="acceso-desc">Planes y suscripciones activas</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('admin.edicion') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--c"><i class="fas fa-edit"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Edición administrativa</span>
                        <span class="acceso-desc">Correcciones con audit log</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('reportes.index') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--d"><i class="fas fa-chart-bar"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Reportes globales</span>
                        <span class="acceso-desc">Exportar matrices y resúmenes</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell"></i> Alertas del sistema</h3>
                @if($stats['pending_justifications'] > 0)
                    <span class="badge-num-header">{{ $stats['pending_justifications'] }}</span>
                @endif
            </div>
            <div class="card-body alertas-body">
                @if($stats['active_subscription'] && $stats['active_subscription']->days_remaining <= 15)
                    <div class="alerta alerta--warning">
                        <div class="alerta-icon"><i class="fas fa-id-card"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">Membresía por vencer</span>
                            <span class="alerta-desc">Vence en {{ $stats['active_subscription']->days_remaining }} días — Renueva para evitar interrupciones</span>
                        </div>
                        <span class="alerta-aula">Admin</span>
                    </div>
                @endif
                @if($stats['pending_justifications'] > 0)
                    <div class="alerta alerta--warning">
                        <div class="alerta-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">{{ $stats['pending_justifications'] }} justificantes sin dictaminar</span>
                            <span class="alerta-desc">Pendientes de revisión</span>
                        </div>
                        <span class="alerta-aula">Global</span>
                    </div>
                @endif
                @if(!$stats['active_subscription'])
                    <div class="alerta alerta--danger">
                        <div class="alerta-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">Sin suscripción activa</span>
                            <span class="alerta-desc">El sistema está en modo solo lectura</span>
                        </div>
                        <span class="alerta-aula">Admin</span>
                    </div>
                @endif
                @if(!$stats['active_subscription'] && $stats['pending_justifications'] == 0)
                    <div style="text-align:center; padding:1.5rem; color:var(--text-secondary)">
                        <i class="fas fa-check-circle" style="color:#28A745; font-size:1.5rem;"></i>
                        <p style="margin-top:.5rem;">Sin alertas activas</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TABLA: ACTIVIDAD RECIENTE --}}
    <div class="card table-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i> Actividad reciente</h3>
            <div class="card-actions">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Buscar..." id="searchInput">
                </div>
                <button class="btn btn-secondary btn-sm" id="toggleFilters">
                    <i class="fas fa-filter"></i><span>Filtros</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="dynamic-table" id="dataTable">
                    <thead>
                        <tr>
                            <th class="sortable">Usuario <i class="fas fa-sort sort-icon"></i></th>
                            <th class="sortable">Entidad <i class="fas fa-sort sort-icon"></i></th>
                            <th class="sortable">Acción <i class="fas fa-sort sort-icon"></i></th>
                            <th class="sortable">Fecha <i class="fas fa-sort sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>admin@gama.com</td>
                            <td>institutions</td>
                            <td><span class="status status-active">create</span></td>
                            <td>{{ now()->format('d/m/Y H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('fechaHoy').textContent =
        new Date().toLocaleDateString('es-MX', opciones);

    // Sorting
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', () => {
            const tbody = document.querySelector('#dataTable tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = header.classList.contains('sort-asc');
            document.querySelectorAll('.sortable').forEach(h => h.classList.remove('sort-asc','sort-desc'));
            header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
            const idx = Array.from(header.parentElement.children).indexOf(header);
            rows.sort((a,b) => {
                const av = a.children[idx].textContent.trim();
                const bv = b.children[idx].textContent.trim();
                return isAsc ? bv.localeCompare(av) : av.localeCompare(bv);
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });
</script>
@endpush