@extends('layouts.app')

@section('title', 'Cierre de Ciclo - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ciclo.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Cierre de Ciclo</h1>
                <p>Validación de requisitos y cierre definitivo del ciclo.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->has('general'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#fee2e2;border-left:4px solid #DC3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#991b1b;">
            <i class="fas fa-exclamation-circle"></i><span>{{ $errors->first('general') }}</span>
        </div>
    @endif

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['sessions_count'] }}</span>
                <span class="kpi-label">Sesiones realizadas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['approved_count'] }}</span>
                <span class="kpi-label">Alumnos aprobados</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--danger">
            <div class="kpi-icon"><i class="fas fa-user-times"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['failed_count'] }}</span>
                <span class="kpi-label">Alumnos a reprobar</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending_justifications'] }}</span>
                <span class="kpi-label">Justificantes pendientes</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-lock"></i> Ejecutar cierre
            </h3>
        </div>
        <div class="card-body">
            @if($cycles->isEmpty())
                <p>No hay ciclos registrados para cerrar.</p>
            @else
                <form method="GET" action="{{ route('ciclo.cierre') }}" style="margin-bottom:1rem;display:flex;gap:.75rem;align-items:end;">
                    <div style="flex:1;">
                        <label class="form-label">Ciclo</label>
                        <select name="cycle" class="filter-select" style="width:100%;">
                            @foreach($cycles as $c)
                                <option value="{{ $c->id }}" @selected($cycle && $cycle->id === $c->id)>
                                    {{ $c->name }} — {{ $c->classroom?->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-outline btn-md" type="submit">Cambiar</button>
                </form>

                @if($cycle)
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:.75rem;margin-bottom:1rem;">
                        <div class="checklist-item checklist-ok">
                            <div class="check-icon check-ok"><i class="fas fa-check"></i></div>
                            <div class="check-content">
                                <span class="check-title">Sin justificantes pendientes</span>
                                <span class="check-desc">{{ $stats['pending_justifications'] === 0 ? 'Listo para cierre' : 'Pendientes: '.$stats['pending_justifications'] }}</span>
                            </div>
                        </div>
                        <div class="checklist-item {{ $cycle->isClosureLocked() ? 'checklist-pending' : 'checklist-ok' }}">
                            <div class="check-icon {{ $cycle->isClosureLocked() ? 'check-pending' : 'check-ok' }}">
                                <i class="fas {{ $cycle->isClosureLocked() ? 'fa-hourglass-half' : 'fa-check' }}"></i>
                            </div>
                            <div class="check-content">
                                <span class="check-title">Estado de bloqueo</span>
                                <span class="check-desc">
                                    {{ $cycle->isClosureLocked() ? 'Bloqueado hasta '.$cycle->closure_locked_until?->format('d/m/Y H:i') : 'Sin bloqueo' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('ciclo.close', $cycle) }}" style="margin-top:1rem;">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Clave de seguridad de cierre</label>
                            <input type="password" name="closure_key" class="form-input" required>
                            <p class="form-hint">
                                Intentos usados: {{ (int) $cycle->closure_attempts }} / 3
                            </p>
                        </div>
                        <button class="btn btn-danger btn-md" type="submit"
                            @if($cycle->isClosureLocked()) disabled @endif>
                            <i class="fas fa-lock"></i> Cerrar ciclo
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection