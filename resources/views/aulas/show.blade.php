@extends('layouts.app')

@section('title', $classroom->subject_name . ' - Detalle de aula')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/aulas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/materias.css') }}">
@endpush

@section('content')
@php
    $isOwner = auth()->id() === $classroom->teacher_id;
@endphp
<div class="main-content">

    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>{{ $classroom->subject_name }}</h1>
                <p>
                    {{ $classroom->period }}
                    · Docente: {{ $classroom->teacher?->first_name }} {{ $classroom->teacher?->last_name }}
                    ·
                    <span class="status {{ $classroom->is_active ? 'status-open' : 'status-closed' }}">
                        {{ $classroom->is_active ? 'Ciclo abierto' : 'Ciclo cerrado' }}
                    </span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('aulas.index') }}" class="btn btn-outline btn-md">
                    <i class="fas fa-arrow-left"></i> Volver a aulas
                </a>
                <a href="{{ route('aulas.alumnos.export', $classroom) }}" class="btn btn-primary btn-md">
                    <i class="fas fa-download"></i> Descargar lista
                </a>
                @if($classroom->is_active)
                    <a href="{{ route('asistencias.docente', ['classroom' => $classroom->id]) }}" class="btn btn-outline btn-md">
                        <i class="fas fa-clipboard-check"></i> Asistencias
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['enrolled'] }} / {{ $stats['capacity'] }}</span>
                <span class="kpi-label">Alumnos inscritos</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['sessions'] }}</span>
                <span class="kpi-label">Sesiones del ciclo</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['at_risk'] }}</span>
                <span class="kpi-label">En observación o riesgo</span>
            </div>
        </div>
        <div class="kpi-card materia-stat-card--highlight" style="border-color:#134474;">
            <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['avg_attendance'] }}%</span>
                <span class="kpi-label">Promedio del grupo (mín. {{ $stats['min_attendance'] }}%)</span>
            </div>
        </div>
    </div>

    @if($isOwner && $activeCode)
        <div style="display:flex;align-items:center;gap:1rem;background:#EAF3FB;border:1px solid #134474;border-left:4px solid #134474;border-radius:8px;padding:.85rem 1.25rem;margin-bottom:1.25rem;flex-wrap:wrap;">
            <i class="fas fa-key" style="color:#134474;font-size:1.25rem;"></i>
            <div>
                <p style="font-weight:700;color:#134474;margin:0;font-size:.9rem;">Código de invitación activo</p>
                <p style="font-size:1.25rem;font-weight:800;letter-spacing:.25rem;color:#F28B2C;margin:.25rem 0;">{{ $activeCode->code }}</p>
                <p style="font-size:.8rem;color:#666;margin:0;">Vence: {{ $activeCode->expires_at->format('d/m/Y H:i') }}</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" style="margin-left:auto;"
                    onclick="copiarCodigo('{{ $activeCode->code }}', this)">
                <i class="fas fa-copy"></i> Copiar
            </button>
        </div>
    @endif

    <div class="aula-detail-grid">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
                <h3 class="card-title" style="margin:0;">
                    <i class="fas fa-user-graduate"></i> Alumnos inscritos
                </h3>
                <span style="font-size:.85rem;color:#6b7280;">{{ $students->count() }} alumno(s)</span>
            </div>
            <div class="card-body p-0">
                @if($students->isEmpty())
                    <p style="padding:1.5rem;margin:0;color:#6b7280;">
                        Aún no hay alumnos inscritos. Comparte el código de invitación para que se unan al aula.
                    </p>
                @else
                    <div class="table-container">
                        <table class="dynamic-table aula-alumnos-table">
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th>% Asistencia</th>
                                    <th>Semáforo</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $semClass = match($student['light']) {
                                            'green' => 'semaforo-badge--green',
                                            'amber' => 'semaforo-badge--amber',
                                            default => 'semaforo-badge--red',
                                        };
                                        $pctClass = match($student['light']) {
                                            'green' => 'pct-ok',
                                            'amber' => 'pct-warning',
                                            default => 'pct-danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="aula-alumno-cell">
                                                <div class="avatar-xs">{{ $student['initials'] }}</div>
                                                <div>
                                                    <span class="cell-nombre">{{ $student['name'] }}</span>
                                                    <span class="cell-grupo">{{ $student['email'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="{{ $pctClass }}" style="font-weight:700;font-size:1rem;">
                                                {{ $student['attendance_pct'] }}%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="semaforo-badge {{ $semClass }}">
                                                <i class="fas fa-circle" style="font-size:.45rem;"></i>
                                                {{ $student['light_label'] }}
                                            </span>
                                        </td>
                                        <td style="font-size:.85rem;color:#6b7280;">
                                            {{ $student['present_count'] + $student['approved_count'] }}
                                            / {{ $student['total_sessions'] }} sesiones a favor
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="margin:0;">
                    <i class="fas fa-history"></i> Historial de sesiones del ciclo
                </h3>
            </div>
            <div class="card-body p-0">
                @if($sessions->isEmpty())
                    <p style="padding:1.5rem;margin:0;color:#6b7280;">
                        No se han registrado sesiones en este ciclo.
                    </p>
                @else
                    <div class="table-container">
                        <table class="dynamic-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Presentes</th>
                                    <th>Faltas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td>{{ $session->session_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if($session->is_active)
                                                <span class="status status-open">Abierta</span>
                                            @else
                                                <span class="status" style="background:#f3f4f6;color:#6b7280;">Cerrada</span>
                                            @endif
                                        </td>
                                        <td>{{ $session->present_count }}</td>
                                        <td>{{ $session->absent_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($isOwner)
        <div class="aula-detail-actions">
            @if($classroom->is_active)
                <form method="POST" action="{{ route('aulas.generate-code', $classroom) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-md">
                        <i class="fas fa-key"></i> Regenerar código
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('aulas.toggle', $classroom) }}" style="display:inline;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline btn-md {{ $classroom->is_active ? 'btn-danger-outline' : '' }}">
                    <i class="fas {{ $classroom->is_active ? 'fa-lock' : 'fa-lock-open' }}"></i>
                    {{ $classroom->is_active ? 'Cerrar ciclo' : 'Reabrir ciclo' }}
                </button>
            </form>
        </div>
    @endif

</div>

@push('scripts')
<script>
    function copiarCodigo(code, btn) {
        const onDone = () => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        };
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(code).then(onDone).catch(onDone);
        } else {
            onDone();
        }
    }
</script>
@endpush
@endsection
