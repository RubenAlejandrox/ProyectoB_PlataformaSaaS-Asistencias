@extends('layouts.app')

@section('title', 'Sesiones (Pruebas) - Admin')

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Sesiones en Admin</h1>
                <p>Vista de apoyo para pruebas de asistencias</p>
            </div>
        </div>
    </div>

    @if(session('invitation_code'))
        @php $ic = session('invitation_code'); @endphp
        <div style="display:flex;align-items:center;gap:1rem;background:#EAF3FB;border:1px solid #134474;border-left:4px solid #134474;border-radius:8px;padding:1rem 1.5rem;margin-bottom:1rem;">
            <i class="fas fa-key" style="color:#134474;font-size:1.3rem;"></i>
            <div>
                <p style="font-weight:700;color:#134474;margin:0;">Código generado para {{ $ic['classroom'] }}</p>
                <p style="font-size:1.3rem;font-weight:800;letter-spacing:.2rem;color:#F28B2C;margin:.2rem 0;">{{ $ic['code'] }}</p>
                <p style="font-size:.8rem;color:#666;margin:0;">Vence: {{ $ic['expires_at'] }}</p>
            </div>
        </div>
    @endif

    @if(session('attendance_access_key'))
        @php $sak = session('attendance_access_key'); @endphp
        <div style="display:flex;align-items:center;gap:1rem;background:#ECFDF3;border:1px solid #28a745;border-left:4px solid #28a745;border-radius:8px;padding:1rem 1.5rem;margin-bottom:1rem;">
            <i class="fas fa-stopwatch" style="color:#28a745;font-size:1.3rem;"></i>
            <div>
                <p style="font-weight:700;color:#166534;margin:0;">Clave de asistencia para {{ $sak['classroom'] }} ({{ $sak['session'] }})</p>
                <p style="font-size:1.3rem;font-weight:800;letter-spacing:.2rem;color:#14532d;margin:.2rem 0;">{{ $sak['code'] }}</p>
                <p style="font-size:.8rem;color:#166534;margin:0;">Vence: {{ $sak['expires_at'] }}</p>
            </div>
        </div>
    @endif

    <div class="kpi-grid" style="margin-bottom:1rem;">
        <div class="kpi-card">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total sesiones</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['active'] }}</span>
                <span class="kpi-label">Activas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['closed'] }}</span>
                <span class="kpi-label">Cerradas</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bolt"></i> Generar Clave de Asistencia (Sesiones Activas)
            </h3>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:.75rem;">
                @forelse($activeSessions as $active)
                    <div style="border:1px solid #d1fae5;border-radius:10px;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;gap:.75rem;background:#f0fdf4;">
                        <div>
                            <p style="margin:0;font-weight:700;color:#14532d;">{{ $active->classroom?->subject_name ?? 'Aula' }}</p>
                            <p style="margin:0;font-size:.85rem;color:#166534;">
                                {{ $active->session_date?->format('d/m/Y') }} · {{ $active->classroom?->period }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.sesiones.attendance-key', $active->id) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm" type="submit" title="Clave para registrar asistencia">
                                <i class="fas fa-key"></i> Asistencia
                            </button>
                        </form>
                    </div>
                @empty
                    <p style="margin:0;color:#888;">No hay sesiones activas en este momento. Si aplicaste filtros, usa “Limpiar”.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.sesiones.index') }}" style="display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:.75rem;align-items:end;">
                <div>
                    <label style="display:block;font-size:.85rem;margin-bottom:.35rem;">Aula</label>
                    <select name="classroom_id" class="filter-select" style="width:100%;">
                        <option value="">Todas</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(request('classroom_id') === $classroom->id)>
                                {{ $classroom->subject_name }} ({{ $classroom->period }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.85rem;margin-bottom:.35rem;">Estado</label>
                    <select name="status" class="filter-select" style="width:100%;">
                        <option value="">Todos</option>
                        <option value="active" @selected(request('status') === 'active')>Activa</option>
                        <option value="closed" @selected(request('status') === 'closed')>Cerrada</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.85rem;margin-bottom:.35rem;">Desde</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="search-input" style="width:100%;">
                </div>
                <div>
                    <label style="display:block;font-size:.85rem;margin-bottom:.35rem;">Hasta</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="search-input" style="width:100%;">
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button class="btn btn-primary btn-md" type="submit">Filtrar</button>
                    <a class="btn btn-outline btn-md" href="{{ route('admin.sesiones.index') }}">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-door-open"></i> Códigos de Acceso por Aula
            </h3>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.75rem;">
                @forelse($classrooms as $classroom)
                    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                        <div>
                            <p style="margin:0;font-weight:700;color:#134474;">{{ $classroom->subject_name }}</p>
                            <p style="margin:0;font-size:.85rem;color:#666;">{{ $classroom->period }}</p>
                        </div>
                        <form method="POST" action="{{ route('invitation-codes.store', $classroom->id) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fas fa-key"></i> Generar código
                            </button>
                        </form>
                    </div>
                @empty
                    <p style="margin:0;color:#888;">No hay aulas disponibles para generar código.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Aula</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th>Asistencias</th>
                            <th>Presentes</th>
                            <th>Faltas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->session_date?->format('d/m/Y') }}</td>
                                <td>{{ $session->classroom?->subject_name }}<br><small>{{ $session->classroom?->period }}</small></td>
                                <td>{{ trim(($session->classroom?->teacher?->first_name ?? '').' '.($session->classroom?->teacher?->last_name ?? '')) }}</td>
                                <td>
                                    @if($session->is_active)
                                        <span class="status status-pending">Activa</span>
                                    @else
                                        <span class="status status-active">Cerrada</span>
                                    @endif
                                </td>
                                <td>{{ $session->attendances_count }}</td>
                                <td>{{ $session->present_count }}</td>
                                <td>{{ $session->absent_count }}</td>
                                <td>
                                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                        <form method="POST" action="{{ route('invitation-codes.store', $session->classroom_id) }}">
                                            @csrf
                                            <button class="btn btn-outline btn-sm" type="submit" title="Código de invitación para inscripción al aula">
                                                <i class="fas fa-door-open"></i> Invitación
                                            </button>
                                        </form>
                                        @if($session->is_active)
                                            <form method="POST" action="{{ route('admin.sesiones.attendance-key', $session->id) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm" type="submit" title="Clave para registrar asistencia">
                                                    <i class="fas fa-key"></i> Asistencia
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm" type="button" disabled title="Solo para sesiones activas" style="opacity:.5;cursor:not-allowed;">
                                                <i class="fas fa-key"></i> Asistencia
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:1.25rem;color:#888;">No hay sesiones para los filtros aplicados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        {{ $sessions->links() }}
    </div>
</div>
@endsection
