{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Módulo de Aulas — Listado de aulas con datos reales,
 *                 generación de códigos de invitación y control de límite
 *                 del plan (RF-04 / RF-05).
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 * @version        2.0.0
 * @creado         07/05/2026
 * @modificado     26/05/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 07/05/2026  | Rubén Alejandro   | Implementación inicial Aulas Index (RF-04).
 * 26/05/2026  | Claude Web        | Conexión con ClassroomController + invitation codes.
 */
--}}

@extends('layouts.app')

@section('title', 'Aulas - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/aulas.css') }}">
@endpush

@section('content')
@php $isStudent = auth()->user()->hasRole('Student'); @endphp
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>{{ $isStudent ? 'Mis Aulas' : 'Aulas' }}</h1>
                <p>{{ $isStudent ? 'Consulta las aulas en las que estás inscrito' : 'Gestión de aulas y grupos de la institución' }}</p>
            </div>
            @unless($isStudent)
            <div class="header-actions">
                @if($stats['can_create'])
                    <a href="{{ route('aulas.create') }}" class="btn btn-primary btn-md">
                        <i class="fas fa-plus"></i>
                        Nueva aula
                    </a>
                @else
                    <button class="btn btn-primary btn-md" disabled
                            title="Límite de aulas alcanzado. Actualiza tu plan."
                            style="opacity:.5;cursor:not-allowed;">
                        <i class="fas fa-plus"></i>
                        Nueva aula
                    </button>
                @endif
            </div>
            @endunless
            @if($isStudent)
            <div class="header-actions">
                <button type="button" class="btn btn-primary btn-md" onclick="abrirModal('modalInscripcionAlumno')">
                    <i class="fas fa-key"></i>
                    Unirme a un aula
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- SUCCESS / ERROR --}}
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

    @if($isStudent && $classrooms->isEmpty())
        <div style="display:flex;align-items:flex-start;gap:.75rem;background:#EAF3FB;border:1px solid #134474;border-left:4px solid #134474;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1.2rem;color:#134474;">
            <i class="fas fa-info-circle" style="font-size:1.25rem;margin-top:.1rem;"></i>
            <div>
                <p style="font-weight:700;margin:0 0 .35rem;">Aún no perteneces a ningún aula</p>
                <p style="margin:0;font-size:.9rem;line-height:1.5;">
                    Pide el código de invitación a tu docente y únete aquí.
                    Al inscribirte, tu cuenta quedará vinculada a la institución del aula.
                </p>
            </div>
        </div>
    @endif

    {{-- BANNER: CÓDIGO DE INVITACIÓN GENERADO --}}
    @if(!$isStudent && session('invitation_code'))
        @php $ic = session('invitation_code'); @endphp
        <div style="display:flex;align-items:center;gap:1rem;background:#EAF3FB;border:1px solid #134474;border-left:4px solid #134474;border-radius:8px;padding:1rem 1.5rem;margin-bottom:1.2rem;">
            <i class="fas fa-key" style="color:#134474;font-size:1.5rem;"></i>
            <div>
                <p style="font-weight:700;color:#134474;margin:0;">Código generado para {{ $ic['classroom'] }}</p>
                <p style="font-size:1.4rem;font-weight:800;letter-spacing:.3rem;color:#F28B2C;margin:.3rem 0;">{{ $ic['code'] }}</p>
                <p style="font-size:.8rem;color:#666;margin:0;">Vence: {{ $ic['expires_at'] }} · Comparte con tus alumnos</p>
            </div>
            <button type="button"
                    onclick="copiarCodigo('{{ $ic['code'] }}', this)"
                    class="btn btn-primary btn-sm" style="margin-left:auto;">
                <i class="fas fa-copy"></i> Copiar
            </button>
        </div>
    @endif

    {{-- LÍMITE DEL PLAN --}}
    @unless($isStudent)
    @if($activePlan)
        <div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem 1rem;margin-bottom:1.2rem;font-size:.85rem;color:#555;flex-wrap:wrap;">
            <i class="fas fa-info-circle" style="color:#134474;"></i>
            <span>Plan <strong>{{ $activePlan->name }}</strong>:
                <strong>{{ $stats['plan_used'] }} / {{ $stats['plan_limit'] }}</strong> aulas utilizadas.
            </span>
            @if(!$stats['can_create'])
                <a href="{{ route('membresias.index') }}" style="margin-left:.5rem;color:#F28B2C;font-weight:600;">
                    Actualizar plan →
                </a>
            @endif
        </div>
    @else
        <div style="display:flex;align-items:center;gap:.5rem;background:#fef3c7;border:1px solid #f59e0b;border-left:4px solid #f59e0b;border-radius:8px;padding:.6rem 1rem;margin-bottom:1.2rem;font-size:.85rem;color:#92400e;">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Tu institución no tiene un plan activo. <a href="{{ route('membresias.index') }}" style="color:#92400e;font-weight:600;text-decoration:underline;">Contrata uno</a> para crear aulas.</span>
        </div>
    @endif
    @endunless

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-chalkboard"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total aulas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-lock-open"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['active'] }}</span>
                <span class="kpi-label">Ciclo abierto</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-lock"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['closed'] }}</span>
                <span class="kpi-label">Ciclo cerrado</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_students'] }}</span>
                <span class="kpi-label">Alumnos totales</span>
            </div>
        </div>
    </div>

    @unless($isStudent)
    {{-- TABS --}}
    <div class="mod-tabs">
        <button class="mod-tab active" data-tab="todas">
            <i class="fas fa-th-large"></i> Todas
        </button>
        <button class="mod-tab" data-tab="abierto">
            <i class="fas fa-lock-open"></i> Ciclo abierto
        </button>
        <button class="mod-tab" data-tab="cerrado">
            <i class="fas fa-lock"></i> Ciclo cerrado
        </button>
    </div>

    {{-- VISTA TOGGLE --}}
    <div class="vista-toggle">
        <button class="toggle-btn active" id="btnGrid" title="Vista tarjetas">
            <i class="fas fa-th-large"></i>
        </button>
        <button class="toggle-btn" id="btnList" title="Vista tabla">
            <i class="fas fa-list"></i>
        </button>
        <div class="search-bar" style="margin-left:auto;">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Buscar aula..." id="buscarAula">
        </div>
    </div>
    @endunless

    {{-- VISTA TARJETAS --}}
    <div id="vistaGrid">
        <div class="aulas-grid" id="gridAulas">
            @forelse($classrooms as $classroom)
                @php
                    $tabStatus = $classroom->is_active ? 'abierto' : 'cerrado';
                    $colors    = ['a','b','c','d','e'];
                    $color     = $colors[$loop->index % count($colors)];
                    $teacher   = $classroom->teacher;
                    $initials  = strtoupper(substr($teacher->first_name ?? 'D', 0, 1))
                               . strtoupper(substr($teacher->last_name ?? 'C', 0, 1));
                    $latestCode = $classroom->invitationCodes
                        ->sortByDesc('created_at')
                        ->first();
                    $activeCode = $classroom->invitationCodes
                        ->where('expires_at', '>', now())
                        ->sortByDesc('created_at')
                        ->first();
                @endphp
                <div class="aula-card {{ !$classroom->is_active ? 'aula-card--cerrada' : '' }}"
                     data-tab="{{ $tabStatus }}"
                     data-classroom-id="{{ $classroom->id }}"
                     data-classroom-name="{{ $classroom->subject_name }}"
                     @if($latestCode?->expires_at) data-latest-expires-at="{{ $latestCode->expires_at->toIso8601String() }}" @endif
                     data-can-regenerate="{{ ($classroom->is_active && auth()->id() === $classroom->teacher_id) ? '1' : '0' }}">
                    <div class="aula-card-header">
                        <div class="aula-icon {{ !$classroom->is_active ? 'aula-icon--closed' : 'aula-icon--'.$color }}">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <span class="status {{ $classroom->is_active ? 'status-open' : 'status-closed' }}">
                            {{ $classroom->is_active ? 'Abierto' : 'Cerrado' }}
                        </span>
                    </div>
                    <h3 class="aula-nombre">{{ $classroom->subject_name }}</h3>
                    <p class="aula-grupo">{{ $classroom->period }}</p>
                    <div class="aula-stats">
                        <div class="aula-stat">
                            <i class="fas fa-users"></i>
                            <span>{{ $classroom->enrollments_count }} / {{ $classroom->max_capacity }}</span>
                        </div>
                        <div class="aula-stat">
                            <i class="fas fa-calendar-check"></i>
                            <span>{{ $classroom->sessions_count }} sesiones</span>
                        </div>
                        <div class="aula-stat">
                            <i class="fas fa-percentage"></i>
                            <span>Mín. {{ $classroom->min_attendance_pct }}%</span>
                        </div>
                    </div>
                    <div class="aula-docente">
                        <div class="avatar-xs">{{ $initials }}</div>
                        <span>{{ $teacher?->first_name }} {{ $teacher?->last_name }}</span>
                    </div>
                    <div class="aula-card-footer">
                        @unless($isStudent)
                        <div class="aula-code-block">
                            <div class="aula-code-row">
                                <span class="aula-codigo">
                                    {{ $activeCode?->code ?? 'Sin código' }}
                                </span>
                                @if($activeCode && auth()->id() === $classroom->teacher_id)
                                    <button type="button"
                                            class="btn-copy-code"
                                            title="Copiar código de acceso"
                                            onclick="copiarCodigo('{{ $activeCode->code }}', this)">
                                        <i class="fas fa-copy"></i> Copiar
                                    </button>
                                @endif
                            </div>
                            <small class="code-countdown"
                                   @if($latestCode?->expires_at) data-expires-at="{{ $latestCode->expires_at->toIso8601String() }}" @endif>
                                {{ $latestCode ? 'Calculando tiempo...' : 'Sin código activo' }}
                            </small>
                            <small class="code-status {{ $activeCode ? 'code-status--active' : ($latestCode ? 'code-status--expired' : 'code-status--none') }}">
                                {{ $activeCode ? 'Código activo para inscripciones' : ($latestCode ? 'Código expirado' : 'Aún sin código generado') }}
                            </small>
                        </div>
                        @endunless
                        @if($isStudent)
                            <a href="{{ route('materias.show', $classroom) }}"
                               class="btn btn-primary btn-md"
                               style="width:100%;justify-content:center;margin-top:.5rem;">
                                <i class="fas fa-chart-line"></i>
                                Ver detalle de materia
                            </a>
                        @else
                        <div class="aula-acciones">
                            <button class="action-btn" title="Ver detalle"
                                onclick="abrirDetalle(
                                    '{{ $classroom->id }}',
                                    '{{ addslashes($classroom->subject_name) }}',
                                    '{{ $classroom->period }}',
                                    {{ $classroom->enrollments_count }},
                                    {{ $classroom->max_capacity }},
                                    {{ $classroom->sessions_count }},
                                    {{ $classroom->min_attendance_pct }},
                                    '{{ addslashes(($teacher?->first_name ?? '') . ' ' . ($teacher?->last_name ?? '')) }}',
                                    {{ $classroom->is_active ? 'true' : 'false' }},
                                    '{{ $activeCode?->code ?? '' }}'
                                )">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($classroom->is_active && auth()->user()->id === $classroom->teacher_id)
                                {{-- Generar nuevo código --}}
                                <form method="POST"
                                      action="{{ route('aulas.generate-code', $classroom->id) }}"
                                      style="display:inline"
                                      id="codeForm-{{ $classroom->id }}">
                                    @csrf
                                    <button type="submit" class="action-btn" title="Regenerar código de invitación">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </form>
                            @endif
                            @if(auth()->user()->id === $classroom->teacher_id || auth()->user()->hasRole('Administrator'))
                                {{-- Toggle estado --}}
                                <form method="POST"
                                      action="{{ route('aulas.toggle', $classroom->id) }}"
                                      style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="action-btn {{ $classroom->is_active ? 'danger' : '' }}"
                                            title="{{ $classroom->is_active ? 'Cerrar ciclo' : 'Reabrir ciclo' }}">
                                        <i class="fas {{ $classroom->is_active ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-secondary)">
                    <i class="fas fa-chalkboard" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
                    @if($isStudent)
                        <p>No estás inscrito en ningún aula.</p>
                        <button type="button" class="btn btn-primary btn-md" style="margin-top:1rem;" onclick="abrirModal('modalInscripcionAlumno')">
                            <i class="fas fa-key"></i> Unirme con código de invitación
                        </button>
                    @else
                        <p>No tienes aulas registradas aún.</p>
                        @if($stats['can_create'])
                            <a href="{{ route('aulas.create') }}" class="btn btn-primary btn-md" style="margin-top:1rem;">
                                <i class="fas fa-plus"></i> Crear primera aula
                            </a>
                        @endif
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    @unless($isStudent)
    {{-- VISTA TABLA --}}
    <div id="vistaList" style="display:none;">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="dynamic-table" id="tablaAulas">
                        <thead>
                            <tr>
                                <th>Aula</th>
                                <th>Docente</th>
                                <th>Período</th>
                                <th>Alumnos</th>
                                <th>Sesiones</th>
                                <th>Mín. Asist.</th>
                                <th>Código y vigencia</th>
                                <th>Ciclo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classrooms as $classroom)
                                @php
                                    $tabStatus = $classroom->is_active ? 'abierto' : 'cerrado';
                                    $teacher   = $classroom->teacher;
                                    $latestCode = $classroom->invitationCodes
                                        ->sortByDesc('created_at')
                                        ->first();
                                    $activeCode = $classroom->invitationCodes
                                        ->where('expires_at', '>', now())
                                        ->sortByDesc('created_at')
                                        ->first();
                                @endphp
                                <tr data-tab="{{ $tabStatus }}">
                                    <td>
                                        <div class="aula-cell">
                                            <div class="aula-dot dot-{{ $classroom->is_active ? 'a' : 'closed' }}"></div>
                                            <div>
                                                <span class="cell-nombre">{{ $classroom->subject_name }}</span>
                                                <span class="cell-grupo">{{ $classroom->period }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $teacher?->first_name }} {{ $teacher?->last_name }}</td>
                                    <td>{{ $classroom->period }}</td>
                                    <td>{{ $classroom->enrollments_count }} / {{ $classroom->max_capacity }}</td>
                                    <td>{{ $classroom->sessions_count }}</td>
                                    <td>{{ $classroom->min_attendance_pct }}%</td>
                                    <td>
                                        @if($activeCode)
                                            <span class="aula-codigo" style="display:block;font-weight:700;color:#F28B2C;letter-spacing:.1rem;">
                                                {{ $activeCode->code }}
                                            </span>
                                            <span class="code-countdown"
                                                  data-expires-at="{{ $latestCode->expires_at->toIso8601String() }}"
                                                  style="display:block;font-size:.75rem;color:#6b7280;">Calculando tiempo...</span>
                                            <span class="code-status" style="display:block;font-size:.72rem;color:#065f46;">Código activo</span>
                                        @elseif($latestCode)
                                            <span style="display:block;font-size:.75rem;color:#9ca3af;">Sin código activo</span>
                                            <span class="code-countdown"
                                                  data-expires-at="{{ $latestCode->expires_at->toIso8601String() }}"
                                                  style="display:block;font-size:.75rem;color:#92400e;">Expirado</span>
                                            <span class="code-status" style="display:block;font-size:.72rem;color:#92400e;">Último código expirado</span>
                                        @else
                                            <span style="font-size:.75rem;color:#9ca3af;">Sin código activo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status {{ $classroom->is_active ? 'status-open' : 'status-closed' }}">
                                            {{ $classroom->is_active ? 'Abierto' : 'Cerrado' }}
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        @if($activeCode && auth()->id() === $classroom->teacher_id)
                                            <button type="button"
                                                class="action-btn"
                                                title="Copiar código"
                                                onclick="copiarCodigo('{{ $activeCode->code }}', this)">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        @endif
                                        <button class="action-btn" title="Ver detalle"
                                            onclick="abrirDetalle(
                                                '{{ $classroom->id }}',
                                                '{{ addslashes($classroom->subject_name) }}',
                                                '{{ $classroom->period }}',
                                                {{ $classroom->enrollments_count }},
                                                {{ $classroom->max_capacity }},
                                                {{ $classroom->sessions_count }},
                                                {{ $classroom->min_attendance_pct }},
                                                '{{ addslashes(($teacher?->first_name ?? '') . ' ' . ($teacher?->last_name ?? '')) }}',
                                                {{ $classroom->is_active ? 'true' : 'false' }},
                                                '{{ $activeCode?->code ?? '' }}'
                                            )">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($classroom->is_active && auth()->id() === $classroom->teacher_id)
                                            <form method="POST"
                                                  action="{{ route('aulas.generate-code', $classroom->id) }}"
                                                  style="display:inline"
                                                  id="tableCodeForm-{{ $classroom->id }}">
                                                @csrf
                                                <button type="submit" class="action-btn" title="Regenerar código de invitación">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endunless

</div>

@if($isStudent)
{{-- MODAL: INSCRIPCIÓN ALUMNO --}}
<div class="modal-overlay {{ !empty($showEnrollmentModal) ? 'active' : '' }}" id="modalInscripcionAlumno">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Unirme a un aula</h3>
                <p class="modal-subtitle">Ingresa el código que te compartió tu docente</p>
            </div>
            <button class="modal-close" type="button" onclick="cerrarModal('modalInscripcionAlumno')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('enrollments.store') }}">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $enrollmentRedirectUrl ?? route('aulas.index') }}">
            <div class="modal-body">
                <p style="margin-bottom:1rem;color:#555;font-size:.9rem;line-height:1.5;">
                    Si te registraste sin código, aquí puedes unirte al aula correcta.
                    Tu cuenta se asociará automáticamente a la institución del docente.
                </p>
                <div class="form-group">
                    <label class="form-label" for="student_invitation_code">Código de invitación</label>
                    <input type="text"
                           id="student_invitation_code"
                           name="invitation_code"
                           class="form-input"
                           placeholder="Ej. ABC12345"
                           value="{{ old('invitation_code') }}"
                           style="text-transform:uppercase;letter-spacing:.1rem;"
                           required>
                    @error('invitation_code')
                        <span style="display:block;margin-top:.4rem;color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalInscripcionAlumno')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="fas fa-user-plus"></i> Inscribirme
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL: DETALLE AULA --}}
<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="detalleTitulo">—</h3>
                <p class="modal-subtitle" id="detalleSubtitulo">—</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalDetalle')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Docente</span>
                    <span class="detalle-value" id="detalleDocente">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estado del ciclo</span>
                    <span class="detalle-value" id="detalleEstado">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Período</span>
                    <span class="detalle-value" id="detallePeriodo">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Alumnos inscritos</span>
                    <span class="detalle-value" id="detalleAlumnos">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Sesiones realizadas</span>
                    <span class="detalle-value" id="detalleSesiones">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Asistencia mínima</span>
                    <span class="detalle-value" id="detalleMinAsist">—</span>
                </div>
                <div class="detalle-item" id="detalleCodigoWrap">
                    <span class="detalle-label">Código activo</span>
                    <span class="detalle-value" id="detalleCodigo"
                          style="font-size:1.2rem;font-weight:800;letter-spacing:.2rem;color:#F28B2C;">—</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalDetalle')">Cerrar</button>
            <a href="{{ $isStudent ? route('asistencias.alumno') : route('asistencias.docente') }}" class="btn btn-primary btn-md">
                <i class="fas fa-clipboard-check"></i>
                Ir a asistencias
            </a>
        </div>
    </div>
</div>

@unless($isStudent)
{{-- MODAL: REGENERAR CÓDIGO EXPIRADO --}}
<div class="modal-overlay" id="modalRegenCode">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Código expirado</h3>
                <p class="modal-subtitle" id="regenSubtitle">Aula</p>
            </div>
            <button class="modal-close" type="button" onclick="cerrarModal('modalRegenCode')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>El código de inscripción expiró. ¿Quieres regenerarlo ahora?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" type="button" onclick="cerrarModal('modalRegenCode')">Más tarde</button>
            <button class="btn btn-primary btn-md" type="button" id="regenConfirmBtn">
                <i class="fas fa-key"></i> Regenerar código
            </button>
        </div>
    </div>
</div>
@endunless

@push('scripts')
<script>
    let pendingRegenerateClassroomId = null;
    const promptedExpiredCodes = new Set();
    const isStudentView = @json($isStudent);

    // ── Modales ───────────────────────────────────────────────────────────────
    function abrirModal(id) { document.getElementById(id)?.classList.add('active'); }
    function cerrarModal(id){ document.getElementById(id)?.classList.remove('active'); }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarModal('modalDetalle');
            cerrarModal('modalRegenCode');
            cerrarModal('modalInscripcionAlumno');
        }
    });

    @if($isStudent && !empty($showEnrollmentModal))
    document.addEventListener('DOMContentLoaded', () => abrirModal('modalInscripcionAlumno'));
    @endif
    @if($isStudent && $errors->has('invitation_code'))
    document.addEventListener('DOMContentLoaded', () => abrirModal('modalInscripcionAlumno'));
    @endif

    function abrirDetalle(id, nombre, periodo, alumnos, capacidad, sesiones, minAsist, docente, activo, codigo) {
        document.getElementById('detalleTitulo').textContent    = nombre;
        document.getElementById('detalleSubtitulo').textContent = periodo;
        document.getElementById('detalleDocente').textContent   = docente.trim() || '—';
        document.getElementById('detallePeriodo').textContent   = periodo;
        document.getElementById('detalleAlumnos').textContent   = `${alumnos} / ${capacidad} cupo`;
        document.getElementById('detalleSesiones').textContent  = sesiones;
        document.getElementById('detalleMinAsist').textContent  = `${minAsist}%`;
        document.getElementById('detalleCodigo').textContent    = codigo || 'Sin código activo';
        const estadoEl = document.getElementById('detalleEstado');
        estadoEl.textContent = activo ? 'Abierto' : 'Cerrado';
        estadoEl.className   = 'status ' + (activo ? 'status-open' : 'status-closed');
        abrirModal('modalDetalle');
    }

    if (!isStudentView) {
    // ── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.mod-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.mod-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const tabActiva = tab.dataset.tab;
            document.querySelectorAll('.aula-card').forEach(card => {
                card.style.display = (tabActiva === 'todas' || card.dataset.tab === tabActiva) ? '' : 'none';
            });
            document.querySelectorAll('#tablaAulas tbody tr').forEach(tr => {
                tr.style.display = (tabActiva === 'todas' || tr.dataset.tab === tabActiva) ? '' : 'none';
            });
        });
    });

    // ── Toggle vista grid/list ────────────────────────────────────────────────
    document.getElementById('btnGrid').addEventListener('click', () => {
        document.getElementById('vistaGrid').style.display = 'block';
        document.getElementById('vistaList').style.display = 'none';
        document.getElementById('btnGrid').classList.add('active');
        document.getElementById('btnList').classList.remove('active');
    });
    document.getElementById('btnList').addEventListener('click', () => {
        document.getElementById('vistaGrid').style.display = 'none';
        document.getElementById('vistaList').style.display = 'block';
        document.getElementById('btnList').classList.add('active');
        document.getElementById('btnGrid').classList.remove('active');
    });

    // ── Búsqueda ──────────────────────────────────────────────────────────────
    document.getElementById('buscarAula').addEventListener('input', function () {
        const texto = this.value.toLowerCase();
        document.querySelectorAll('.aula-card').forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
        document.querySelectorAll('#tablaAulas tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    // ── Copiar código al portapapeles ─────────────────────────────────────────
    function copiarCodigo(code, btn) {
        const fallback = () => {
            const ta = document.createElement('textarea');
            ta.value = code;
            ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); } catch (e) { /* noop */ }
            document.body.removeChild(ta);
        };
        const onDone = () => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        };
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(code).then(onDone).catch(() => { fallback(); onDone(); });
        } else {
            fallback();
            onDone();
        }
    }

    function formatCountdown(expiresAt) {
        const ms = new Date(expiresAt).getTime() - Date.now();
        if (Number.isNaN(ms) || ms <= 0) return 'Expirado';

        const totalMinutes = Math.floor(ms / 60000);
        const days = Math.floor(totalMinutes / (60 * 24));
        const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
        const minutes = totalMinutes % 60;

        return `${String(days).padStart(2, '0')}:${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function updateCodeCountdowns() {
        document.querySelectorAll('.code-countdown[data-expires-at]').forEach(el => {
            const value = formatCountdown(el.dataset.expiresAt);
            el.textContent = value === 'Expirado'
                ? 'Expirado'
                : `Tiempo restante ${value} (dd:hh:mm)`;
        });
    }

    function askToRegenerateIfExpired() {
        const cards = Array.from(document.querySelectorAll('.aula-card[data-latest-expires-at][data-can-regenerate="1"]'));

        for (const card of cards) {
            const classroomId = card.dataset.classroomId;
            const expiresAt = card.dataset.latestExpiresAt;
            const key = `${classroomId}:${expiresAt}`;
            const isExpired = formatCountdown(expiresAt) === 'Expirado';

            if (!isExpired || promptedExpiredCodes.has(key)) continue;
            promptedExpiredCodes.add(key);

            pendingRegenerateClassroomId = classroomId;
            document.getElementById('regenSubtitle').textContent = card.dataset.classroomName || 'Aula';
            abrirModal('modalRegenCode');
            break;
        }
    }

    document.getElementById('regenConfirmBtn')?.addEventListener('click', () => {
        if (!pendingRegenerateClassroomId) return;
        const form = document.getElementById(`codeForm-${pendingRegenerateClassroomId}`);
        const tableForm = document.getElementById(`tableCodeForm-${pendingRegenerateClassroomId}`);
        if (form) {
            form.submit();
            return;
        }
        if (tableForm) tableForm.submit();
    });

    updateCodeCountdowns();
    askToRegenerateIfExpired();
    setInterval(updateCodeCountdowns, 30000);
    setInterval(askToRegenerateIfExpired, 30000);
    }
</script>
@endpush

@endsection
