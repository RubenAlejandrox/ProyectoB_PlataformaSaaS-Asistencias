{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Módulo de Gestión de Instituciones — Alta, edición
 *                 y desactivación de instituciones (RF-02).
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 * @version        1.0.0
 * @creado         07/05/2026
 * @modificado     07/05/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 07/05/2026  | Rubén Alejandro   | Implementación inicial Instituciones (RF-02).
 */
--}}

@extends('layouts.app')

@section('title', 'Instituciones - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/instituciones.css') }}">
@endpush

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Instituciones</h1>
                <p>Gestión de instituciones registradas en la plataforma</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary btn-md" onclick="abrirModal('modalCrear')">
                    <i class="fas fa-plus"></i>
                    Nueva institución
                </button>
            </div>
        </div>
    </div>

    {{-- SUCCESS / ERROR --}}
    @if(session('success'))
        <div class="alert-success" style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border:1px solid #6ee7b7;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->has('general'))
        <div class="alert-error" style="display:flex;align-items:center;gap:.6rem;background:#fee2e2;border:1px solid #fca5a5;border-left:4px solid #DC3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#991b1b;">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $errors->first('general') }}</span>
        </div>
    @endif

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-building"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total instituciones</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['active'] }}</span>
                <span class="kpi-label">Activas</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-pause-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['inactive'] }}</span>
                <span class="kpi-label">Inactivas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-chalkboard"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['classrooms'] }}</span>
                <span class="kpi-label">Aulas registradas</span>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-building"></i>
                Listado de instituciones
            </h3>
            <div class="card-actions">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input"
                           placeholder="Buscar institución..."
                           id="buscarInstitucion">
                </div>
                <select class="filter-select" id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table" id="tablaInstituciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Institución</th>
                            <th>Aulas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($institutions as $inst)
                        <tr data-estado="{{ $inst->is_active ? '1' : '0' }}">
                            <td>{{ str_pad($loop->index + 1, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="inst-cell {{ !$inst->is_active ? 'inst-cell--inactive' : '' }}">
                                    <div class="inst-icon {{ !$inst->is_active ? 'inst-icon--inactive' : '' }}">
                                        @if($inst->logo_url)
                                            <img src="{{ $inst->logo_url }}"
                                                 alt="{{ $inst->name }}"
                                                 style="width:32px;height:32px;object-fit:cover;border-radius:4px;">
                                        @else
                                            <i class="fas fa-university"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="inst-nombre">{{ $inst->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-num">{{ $inst->classrooms_count }}</span></td>
                            <td>
                                <span class="status {{ $inst->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $inst->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="action-cell">
                                {{-- Ver --}}
                                <button class="action-btn" title="Ver detalle"
                                    onclick="abrirDetalle(
                                        '{{ $inst->id }}',
                                        '{{ addslashes($inst->name) }}',
                                        {{ $inst->classrooms_count }},
                                        {{ $inst->is_active ? 'true' : 'false' }},
                                        '{{ $inst->logo_url ?? '' }}'
                                    )">
                                    <i class="fas fa-eye"></i>
                                </button>

                                {{-- Editar --}}
                                @if($inst->is_active)
                                <button class="action-btn" title="Editar"
                                    onclick="abrirEditar(
                                        '{{ $inst->id }}',
                                        '{{ addslashes($inst->name) }}',
                                        '{{ $inst->logo_url ?? '' }}'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif

                                {{-- Toggle estado --}}
                                <form method="POST"
                                      action="{{ route('instituciones.toggle', $inst->id) }}"
                                      style="display:inline"
                                      id="toggleForm-{{ $inst->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button"
                                            class="action-btn {{ $inst->is_active ? 'danger' : '' }}"
                                            title="{{ $inst->is_active ? 'Desactivar' : 'Reactivar' }}"
                                            onclick="confirmarToggle(
                                                '{{ $inst->id }}',
                                                '{{ addslashes($inst->name) }}',
                                                {{ $inst->is_active ? 'true' : 'false' }}
                                            )">
                                        <i class="fas {{ $inst->is_active ? 'fa-ban' : 'fa-redo' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2rem;color:var(--text-secondary)">
                                <i class="fas fa-building" style="font-size:2rem;margin-bottom:.5rem;display:block;"></i>
                                No hay instituciones registradas aún.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="pagination" style="padding:1rem;">
                {{ $institutions->links() }}
            </div>
        </div>
    </div>

</div>

{{-- MODAL: NUEVA INSTITUCIÓN --}}
<div class="modal-overlay" id="modalCrear">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Nueva Institución</h3>
                <p class="modal-subtitle">Completa los datos de la institución</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalCrear')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('instituciones.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                @if($errors->has('name'))
                    <div class="alert-error" style="margin-bottom:1rem;display:flex;gap:.5rem;align-items:center;background:#fee2e2;border-left:4px solid #DC3545;padding:.6rem 1rem;border-radius:6px;color:#991b1b;font-size:.9rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $errors->first('name') }}
                    </div>
                @endif
                <div class="form-grid">
                    <div class="form-group form-group--full">
                        <label class="form-label">Nombre de la institución <span class="required">*</span></label>
                        <input type="text"
                               name="name"
                               class="form-input"
                               placeholder="Ej. CBTIS 168"
                               value="{{ old('name') }}"
                               required>
                    </div>
                    <div class="form-group form-group--full">
                        <label class="form-label">
                            Logo
                            <span style="font-size:.75rem;color:var(--text-secondary);font-weight:400;">(PNG/JPG · máx. 2MB)</span>
                        </label>
                        <input type="file"
                               name="logo"
                               class="form-input"
                               accept=".png,.jpg,.jpeg"
                               style="padding:.6rem;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalCrear')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="fas fa-save"></i>
                    Guardar institución
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: VER DETALLE --}}
<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="detalleTitulo">—</h3>
                <p class="modal-subtitle">Detalle de la institución</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalDetalle')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="detalle-grid">
                <div class="detalle-item" id="detalleLogoWrap" style="display:none;">
                    <span class="detalle-label">Logo</span>
                    <img id="detalleLogo" src="" alt="Logo"
                         style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estado</span>
                    <span class="status" id="detalleEstado">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Aulas registradas</span>
                    <span class="detalle-value" id="detalleAulas">—</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalDetalle')">Cerrar</button>
        </div>
    </div>
</div>

{{-- MODAL: EDITAR --}}
<div class="modal-overlay" id="modalEditar">
    <div class="modal modal-md">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Editar Institución</h3>
                <p class="modal-subtitle" id="editarSubtitulo">—</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalEditar')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="formEditar" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group form-group--full">
                        <label class="form-label">Nombre <span class="required">*</span></label>
                        <input type="text" name="name" id="editarNombre" class="form-input" required>
                    </div>
                    <div class="form-group form-group--full">
                        <label class="form-label">
                            Nuevo logo
                            <span style="font-size:.75rem;color:var(--text-secondary);font-weight:400;">(opcional · PNG/JPG · máx. 2MB)</span>
                        </label>
                        <input type="file" name="logo" class="form-input"
                               accept=".png,.jpg,.jpeg" style="padding:.6rem;">
                        <div id="editarLogoActual" style="margin-top:.5rem;display:none;">
                            <img id="editarLogoImg" src="" alt="Logo actual"
                                 style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">
                            <span style="font-size:.8rem;color:var(--text-secondary);margin-left:.5rem;">Logo actual</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="fas fa-save"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: CONFIRMAR TOGGLE --}}
<div class="modal-overlay" id="modalConfirmar">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="confirmarTitulo">Confirmar acción</h3>
                <p class="modal-subtitle" id="confirmarSubtitulo"></p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalConfirmar')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmarMensaje"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalConfirmar')">Cancelar</button>
            <button class="btn btn-danger btn-md" id="confirmarBtn" onclick="ejecutarToggle()">Confirmar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let toggleTargetId = null;

    // ── Modales ──────────────────────────────────────────────────────────────
    function abrirModal(id) { document.getElementById(id).classList.add('active'); }
    function cerrarModal(id){ document.getElementById(id).classList.remove('active'); }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape')
            ['modalCrear','modalDetalle','modalEditar','modalConfirmar'].forEach(cerrarModal);
    });

    // ── Detalle ──────────────────────────────────────────────────────────────
    function abrirDetalle(id, nombre, aulas, activa, logo) {
        document.getElementById('detalleTitulo').textContent = nombre;
        document.getElementById('detalleAulas').textContent  = aulas;
        const estadoEl = document.getElementById('detalleEstado');
        estadoEl.textContent  = activa ? 'Activa' : 'Inactiva';
        estadoEl.className    = 'status ' + (activa ? 'status-active' : 'status-inactive');
        const logoWrap = document.getElementById('detalleLogoWrap');
        if (logo) {
            document.getElementById('detalleLogo').src = logo;
            logoWrap.style.display = 'block';
        } else {
            logoWrap.style.display = 'none';
        }
        abrirModal('modalDetalle');
    }

    // ── Editar ───────────────────────────────────────────────────────────────
    function abrirEditar(id, nombre, logo) {
        document.getElementById('editarSubtitulo').textContent = nombre;
        document.getElementById('editarNombre').value          = nombre;
        document.getElementById('formEditar').action =
            `/instituciones/${id}`;
        const logoWrap = document.getElementById('editarLogoActual');
        if (logo) {
            document.getElementById('editarLogoImg').src = logo;
            logoWrap.style.display = 'flex';
            logoWrap.style.alignItems = 'center';
        } else {
            logoWrap.style.display = 'none';
        }
        abrirModal('modalEditar');
    }

    // ── Toggle estado ────────────────────────────────────────────────────────
    function confirmarToggle(id, nombre, activa) {
        toggleTargetId = id;
        const accion   = activa ? 'Desactivar' : 'Reactivar';
        document.getElementById('confirmarTitulo').textContent    = `${accion} institución`;
        document.getElementById('confirmarSubtitulo').textContent = activa
            ? 'Las aulas activas quedarán suspendidas'
            : 'Se restaurará el acceso a sus aulas';
        document.getElementById('confirmarMensaje').textContent   =
            `¿${accion} la institución "${nombre}"?`;
        const btn = document.getElementById('confirmarBtn');
        btn.className   = activa ? 'btn btn-danger btn-md' : 'btn btn-primary btn-md';
        btn.textContent = accion;
        abrirModal('modalConfirmar');
    }

    function ejecutarToggle() {
        if (toggleTargetId) {
            document.getElementById(`toggleForm-${toggleTargetId}`).submit();
        }
    }

    // ── Búsqueda y filtro ────────────────────────────────────────────────────
    function filtrar() {
        const texto  = document.getElementById('buscarInstitucion').value.toLowerCase();
        const estado = document.getElementById('filtroEstado').value;
        document.querySelectorAll('#tablaInstituciones tbody tr').forEach(tr => {
            const nombre  = tr.querySelector('.inst-nombre')?.textContent.toLowerCase() ?? '';
            const trEst   = tr.dataset.estado;
            const muestra = (!texto || nombre.includes(texto)) && (!estado || trEst === estado);
            tr.style.display = muestra ? '' : 'none';
        });
    }
    document.getElementById('buscarInstitucion').addEventListener('input', filtrar);
    document.getElementById('filtroEstado').addEventListener('change', filtrar);

    // ── Abrir modal crear si hay errores de validación ───────────────────────
    @if($errors->hasAny(['name', 'logo']))
        document.addEventListener('DOMContentLoaded', () => abrirModal('modalCrear'));
    @endif
</script>
@endpush

@endsection