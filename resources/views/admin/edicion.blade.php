@extends('layouts.app')

@section('title', 'Edición Administrativa - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-edicion.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Edición Administrativa</h1>
                <p>Correcciones con auditoría transaccional obligatoria</p>
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
        <div class="card-header"><h3 class="card-title"><i class="fas fa-key"></i> Restablecer contraseña de usuario</h3></div>
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.edicion') }}" style="padding:.8rem 1rem;display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
                <input type="text"
                       name="q"
                       class="search-input"
                       placeholder="Buscar por nombre o correo"
                       value="{{ $search ?? '' }}"
                       style="max-width:320px;">
                <select name="role" class="filter-select">
                    <option value="" @selected(($roleFilter ?? '') === '')>Todos los roles</option>
                    <option value="Administrator" @selected(($roleFilter ?? '') === 'Administrator')>Administrator</option>
                    <option value="Teacher" @selected(($roleFilter ?? '') === 'Teacher')>Teacher</option>
                    <option value="Student" @selected(($roleFilter ?? '') === 'Student')>Student</option>
                </select>
                <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="{{ route('admin.edicion') }}" class="btn btn-outline btn-sm">Limpiar</a>
            </form>
            <div style="padding:.8rem 1rem;font-size:.9rem;color:#6b7280;">
                Contraseña temporal definida por política: <strong>GamaSolu1234$+</strong>
            </div>
            <div class="table-container">
                <table class="dynamic-table">
                    <thead><tr><th>Usuario</th><th>Correo</th><th>Institución</th><th>Rol</th><th>Acción</th></tr></thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->institution?->name ?? 'Sin institución' }}</td>
                            <td>{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.usuario.reset-password', $user) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-warning btn-sm" type="submit">
                                        <i class="fas fa-unlock-alt"></i> Restablecer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:1.25rem;color:#888;">Sin usuarios disponibles.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="pagination-compact-wrapper" style="padding:.9rem 1rem;">
                    <div class="pagination-summary">
                        Mostrando {{ $users->firstItem() }}-{{ $users->lastItem() }} de {{ $users->total() }} usuarios
                    </div>
                    <nav class="gama-pagination" aria-label="Paginación usuarios">
                        @if($users->onFirstPage())
                            <span class="gama-page-btn gama-page-btn--disabled">Anterior</span>
                        @else
                            <a class="gama-page-btn" href="{{ $users->previousPageUrl() }}">Anterior</a>
                        @endif

                        @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                            @if($page == $users->currentPage())
                                <span class="gama-page-btn gama-page-btn--active">{{ $page }}</span>
                            @else
                                <a class="gama-page-btn" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($users->hasMorePages())
                            <a class="gama-page-btn" href="{{ $users->nextPageUrl() }}">Siguiente</a>
                        @else
                            <span class="gama-page-btn gama-page-btn--disabled">Siguiente</span>
                        @endif
                    </nav>
                </div>
            @endif
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check"></i> Corregir asistencia</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead><tr><th>Alumno</th><th>Aula</th><th>Fecha</th><th>Actual</th><th>Nuevo</th><th>Motivo</th><th>Guardar</th></tr></thead>
                    <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->student?->first_name }} {{ $attendance->student?->last_name }}</td>
                            <td>{{ $attendance->session?->classroom?->subject_name }}</td>
                            <td>{{ $attendance->session?->session_date?->format('d/m/Y') }}</td>
                            <td>{{ $attendance->status }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.asistencia.correct', $attendance) }}" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf @method('PUT')
                                    <select name="status" class="filter-select">
                                        <option value="present">present</option>
                                        <option value="absent">absent</option>
                                    </select>
                            </td>
                            <td><input class="search-input" name="reason" required placeholder="Motivo"></td>
                            <td><button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-save"></i></button></td>
                                </form>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:1.25rem;color:#888;">Sin asistencias para corregir.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-times"></i> Baja administrativa</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead><tr><th>Alumno</th><th>Aula</th><th>Estado</th><th>Motivo</th><th>Baja</th></tr></thead>
                    <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}</td>
                            <td>{{ $enrollment->classroom?->subject_name }}</td>
                            <td>{{ $enrollment->is_active ? 'Activo' : 'Inactivo' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.alumno.drop', $enrollment) }}" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf @method('PUT')
                                    <input class="search-input" name="reason" required placeholder="Motivo de baja">
                            </td>
                            <td><button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-user-times"></i></button></td>
                                </form>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:1.25rem;color:#888;">Sin alumnos activos en aulas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-times"></i> Eliminar sesión</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead><tr><th>Aula</th><th>Fecha</th><th>Motivo</th><th>Eliminar</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->classroom?->subject_name }}</td>
                            <td>{{ $session->session_date?->format('d/m/Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.sesion.delete', $session) }}" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf @method('DELETE')
                                    <input class="search-input" name="reason" required placeholder="Motivo de eliminación">
                            </td>
                            <td><button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button></td>
                                </form>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:1.25rem;color:#888;">Sin sesiones disponibles.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-list"></i> Auditoría reciente</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table">
                    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th></tr></thead>
                    <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->first_name }} {{ $log->user?->last_name }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->entity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:1.25rem;color:#888;">Sin registros de auditoría.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
