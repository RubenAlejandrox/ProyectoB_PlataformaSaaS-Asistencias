@extends('layouts.app')

@section('title', 'Bitácora de Auditoría - GAMA Solutions')

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Bitácora de Auditoría</h1>
                <p>Registro de acciones sobre entidades de la plataforma</p>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total eventos</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['create'] }}</span>
                <span class="kpi-label">Altas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['update'] }}</span>
                <span class="kpi-label">Cambios</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--danger">
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['delete'] }}</span>
                <span class="kpi-label">Eliminaciones</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Eventos</h3>
            <form id="filtersForm" method="GET" action="{{ route('bitacora.index') }}" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Usuario / correo</label>
                    <input type="text"
                           name="search"
                           class="search-input"
                           value="{{ request('search') }}"
                           placeholder="Nombre o email">
                </div>

                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Desde</label>
                    <input type="date" name="from_date" class="filter-select" value="{{ request('from_date') }}">
                </div>

                <div>
                    <label style="display:block;font-size:.78rem;color:#6b7280;margin-bottom:.25rem;">Hasta</label>
                    <input type="date" name="to_date" class="filter-select" value="{{ request('to_date') }}">
                </div>

                <select name="action" class="filter-select">
                    <option value="">Todas las acciones</option>
                    <option value="create" @selected(request('action') === 'create')>create</option>
                    <option value="update" @selected(request('action') === 'update')>update</option>
                    <option value="delete" @selected(request('action') === 'delete')>delete</option>
                </select>

                <select name="entity" class="filter-select">
                    <option value="">Todas las entidades</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity }}" @selected(request('entity') === $entity)>{{ $entity }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                <a href="{{ route('bitacora.index') }}" class="btn btn-outline btn-sm">
                    Limpiar
                </a>

                <a href="{{ route('bitacora.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>ID Entidad</th>
                            <th>Datos nuevos</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>
                                {{ trim(($log->user->first_name ?? '') . ' ' . ($log->user->last_name ?? '')) ?: ($log->user->email ?? 'N/A') }}
                            </td>
                            <td><span class="status">{{ $log->action }}</span></td>
                            <td>{{ $log->entity }}</td>
                            <td><code>{{ $log->entity_id }}</code></td>
                            <td>
                                <details>
                                    <summary>Ver</summary>
                                    <pre style="white-space:pre-wrap;max-width:500px;">{{ json_encode($log->new_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2rem;color:#6b7280;">No hay eventos registrados.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
