{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * @descripcion    Dashboard Alumno — Progreso de asistencia por materia,
 *                 semáforo de riesgo, historial y justificantes enviados.
 * @version        1.0.0
 * @creado         26/05/2026
 */
--}}

@extends('layouts.app')

@section('title', 'Mi Portal - GAMA Solutions')

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Mi Portal</h1>
                <p>Bienvenido, {{ $user->first_name }} &nbsp;·&nbsp; Resumen de asistencias</p>
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
            <div class="kpi-icon"><i class="fas fa-book-open"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_subjects'] }}</span>
                <span class="kpi-label">Materias inscritas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ number_format($stats['avg_attendance'], 1) }}%</span>
                <span class="kpi-label">Asistencia promedio</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['at_risk'] }}</span>
                <span class="kpi-label">Materias en riesgo</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--orange">
            <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending_justifications'] }}</span>
                <span class="kpi-label">Justificantes en revisión</span>
            </div>
        </div>
    </div>

    {{-- PROGRESO POR MATERIA --}}
    <div class="card table-card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar"></i>
                Progreso por materia
            </h3>
        </div>
        <div class="card-body">
            @if($progress->isEmpty())
                <div style="text-align:center; padding:2rem; color:var(--text-secondary)">
                    <i class="fas fa-book-open" style="font-size:2rem; margin-bottom:.5rem;"></i>
                    <p>No estás inscrito en ninguna materia aún.</p>
                </div>
            @else
                @foreach($progress as $item)
                <div style="margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                        <div>
                            <strong>{{ $item['classroom']->subject_name }}</strong>
                            <span style="font-size:.8rem; color:var(--text-secondary); margin-left:.5rem;">
                                {{ $item['classroom']->period }}
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:.75rem;">
                            {{-- Semáforo --}}
                            <span style="
                                display:inline-flex; align-items:center; gap:.3rem;
                                padding:.2rem .6rem; border-radius:999px; font-size:.8rem; font-weight:600;
                                background:{{ $item['light'] === 'green' ? '#d1fae5' : ($item['light'] === 'amber' ? '#fef3c7' : '#fee2e2') }};
                                color:{{ $item['light'] === 'green' ? '#065f46' : ($item['light'] === 'amber' ? '#92400e' : '#991b1b') }};
                            ">
                                <i class="fas fa-circle" style="font-size:.5rem;"></i>
                                {{ $item['light'] === 'green' ? 'En regla' : ($item['light'] === 'amber' ? 'En observación' : 'En riesgo') }}
                            </span>
                            <span class="{{ $item['light'] === 'green' ? 'pct-ok' : ($item['light'] === 'amber' ? 'pct-warning' : 'pct-danger') }}">
                                {{ $item['percentage'] }}%
                            </span>
                        </div>
                    </div>
                    {{-- Barra de progreso --}}
                    <div style="background:#f3f4f6; border-radius:999px; height:8px; overflow:hidden;">
                        <div style="
                            height:100%; border-radius:999px;
                            width:{{ $item['percentage'] }}%;
                            background:{{ $item['light'] === 'green' ? '#28A745' : ($item['light'] === 'amber' ? '#F28B2C' : '#DC3545') }};
                            transition: width .5s ease;
                        "></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:.3rem; font-size:.75rem; color:var(--text-secondary)">
                        <span>{{ $item['present'] }} asistencias de {{ $item['total'] }} sesiones</span>
                        <span>Mínimo requerido: {{ $item['threshold'] }}%</span>
                    </div>
                </div>
                @endforeach
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
                <a href="{{ route('asistencias.alumno') }}" class="acceso-btn">
                    <div class="acceso-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Registrar asistencia</span>
                        <span class="acceso-desc">Ingresar clave de acceso</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('justificantes.index') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--b"><i class="fas fa-file-upload"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Subir justificante</span>
                        <span class="acceso-desc">Adjuntar comprobante de falta</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
                <a href="{{ route('asistencias.alumno') }}" class="acceso-btn">
                    <div class="acceso-icon acceso-icon--c"><i class="fas fa-history"></i></div>
                    <div class="acceso-text">
                        <span class="acceso-nombre">Mi historial</span>
                        <span class="acceso-desc">Ver asistencias registradas</span>
                    </div>
                    <i class="fas fa-chevron-right acceso-arrow"></i>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell"></i> Mis alertas</h3>
                @if($stats['at_risk'] > 0)
                    <span class="badge-num-header">{{ $stats['at_risk'] }}</span>
                @endif
            </div>
            <div class="card-body alertas-body">
                @forelse($progress->where('light', 'red') as $item)
                    <div class="alerta alerta--danger">
                        <div class="alerta-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">{{ $item['classroom']->subject_name }} — Asistencia crítica</span>
                            <span class="alerta-desc">{{ $item['percentage'] }}% — Mínimo requerido: {{ $item['threshold'] }}%</span>
                        </div>
                        <span class="alerta-aula">Riesgo</span>
                    </div>
                @empty
                @endforelse

                @forelse($progress->where('light', 'amber') as $item)
                    <div class="alerta alerta--warning">
                        <div class="alerta-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="alerta-content">
                            <span class="alerta-titulo">{{ $item['classroom']->subject_name }} — En observación</span>
                            <span class="alerta-desc">{{ $item['percentage'] }}% — Estás cerca del límite mínimo</span>
                        </div>
                        <span class="alerta-aula">Atención</span>
                    </div>
                @empty
                @endforelse

                @if($stats['at_risk'] == 0 && $progress->where('light','amber')->isEmpty())
                    <div style="text-align:center; padding:1.5rem; color:var(--text-secondary)">
                        <i class="fas fa-check-circle" style="color:#28A745; font-size:1.5rem;"></i>
                        <p style="margin-top:.5rem;">¡Vas muy bien! Sin alertas activas</p>
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