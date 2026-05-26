{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * @descripcion    Dashboard Docente — Mis aulas, sesiones activas,
 *                 justificantes pendientes y semáforo de alumnos en riesgo.
 * @version        1.0.0
 * @creado         26/05/2026
 */
--}}

@extends('layouts.app')

@section('title', 'Dashboard Docente - GAMA Solutions')

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Dashboard</h1>
                <p>Bienvenido, {{ $user->first_name }} &nbsp;·&nbsp; Panel del Docente</p>
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
            <div class="kpi-icon"><i class="fas fa-chalkboard"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_classrooms'] }}</span>
                <span class="kpi-label">Mis aulas activas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_students'] }}</span>
                <span class="kpi-label">Alumnos inscritos</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['at_risk_students'] }}</span>
                <span class="kpi-label">Faltas acumuladas</span>
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

    {{-- MIS AULAS --}}
    <div class="card table-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chalkboard"></i> Mis aulas</h3>
            <div class="card-actions">
                <a href="{{ route('aulas.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva aula
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($classrooms->isEmpty())
                <div style="text-align:center; padding:2rem; color:var(--text-secondary)">
                    <i class="fas fa-chalkboard" style="font-size:2rem; margin-bottom:.5rem;"></i>
                    <p>No tienes aulas registradas aún.</p>
                    <a href="{{ route('aulas.create') }}" class="btn btn-primary btn-sm">Crear primera aula</a>
                </div>
            @else
                <div class="table-container">
                    <table class="dynamic-table" id="aulasTable">
                        <thead>
                            <tr>
                                <th class="sortable">Materia <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable">Período <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable">Alumnos <i class="fas fa-sort sort-icon"></i></th>
                                <th class="sortable">Min. Asistencia <i class="fas fa-sort sort-icon"></i></th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classrooms as $classroom)
                            <tr>
                                <td>{{ $classroom->subject_name }}</td>
                                <td>{{ $classroom->period }}</td>
                                <td>
                                    <span class="{{ $classroom->enrollments_count >= $classroom->max_capacity ? 'pct-danger' : 'pct-ok' }}">
                                        {{ $classroom->enrollments_count }} / {{ $classroom->max_capacity }}
                                    </span>
                                </td>
                                <td>{{ $classroom->min_attendance_pct }}%</td>
                                <td class="action-cell">
                                    <button class="action-btn" title="Ver aula">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" title="Nueva sesión">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- PANEL: ACCESOS RÁPIDOS + ALERTAS --}}
    <div class="panel-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Accesos rápidos</h3>
            </div>
            <div class="card-body accesos-body">
                <a href="{{ route('aulas.index') }}" class="acceso-btn">
                    <div class="acceso-icon"><i class="fas fa-chalkboard"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Mis aulas</span>
                        <span class="acceso-desc">Ver y gestionar aulas activas</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('asistencias.docente') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--b"><i class="fas fa-key"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Nueva sesión</span>
                        <span class="acceso-desc">Generar clave de asistencia</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('justificantes.index') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--c"><i class="fas fa-file-alt"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Justificantes</span>
                        <span class="acceso-desc">{{ $stats['pending_justifications'] }} pendientes de dictaminar</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('reportes.index') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--d"><i class="fas fa-chart-bar"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Reportes</span>
                        <span class="acceso-desc">Generar y exportar matrices</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell"></i> Alertas</h3>
                @if($stats['pending_justifications'] > 0)
                    <span class="badge-num-header">{{ $stats['pending_justifications'] }}</span>
                @endif
            </div>
            <div class="card-body alertas-body">
                @if($stats['pending_justifications'] > 0)
                    <div class="alerta alerta--warning">
                        <div class="alerta-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">{{ $stats['pending_justifications'] }} justificantes pendientes</span>
                            <span class="alerta-desc">Requieren tu dictamen</span>
                        </div>
                        <span class="alerta-aula">Global</span>
                    </div>
                @endif
                @if($stats['at_risk_students'] > 0)
                    <div class="alerta alerta--danger">
                        <div class="alerta-icon"><i class="fas fa-user-times"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">Alumnos con faltas acumuladas</span>
                            <span class="alerta-desc">{{ $stats['at_risk_students'] }} faltas registradas en tus aulas</span>
                        </div>
                        <span class="alerta-aula">Aulas</span>
                    </div>
                @endif
                @if($stats['pending_justifications'] == 0 && $stats['at_risk_students'] == 0)
                    <div style="text-align:center; padding:1.5rem; color:var(--text-secondary)">
                        <i class="fas fa-check-circle" style="color:#28A745; font-size:1.5rem;"></i>
                        <p style="margin-top:.5rem;">Sin alertas activas</p>
                    </div>
                @endif
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
</script>
@endpush