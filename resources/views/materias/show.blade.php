@extends('layouts.app')

@section('title', $classroom->subject_name . ' - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/materias.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>{{ $classroom->subject_name }}</h1>
                <p>{{ $classroom->period }} · Docente: {{ $classroom->teacher?->first_name }} {{ $classroom->teacher?->last_name }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('asistencias.alumno', ['classroom' => $classroom->id]) }}" class="btn btn-outline btn-md">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    @php
        $semClass = match($progress['light']) {
            'green' => 'semaforo-badge--green',
            'amber' => 'semaforo-badge--amber',
            default => 'semaforo-badge--red',
        };
        $semLabel = match($progress['light']) {
            'green' => 'En regla',
            'amber' => 'En observación',
            default => 'En riesgo',
        };
    @endphp

    <div class="materia-header-meta">
        <div class="materia-stat-card materia-stat-card--highlight">
            <div class="materia-stat-value">{{ $progress['attendance_pct'] }}%</div>
            <div class="materia-stat-label">Asistencia actual</div>
            <span class="semaforo-badge {{ $semClass }}" style="margin-top:.5rem;">
                <i class="fas fa-circle" style="font-size:.45rem;"></i> {{ $semLabel }}
            </span>
        </div>
        <div class="materia-stat-card">
            <div class="materia-stat-value">{{ $progress['threshold'] }}%</div>
            <div class="materia-stat-label">Umbral mínimo</div>
        </div>
        <div class="materia-stat-card">
            <div class="materia-stat-value">{{ $progress['present_count'] + $progress['approved_count'] }}/{{ $progress['total_sessions'] }}</div>
            <div class="materia-stat-label">Sesiones a favor</div>
        </div>
        <div class="materia-stat-card">
            <div class="materia-stat-value">{{ $projection['remaining'] ?? '—' }}</div>
            <div class="materia-stat-label">Faltas posibles (proyección)</div>
        </div>
    </div>

    <div class="proyeccion-box">
        <i class="fas fa-calculator"></i>
        {{ $projection['message'] }}
    </div>

    <div class="panel-grid" style="grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.5rem;align-items:start;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Calendario del mes</h3>
            </div>
            <div class="card-body">
                <div class="calendario-nav">
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('materias.show', ['classroom' => $classroom->id, 'year' => $prevMonth['year'], 'month' => $prevMonth['month']]) }}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h3>{{ $calendar['month_label'] }}</h3>
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('materias.show', ['classroom' => $classroom->id, 'year' => $nextMonth['year'], 'month' => $nextMonth['month']]) }}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <table class="calendario-grid">
                    <thead>
                        <tr>
                            <th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($calendar['weeks'] as $week)
                            <tr>
                                @foreach($week as $day)
                                    <td>
                                        <div class="cal-dia cal-dia--{{ $day['status'] }} {{ !$day['in_month'] ? 'cal-dia--fuera' : '' }}"
                                             title="{{ $day['has_session'] ? 'Sesión: ' . ucfirst($day['status']) : '' }}">
                                            {{ $day['day'] }}
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="calendario-leyenda">
                    <span class="leyenda-item"><span class="leyenda-dot" style="background:#d1fae5;"></span> Asistencia</span>
                    <span class="leyenda-item"><span class="leyenda-dot" style="background:#fee2e2;"></span> Falta</span>
                    <span class="leyenda-item"><span class="leyenda-dot" style="background:#fef3c7;"></span> Justificado</span>
                    <span class="leyenda-item"><span class="leyenda-dot" style="background:#fff7ed;border:1px dashed #f59e0b;"></span> En revisión</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt"></i> Mis justificantes</h3>
            </div>
            <div class="card-body">
                @if($justifications->isEmpty())
                    <p style="margin:0;color:#6b7280;">No has enviado justificantes para esta materia.</p>
                @else
                    <ul class="just-list">
                        @foreach($justifications as $just)
                            @php
                                $date = $just->attendance?->session?->session_date?->format('d/m/Y') ?? '—';
                                $statusClass = 'just-status--' . $just->status;
                                $statusLabel = match($just->status) {
                                    'approved' => 'Aprobado',
                                    'rejected' => 'Rechazado',
                                    default => 'Pendiente',
                                };
                            @endphp
                            <li class="just-item">
                                <div>
                                    <strong>{{ $date }}</strong>
                                    @if($just->reason)
                                        <p style="margin:.25rem 0 0;font-size:.85rem;color:#6b7280;">{{ Str::limit($just->reason, 80) }}</p>
                                    @endif
                                    @if($just->reviewed_at)
                                        <p style="margin:.2rem 0 0;font-size:.75rem;color:#94a3b8;">
                                            Revisado {{ $just->reviewed_at->format('d/m/Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                                <span class="just-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('justificantes.index') }}" class="btn btn-outline btn-sm" style="margin-top:1rem;">
                    Ver todos los justificantes
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
