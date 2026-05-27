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
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Aulas</h1>
                <p>Gestión de aulas y grupos de la institución</p>
            </div>
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

    {{-- BANNER: CÓDIGO DE INVITACIÓN GENERADO --}}
    @if(session('invitation_code'))
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
                    $activeCode = $classroom->invitationCodes
                        ->where('is_used', false)
                        ->where('expires_at', '>', now())
                        ->sortByDesc('created_at')
                        ->first();
                @endphp
                <div class="aula-card {{ !$classroom->is_active ? 'aula-card--cerrada' : '' }}"
                     data-tab="{{ $tabStatus }}">
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
                        <span class="aula-codigo">
                            {{ $activeCode?->code ?? 'Sin código' }}
                        </span>
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
                            @if($classroom->is_active && auth()->id() === $classroom->teacher_id)
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
                            @if(auth()->id() === $classroom->teacher_id || auth()->user()->hasRole('Administrator'))
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
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-secondary)">
                    <i class="fas fa-chalkboard" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
                    <p>No tienes aulas registradas aún.</p>
                    @if($stats['can_create'])
                        <a href="{{ route('aulas.create') }}" class="btn btn-primary btn-md" style="margin-top:1rem;">
                            <i class="fas fa-plus"></i> Crear primera aula
                        </a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

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
                                <th>Ciclo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classrooms as $classroom)
                                @php
                                    $tabStatus = $classroom->is_active ? 'abierto' : 'cerrado';
                                    $teacher   = $classroom->teacher;
                                    $activeCode = $classroom->invitationCodes
                                        ->where('is_used', false)
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
                                        <span class="status {{ $classroom->is_active ? 'status-open' : 'status-closed' }}">
                                            {{ $classroom->is_active ? 'Abierto' : 'Cerrado' }}
                                        </span>
                                    </td>
                                    <td class="action-cell">
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
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

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
            <a href="{{ route('asistencias.docente') }}" class="btn btn-primary btn-md">
                <i class="fas fa-clipboard-check"></i>
                Ir a asistencias
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ── Modales ───────────────────────────────────────────────────────────────
    function abrirModal(id) { document.getElementById(id).classList.add('active'); }
    function cerrarModal(id){ document.getElementById(id).classList.remove('active'); }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal('modalDetalle'); });

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

    // ── Detalle ───────────────────────────────────────────────────────────────
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
</script>
@endpush

@endsection
