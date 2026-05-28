@extends('layouts.app')

@section('title', 'Reportes - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reportes.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Reportes Analíticos</h1>
                <p>Descargas XLSX y envío por correo</p>
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

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.index') }}" style="display:grid;grid-template-columns:2fr 1fr auto;gap:.75rem;align-items:end;">
                <div>
                    <label class="form-label">Aula</label>
                    <select name="classroom" class="filter-select" style="width:100%;">
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" @selected($classroom && $classroom->id === $c->id)>
                                {{ $c->subject_name }} — {{ $c->period }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Mes</label>
                    <input class="search-input" style="width:100%;" type="month" name="month" value="{{ $month }}">
                </div>
                <button class="btn btn-outline btn-md" type="submit">Filtrar</button>
            </form>
        </div>
    </div>

    @if($classroom)
    <div class="panel-grid" style="grid-template-columns:1fr 1fr;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download"></i> Descargas XLSX</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <a class="btn btn-primary btn-md" href="{{ route('reportes.matrix', $classroom) }}">
                        <i class="fas fa-table"></i> Matriz A/F/J
                    </a>
                    <a class="btn btn-outline btn-md" href="{{ route('reportes.monthly', ['classroom' => $classroom, 'month' => $month]) }}">
                        <i class="fas fa-calendar-alt"></i> Resumen mensual
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope"></i> Envío por correo</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('reportes.send', $classroom) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Correo destino</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de reporte</label>
                        <select name="type" class="form-input">
                            <option value="matrix">Matriz A/F/J</option>
                            <option value="monthly">Resumen mensual</option>
                        </select>
                    </div>
                    <input type="hidden" name="month" value="{{ $month }}">
                    <div class="form-group">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="subject" class="form-input" value="Reporte de asistencias">
                    </div>
                    <button class="btn btn-primary btn-md" type="submit">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-exclamation-triangle"></i> Alumnos en riesgo
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>%</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riskPreview as $row)
                            <tr>
                                <td>{{ $row['student'] }}</td>
                                <td>{{ $row['pct'] }}%</td>
                                <td>
                                    @if($row['status'] === 'Riesgo')
                                        <span class="status status-warning">En riesgo</span>
                                    @else
                                        <span class="status status-ok">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center;padding:1.25rem;color:#888;">Sin datos para el periodo seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
        <div class="card"><div class="card-body">No hay aulas disponibles para generar reportes.</div></div>
    @endif
</div>
@endsection