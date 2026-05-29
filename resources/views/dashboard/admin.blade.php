{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Dashboard Administrador — KPIs institucionales, membresías,
 *                 estado del sistema SaaS y actividad reciente global.
 * @autor          G.A.M.A. Solutions
 * @version        1.1.0
 * @creado         26/05/2026
 */
--}}

@extends('layouts.app')

@section('title', 'Dashboard Administrador - GAMA Solutions')

@section('content')
@php
    $sub = $stats['active_subscription'] ?? null;
    $hasAlerts = ! $sub
        || $stats['pending_justifications'] > 0
        || ($sub && $sub->days_remaining <= 15)
        || ($sub && $stats['plan_max_classrooms'] > 0 && $stats['plan_used_classrooms'] >= $stats['plan_max_classrooms']);
    $actionLabels = [
        'create' => 'Creación',
        'update' => 'Actualización',
        'delete' => 'Eliminación',
    ];
    $classroomPct = $stats['plan_max_classrooms'] > 0
        ? min(100, round($stats['plan_used_classrooms'] / $stats['plan_max_classrooms'] * 100))
        : 0;
    $studentPct = $stats['plan_max_students'] > 0
        ? min(100, round($stats['plan_used_students'] / $stats['plan_max_students'] * 100))
        : 0;
@endphp
<div class="main-content">

  <div class="page-header">
    <div class="header-content">
      <div class="header-text">
        <h1>Dashboard</h1>
        <p>
          Bienvenido, {{ $user->first_name }}
          @if($stats['institution_name'])
            &nbsp;·&nbsp; {{ $stats['institution_name'] }}
          @endif
        </p>
      </div>
    </div>
    <div class="header-actions">
      <span class="header-date">
        <i class="fas fa-calendar-alt"></i>
        <span id="fechaHoy"></span>
      </span>
    </div>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fas fa-chalkboard"></i></div>
      <div class="kpi-content">
        <span class="kpi-value">{{ $stats['total_classrooms'] }}</span>
        <span class="kpi-label">Aulas activas</span>
      </div>
    </div>
    <div class="kpi-card kpi-card--success">
      <div class="kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
      <div class="kpi-content">
        <span class="kpi-value">{{ $stats['total_teachers'] }}</span>
        <span class="kpi-label">Docentes registrados</span>
      </div>
    </div>
    <div class="kpi-card kpi-card--warning">
      <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
      <div class="kpi-content">
        <span class="kpi-value">{{ $stats['total_students'] }}</span>
        <span class="kpi-label">Alumnos inscritos</span>
      </div>
    </div>
    <div class="kpi-card kpi-card--orange">
      <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
      <div class="kpi-content">
        <span class="kpi-value">{{ $stats['pending_justifications'] }}</span>
        <span class="kpi-label">Justificantes pendientes</span>
      </div>
    </div>
  </div>

  <div class="charts-grid">

    <div class="card chart-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-id-card"></i> Plan de membresía activo</h3>
      </div>
      <div class="card-body">
        @if($sub)
          <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);gap:1rem;">
            <div class="kpi-card">
              <div class="kpi-icon"><i class="fas fa-tag"></i></div>
              <div class="kpi-content">
                <span class="kpi-value" style="font-size:1.1rem;">{{ $sub->plan->name }}</span>
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
                <span class="kpi-value">{{ $sub->days_remaining }}</span>
                <span class="kpi-label">Días restantes</span>
              </div>
            </div>
          </div>
          <div style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
            <span style="font-size:.85rem;color:var(--text-secondary);">
              Vigente hasta {{ $sub->end_date->format('d/m/Y') }}
            </span>
            <a href="{{ route('membresias.index') }}" class="btn btn-primary btn-sm">
              <i class="fas fa-arrow-up"></i> Gestionar plan
            </a>
          </div>
        @else
          <div style="text-align:center;padding:2rem;color:var(--text-secondary);">
            <i class="fas fa-exclamation-circle" style="font-size:2rem;color:#DC3545;margin-bottom:.5rem;"></i>
            <p>Sin suscripción activa en esta institución</p>
            <a href="{{ route('membresias.index') }}" class="btn btn-primary btn-sm">Activar plan</a>
          </div>
        @endif
      </div>
    </div>

    <div class="card chart-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Uso del plan y actividad</h3>
      </div>
      <div class="card-body">
        @if($sub)
          <div style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.35rem;">
              <span>Aulas utilizadas</span>
              <strong>{{ $stats['plan_used_classrooms'] }} / {{ $stats['plan_max_classrooms'] }}</strong>
            </div>
            <div style="height:8px;background:#e3edf5;border-radius:4px;overflow:hidden;">
              <div style="height:100%;width:{{ $classroomPct }}%;background:#134474;border-radius:4px;"></div>
            </div>
          </div>
          <div style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.35rem;">
              <span>Alumnos inscritos</span>
              <strong>{{ $stats['plan_used_students'] }} / {{ $stats['plan_max_students'] }}</strong>
            </div>
            <div style="height:8px;background:#e3edf5;border-radius:4px;overflow:hidden;">
              <div style="height:100%;width:{{ $studentPct }}%;background:#F28B2C;border-radius:4px;"></div>
            </div>
          </div>
        @else
          <p style="margin:0 0 1rem;color:#6b7280;font-size:.9rem;">
            Contrata un plan para ver el uso de cupos de aulas y alumnos.
          </p>
        @endif
        <div class="kpi-card" style="margin:0;">
          <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
          <div class="kpi-content">
            <span class="kpi-value">{{ $stats['sessions_this_month'] }}</span>
            <span class="kpi-label">Sesiones registradas este mes</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel-grid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bolt"></i> Accesos rápidos</h3>
      </div>
      <div class="card-body accesos-body">
        <a href="{{ route('aulas.index') }}" class="acceso-btn">
          <div class="acceso-icon"><i class="fas fa-chalkboard"></i></div>
          <div class="acceso-text">
            <span class="acceso-nombre">Aulas</span>
            <span class="acceso-desc">Ver aulas y detalle por materia</span>
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
            <span class="acceso-nombre">Reportes</span>
            <span class="acceso-desc">Exportar matrices y resúmenes</span>
          </div>
          <i class="fas fa-chevron-right acceso-arrow"></i>
        </a>
        <a href="{{ route('bitacora.index') }}" class="acceso-btn">
          <div class="acceso-icon acceso-icon--e"><i class="fas fa-history"></i></div>
          <div class="acceso-text">
            <span class="acceso-nombre">Bitácora de auditoría</span>
            <span class="acceso-desc">Historial completo de cambios</span>
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
        @if($sub && $sub->days_remaining <= 15)
          <div class="alerta alerta--warning">
            <div class="alerta-icon"><i class="fas fa-id-card"></i></div>
            <div class="alerta-content">
              <span class="alerta-titulo">Membresía por vencer</span>
              <span class="alerta-desc">Vence en {{ $sub->days_remaining }} días — Renueva para evitar interrupciones</span>
            </div>
            <span class="alerta-aula">Admin</span>
          </div>
        @endif
        @if($stats['pending_justifications'] > 0)
          <div class="alerta alerta--warning">
            <div class="alerta-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="alerta-content">
              <span class="alerta-titulo">{{ $stats['pending_justifications'] }} justificante(s) sin dictaminar</span>
              <span class="alerta-desc">Pendientes de revisión en las aulas de la institución</span>
            </div>
            <span class="alerta-aula">Institución</span>
          </div>
        @endif
        @if($sub && $stats['plan_used_classrooms'] >= $stats['plan_max_classrooms'])
          <div class="alerta alerta--warning">
            <div class="alerta-icon"><i class="fas fa-chalkboard"></i></div>
            <div class="alerta-content">
              <span class="alerta-titulo">Límite de aulas alcanzado</span>
              <span class="alerta-desc">{{ $stats['plan_used_classrooms'] }} de {{ $stats['plan_max_classrooms'] }} aulas en uso</span>
            </div>
            <span class="alerta-aula">Plan</span>
          </div>
        @endif
        @if(! $sub)
          <div class="alerta alerta--danger">
            <div class="alerta-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="alerta-content">
              <span class="alerta-titulo">Sin suscripción activa</span>
              <span class="alerta-desc">Activa un plan para habilitar todas las funciones</span>
            </div>
            <span class="alerta-aula">Admin</span>
          </div>
        @endif
        @unless($hasAlerts)
          <div style="text-align:center;padding:1.5rem;color:var(--text-secondary);">
            <i class="fas fa-check-circle" style="color:#28A745;font-size:1.5rem;"></i>
            <p style="margin-top:.5rem;">Sin alertas activas</p>
          </div>
        @endunless
      </div>
    </div>
  </div>

  <div class="card table-card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-history"></i> Actividad reciente</h3>
      <div class="card-actions">
        <a href="{{ route('bitacora.index') }}" class="btn btn-outline btn-sm">
          <i class="fas fa-external-link-alt"></i> Ver bitácora
        </a>
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
            @forelse($recentActivity as $log)
              @php
                $actor = $log->user
                    ? trim($log->user->first_name.' '.$log->user->last_name)
                    : 'Sistema';
                if ($actor === '' && $log->user) {
                    $actor = $log->user->email;
                }
                $actionClass = match($log->action) {
                    'create' => 'status-active',
                    'delete' => 'status-closed',
                    default => 'status-open',
                };
              @endphp
              <tr>
                <td>
                  <span class="cell-nombre">{{ $actor }}</span>
                  @if($log->user?->email)
                    <span class="cell-grupo" style="display:block;font-size:.75rem;color:#6b7280;">{{ $log->user->email }}</span>
                  @endif
                </td>
                <td>{{ $log->entity }}</td>
                <td>
                  <span class="status {{ $actionClass }}">
                    {{ $actionLabels[$log->action] ?? $log->action }}
                  </span>
                </td>
                <td>{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align:center;padding:2rem;color:#6b7280;">
                  No hay registros de auditoría para esta institución.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  document.getElementById('fechaHoy').textContent =
    new Date().toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

  document.querySelectorAll('.sortable').forEach(header => {
    header.addEventListener('click', () => {
      const tbody = document.querySelector('#dataTable tbody');
      const rows  = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.children.length > 1);
      if (!rows.length) return;
      const isAsc = header.classList.contains('sort-asc');
      document.querySelectorAll('.sortable').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
      header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
      const idx = Array.from(header.parentElement.children).indexOf(header);
      rows.sort((a, b) => {
        const av = a.children[idx].textContent.trim();
        const bv = b.children[idx].textContent.trim();
        return isAsc ? bv.localeCompare(av) : av.localeCompare(bv);
      });
      rows.forEach(r => tbody.appendChild(r));
    });
  });
</script>
@endpush
