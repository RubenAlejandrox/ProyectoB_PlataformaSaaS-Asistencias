{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * @version        2.0.0
 * @modificado     26/05/2026 — Vista conectada a JustificationController por rol.
 */
--}}

@extends('layouts.app')

@section('title', 'Justificantes - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/justificantes.css') }}">
@endpush

@section('content')
@php
    $subtitles = [
        'Student'       => 'Envía y consulta tus solicitudes de justificante',
        'Teacher'       => 'Revisa y dictamina justificantes de tus aulas',
        'Administrator' => 'Supervisión de justificantes de la institución',
    ];
    $subtitle = $subtitles[$role] ?? 'Solicitud y resolución de justificantes de inasistencia';
@endphp

<div class="main-content">

    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Justificantes</h1>
                <p>{{ $subtitle }}</p>
            </div>
            @if($canCreate)
                <div class="header-actions">
                    <button type="button" class="btn btn-primary btn-md" onclick="abrirModalSolicitud()"
                            @if($absencesWithoutJustification->isEmpty()) disabled title="No tienes faltas pendientes de justificar" style="opacity:.5;cursor:not-allowed;" @endif>
                        <i class="fas fa-plus"></i>
                        Nueva solicitud
                    </button>
                </div>
            @endif
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
    @if($errors->any() && !$errors->has('general'))
        <div style="display:flex;align-items:flex-start;gap:.6rem;background:#fee2e2;border-left:4px solid #DC3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#991b1b;">
            <i class="fas fa-exclamation-circle" style="margin-top:.15rem;"></i>
            <ul style="margin:0;padding-left:1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-inbox"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total solicitudes</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending'] }}</span>
                <span class="kpi-label">Pendientes</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--success">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['approved'] }}</span>
                <span class="kpi-label">Aprobados</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--danger">
            <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['rejected'] }}</span>
                <span class="kpi-label">Rechazados</span>
            </div>
        </div>
    </div>

    <div class="mod-tabs">
        <button type="button" class="mod-tab active" data-tab="todos">
            <i class="fas fa-list"></i> Todos
        </button>
        <button type="button" class="mod-tab" data-tab="pendientes">
            <i class="fas fa-hourglass-half"></i> Pendientes
            @if($stats['pending'] > 0)
                <span class="tab-badge">{{ $stats['pending'] }}</span>
            @endif
        </button>
        <button type="button" class="mod-tab" data-tab="resueltos">
            <i class="fas fa-check-double"></i> Resueltos
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-alt"></i>
                @if($role === 'Student')
                    Mis solicitudes
                @else
                    Solicitudes de justificante
                @endif
            </h3>
            <div class="card-actions">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Buscar alumno, aula o motivo..." id="buscarJustificante">
                </div>
                @if($classrooms->isNotEmpty())
                    <select class="filter-select" id="filtroAula">
                        <option value="">Todas las aulas</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}">{{ $c->subject_name }} — {{ $c->period }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="dynamic-table" id="tablaJustificantes">
                    <thead>
                        <tr>
                            <th>#</th>
                            @if($role !== 'Student')
                                <th>Alumno</th>
                            @endif
                            <th>Aula</th>
                            <th>Fecha de falta</th>
                            <th>Motivo</th>
                            <th>Fecha solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($justifications as $index => $j)
                            @php
                                $classroom = $j->attendance?->session?->classroom;
                                $sessionDate = $j->attendance?->session?->session_date;
                                $tab = $j->status === 'pending' ? 'pendientes' : 'resueltos';
                                $studentName = trim(($j->student?->first_name ?? '') . ' ' . ($j->student?->last_name ?? ''));
                                $parts = preg_split('/\s+/', $studentName, -1, PREG_SPLIT_NO_EMPTY);
                                $initials = strtoupper(
                                    substr($parts[0] ?? '?', 0, 1) .
                                    substr($parts[1] ?? ($parts[0] ?? '?'), 0, 1)
                                );
                                $aulaLabel = $classroom
                                    ? $classroom->subject_name . ' — ' . $classroom->period
                                    : '—';
                                $reason = $j->reason ?: 'Sin motivo indicado';
                                $statusLabels = [
                                    'pending'  => ['Pendiente', 'status-pending'],
                                    'approved' => ['Aprobado', 'status-approved'],
                                    'rejected' => ['Rechazado', 'status-rejected'],
                                ];
                                [$statusLabel, $statusClass] = $statusLabels[$j->status] ?? ['Desconocido', ''];
                                $fileName = $j->file_url ? basename(parse_url($j->file_url, PHP_URL_PATH) ?: 'documento') : '';
                                $rowData = [
                                    'id'             => $j->id,
                                    'num'            => str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                                    'student'        => $studentName,
                                    'initials'       => $initials,
                                    'aula'           => $aulaLabel,
                                    'fechaFalta'     => $sessionDate?->format('d/m/Y') ?? '—',
                                    'fechaSolicitud' => $j->created_at?->format('d/m/Y') ?? '—',
                                    'motivo'         => $reason,
                                    'status'         => $statusLabel,
                                    'statusClass'    => $statusClass,
                                    'fileUrl'        => $j->file_url,
                                    'fileName'       => $fileName,
                                    'isPending'      => $j->isPending(),
                                    'reviewer'       => $j->reviewer
                                        ? trim($j->reviewer->first_name . ' ' . $j->reviewer->last_name)
                                        : null,
                                    'reviewedAt'     => $j->reviewed_at?->format('d/m/Y H:i'),
                                ];
                            @endphp
                            <tr data-tab="{{ $tab }}"
                                data-classroom-id="{{ $classroom?->id }}"
                                data-row='@json($rowData)'>
                                <td>{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}</td>
                                @if($role !== 'Student')
                                    <td>
                                        <div class="alumno-cell">
                                            <div class="avatar-sm">{{ $initials }}</div>
                                            <span>{{ $studentName ?: '—' }}</span>
                                        </div>
                                    </td>
                                @endif
                                <td>{{ $aulaLabel }}</td>
                                <td>{{ $sessionDate?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <span class="motivo-texto" title="{{ $reason }}">
                                        {{ Str::limit($reason, 40) }}
                                    </span>
                                </td>
                                <td>{{ $j->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td class="action-cell">
                                    <button type="button" class="action-btn" title="Ver detalle"
                                            onclick="verDetalle(this.closest('tr'))">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($canReview && $j->isPending())
                                        <button type="button" class="action-btn approve" title="Aprobar"
                                                onclick="dictaminar('{{ $j->id }}', 'aprobar', @json($studentName))">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="action-btn reject" title="Rechazar"
                                                onclick="dictaminar('{{ $j->id }}', 'rechazar', @json($studentName))">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="filaVacia">
                                <td colspan="{{ $role === 'Student' ? 7 : 8 }}" style="text-align:center;padding:2rem;color:#6b7280;">
                                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.5;"></i>
                                    @if($role === 'Student')
                                        No tienes solicitudes de justificante registradas.
                                    @else
                                        No hay justificantes para mostrar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modal detalle --}}
<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Detalle de justificante</h3>
                <p class="modal-subtitle" id="detalleSubtitulo">Solicitud</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModal('modalDetalle')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="detalle-grid">
                @if($role !== 'Student')
                    <div class="detalle-item">
                        <span class="detalle-label">Alumno</span>
                        <span class="detalle-value" id="detalleAlumno">—</span>
                    </div>
                @endif
                <div class="detalle-item">
                    <span class="detalle-label">Aula</span>
                    <span class="detalle-value" id="detalleAula">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Fecha de falta</span>
                    <span class="detalle-value" id="detalleFechaFalta">—</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Fecha de solicitud</span>
                    <span class="detalle-value" id="detalleFechaSolicitud">—</span>
                </div>
                <div class="detalle-item detalle-item--full">
                    <span class="detalle-label">Motivo</span>
                    <span class="detalle-value" id="detalleMotivo">—</span>
                </div>
                <div class="detalle-item detalle-item--full">
                    <span class="detalle-label">Documento adjunto</span>
                    <div id="detalleAdjunto">—</div>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estado actual</span>
                    <span id="detalleEstado">—</span>
                </div>
                <div class="detalle-item" id="detalleRevisionWrap" style="display:none;">
                    <span class="detalle-label">Revisado por</span>
                    <span class="detalle-value" id="detalleRevisor">—</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalDetalle')">Cerrar</button>
            @if($canReview)
                <button type="button" class="btn btn-danger btn-md" id="btnDetalleRechazar" style="display:none;">
                    <i class="fas fa-times"></i> Rechazar
                </button>
                <button type="button" class="btn btn-success btn-md" id="btnDetalleAprobar" style="display:none;">
                    <i class="fas fa-check"></i> Aprobar
                </button>
            @endif
        </div>
    </div>
</div>

@if($canCreate)
<div class="modal-overlay" id="modalSolicitud">
    <div class="modal modal-md">
        <form method="POST" action="{{ route('justificantes.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Nueva solicitud de justificante</h3>
                    <p class="modal-subtitle">Selecciona la falta y adjunta tu comprobante</p>
                </div>
                <button type="button" class="modal-close" onclick="cerrarModal('modalSolicitud')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="attendance_id">Falta a justificar <span class="required">*</span></label>
                    <select class="form-input" name="attendance_id" id="attendance_id" required>
                        <option value="">Selecciona la sesión con falta...</option>
                        @foreach($absencesWithoutJustification as $att)
                            @php
                                $c = $att->session?->classroom;
                                $label = ($c ? $c->subject_name . ' — ' . $c->period : 'Aula')
                                    . ' · ' . ($att->session?->session_date?->format('d/m/Y') ?? '—');
                            @endphp
                            <option value="{{ $att->id }}" @selected(old('attendance_id') == $att->id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reason">Motivo</label>
                    <textarea class="form-textarea" name="reason" id="reason" rows="3"
                              placeholder="Describe el motivo de tu inasistencia...">{{ old('reason') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Documento de respaldo <span class="required">*</span></label>
                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <p class="upload-text">Arrastra un archivo o <span class="upload-link">haz clic para seleccionar</span></p>
                        <p class="upload-hint">PDF, JPG o PNG — máx. 5 MB</p>
                        <input type="file" name="file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png" required
                               style="display:none" onchange="mostrarArchivo(this)">
                    </div>
                    <div class="archivo-seleccionado" id="archivoSeleccionado" style="display:none">
                        <i class="fas fa-file-alt"></i>
                        <span id="nombreArchivo"></span>
                        <button type="button" class="btn-remove-file" onclick="quitarArchivo()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalSolicitud')">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="fas fa-paper-plane"></i> Enviar solicitud
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canReview)
<div class="modal-overlay" id="modalDictamen">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="dictamenTitulo">Confirmar dictamen</h3>
                <p class="modal-subtitle">Esta acción no se puede deshacer</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModal('modalDictamen')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="dictamenMensaje"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-md" onclick="cerrarModal('modalDictamen')">Cancelar</button>
            <button type="button" class="btn btn-primary btn-md" id="dictamenConfirmar">Confirmar</button>
        </div>
    </div>
</div>

<form id="formReview" method="POST" action="" style="display:none;">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" id="reviewStatus" value="">
</form>
@endif

@push('scripts')
<script>
    const canReview = @json($canReview);
    const reviewBaseUrl = @json(url('/justificantes'));
    let detalleActual = null;
    let dictamenPendiente = null;

    document.querySelectorAll('.mod-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.mod-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            filtrarFilas();
        });
    });

    function filtrarFilas() {
        const tabActiva = document.querySelector('.mod-tab.active')?.dataset.tab || 'todos';
        const texto = (document.getElementById('buscarJustificante')?.value || '').toLowerCase();
        const aulaId = document.getElementById('filtroAula')?.value || '';

        document.querySelectorAll('#tablaJustificantes tbody tr[data-tab]').forEach(tr => {
            const matchTab = tabActiva === 'todos' || tr.dataset.tab === tabActiva;
            const matchText = !texto || tr.textContent.toLowerCase().includes(texto);
            const matchAula = !aulaId || tr.dataset.classroomId === aulaId;
            tr.style.display = matchTab && matchText && matchAula ? '' : 'none';
        });
    }

    document.getElementById('buscarJustificante')?.addEventListener('input', filtrarFilas);
    document.getElementById('filtroAula')?.addEventListener('change', filtrarFilas);

    function abrirModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function abrirModalSolicitud() {
        abrirModal('modalSolicitud');
    }

    function verDetalle(row) {
        const data = JSON.parse(row.dataset.row);
        detalleActual = data;

        document.getElementById('detalleSubtitulo').textContent = 'Solicitud #' + data.num;
        const alumnoEl = document.getElementById('detalleAlumno');
        if (alumnoEl) alumnoEl.textContent = data.student;
        document.getElementById('detalleAula').textContent = data.aula;
        document.getElementById('detalleFechaFalta').textContent = data.fechaFalta;
        document.getElementById('detalleFechaSolicitud').textContent = data.fechaSolicitud;
        document.getElementById('detalleMotivo').textContent = data.motivo;

        const adjunto = document.getElementById('detalleAdjunto');
        if (data.fileUrl) {
            adjunto.innerHTML = `
                <a href="${data.fileUrl}" target="_blank" rel="noopener" class="adjunto-placeholder" style="text-decoration:none;">
                    <i class="fas fa-file-pdf"></i>
                    <span>${data.fileName || 'Ver documento'}</span>
                    <span class="adjunto-size">Abrir</span>
                </a>`;
        } else {
            adjunto.textContent = 'Sin archivo';
        }

        document.getElementById('detalleEstado').innerHTML =
            `<span class="status ${data.statusClass}">${data.status}</span>`;

        const revWrap = document.getElementById('detalleRevisionWrap');
        if (data.reviewer) {
            revWrap.style.display = '';
            document.getElementById('detalleRevisor').textContent =
                data.reviewer + (data.reviewedAt ? ' · ' + data.reviewedAt : '');
        } else {
            revWrap.style.display = 'none';
        }

        if (canReview) {
            const show = data.isPending;
            const btnA = document.getElementById('btnDetalleAprobar');
            const btnR = document.getElementById('btnDetalleRechazar');
            btnA.style.display = show ? '' : 'none';
            btnR.style.display = show ? '' : 'none';
            btnA.onclick = () => { cerrarModal('modalDetalle'); dictaminar(data.id, 'aprobar', data.student); };
            btnR.onclick = () => { cerrarModal('modalDetalle'); dictaminar(data.id, 'rechazar', data.student); };
        }

        abrirModal('modalDetalle');
    }

    function dictaminar(id, accion, nombre) {
        if (!canReview) return;

        const esAprobar = accion === 'aprobar';
        document.getElementById('dictamenTitulo').textContent =
            esAprobar ? 'Confirmar aprobación' : 'Confirmar rechazo';
        document.getElementById('dictamenMensaje').textContent =
            esAprobar
                ? `¿Aprobar el justificante de ${nombre}? La falta quedará marcada como justificada.`
                : `¿Rechazar el justificante de ${nombre}? La falta se mantendrá sin justificación.`;

        dictamenPendiente = { id, status: esAprobar ? 'approved' : 'rejected' };

        const btn = document.getElementById('dictamenConfirmar');
        btn.className = esAprobar ? 'btn btn-success btn-md' : 'btn btn-danger btn-md';
        btn.innerHTML = esAprobar
            ? '<i class="fas fa-check"></i> Aprobar'
            : '<i class="fas fa-times"></i> Rechazar';
        btn.onclick = confirmarDictamen;

        abrirModal('modalDictamen');
    }

    function confirmarDictamen() {
        if (!dictamenPendiente) return;
        const form = document.getElementById('formReview');
        form.action = reviewBaseUrl + '/' + dictamenPendiente.id + '/review';
        document.getElementById('reviewStatus').value = dictamenPendiente.status;
        form.submit();
    }

    @if($canCreate)
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const input = document.getElementById('fileInput');
            if (e.dataTransfer.files[0]) {
                input.files = e.dataTransfer.files;
                mostrarArchivo(input);
            }
        });
    }

    function mostrarArchivo(input) {
        if (input.files[0]) {
            document.getElementById('uploadArea').style.display = 'none';
            document.getElementById('archivoSeleccionado').style.display = 'flex';
            document.getElementById('nombreArchivo').textContent = input.files[0].name;
        }
    }

    function quitarArchivo() {
        document.getElementById('fileInput').value = '';
        document.getElementById('uploadArea').style.display = 'block';
        document.getElementById('archivoSeleccionado').style.display = 'none';
    }

    @if($errors->has('attendance_id') || $errors->has('file') || old('attendance_id'))
        abrirModalSolicitud();
    @endif
    @endif

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalDetalle', 'modalSolicitud', 'modalDictamen'].forEach(id => {
                const el = document.getElementById(id);
                if (el) cerrarModal(id);
            });
        }
    });
</script>
@endpush

@endsection
