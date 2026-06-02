@extends('layouts.app')

@section('title', 'Asistencias — Docente - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencias-docente.css') }}">
@endpush

@section('content')
<div class="main-content" x-data="docenteAsistencias()" x-init="init()">

    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Control de Asistencias</h1>
                @if($classroom)
                    <p>{{ $classroom->subject_name }} — Grupo {{ $classroom->grupo }} · {{ $classroom->period }}</p>
                @else
                    <p>Selecciona un aula para comenzar</p>
                @endif
            </div>
            <div class="header-actions">
                @if($classrooms->count() > 1)
                    <div class="switch-aula-box">
                        <div class="switch-aula-title">
                            <i class="fas fa-random"></i>
                            Cambiar aula / grupo
                        </div>
                        <form method="GET" action="{{ route('asistencias.docente') }}">
                            <label for="classroomSwitcher" class="switch-aula-label">Selecciona el grupo que vas a gestionar:</label>
                            <div class="switch-aula-select-wrap">
                                <select id="classroomSwitcher" name="classroom" class="filter-select switch-aula-select" onchange="this.form.submit()">
                                    @foreach($classrooms as $c)
                                        <option value="{{ $c->id }}" @selected($classroom && $classroom->id === $c->id)>
                                            {{ $c->subject_name }} — Grupo {{ $c->grupo }} ({{ $c->period }})
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fas fa-chevron-down switch-aula-chevron" aria-hidden="true"></i>
                            </div>
                            <p class="switch-aula-help">
                                Al cambiar de aula, se actualizan la clave, los alumnos y la sesión del grupo seleccionado.
                            </p>
                        </form>
                    </div>
                @endif
                @if($classroom)
                    <span class="badge badge-info">
                        <i class="fas fa-users"></i>
                        {{ $stats['enrolled_count'] }} alumnos inscritos
                    </span>
                    <span class="badge badge-success" x-show="sesionActiva" style="display:none;">
                        <i class="fas fa-circle pulse"></i>
                        Sesión activa
                    </span>
                @endif
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

    @if(!$classroom)
        <div class="card">
            <div class="card-body">
                <p>No tienes aulas activas. <a href="{{ route('aulas.index') }}">Crea un aula</a> para iniciar sesiones de asistencia.</p>
            </div>
        </div>
    @else

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total_sessions'] }}</span>
                <span class="kpi-label">Sesiones realizadas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" x-text="contadorAsistencias">{{ $stats['present_today'] }}</span>
                <span class="kpi-label">Asistieron hoy</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['at_risk'] }}</span>
                <span class="kpi-label">Alumnos en riesgo</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['pending_justif'] }}</span>
                <span class="kpi-label">Justificantes pendientes</span>
            </div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel-left">
            <div class="card card--clave">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key"></i> Clave de Sesión</h3>
                    <span class="badge" :class="claveActiva ? 'badge-success' : 'badge-muted'" x-text="claveActiva ? 'Sesión activa' : 'Sin clave activa'"></span>
                </div>
                <div class="card-body clave-body">

                    @if(!$todaySession)
                        <p class="clave-desc">Abre una sesión de hoy para generar la clave de asistencia.</p>
                        <form method="POST" action="{{ route('asistencias.docente.sesion') }}">
                            @csrf
                            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                            <button type="submit" class="btn btn-primary btn-lg btn-full">
                                <i class="fas fa-play-circle"></i> Abrir sesión de hoy
                            </button>
                        </form>
                    @elseif(!$activeSession)
                        <p class="clave-desc" style="margin-bottom:1rem;">
                            La sesión de hoy está cerrada. Puedes corregir estados en la tabla de alumnos.
                        </p>
                    @else
                        <div x-show="!claveActiva">
                            <p class="clave-desc">Genera una clave alfanumérica de 8 caracteres para que tus alumnos registren su asistencia.</p>
                            <div class="clave-config">
                                <label class="form-label">Tiempo para registrar asistencia</label>
                                <div class="duracion-options">
                                    @foreach($keyDurations as $opt)
                                        <button type="button" class="duracion-btn"
                                                :class="{ 'active': duracion === {{ $opt['seconds'] }} }"
                                                @click="duracion = {{ $opt['seconds'] }}">{{ $opt['label'] }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-lg btn-full" @click="generarClave()" :disabled="generando">
                                <i class="fas fa-key"></i>
                                <span x-text="generando ? 'Generando...' : 'Generar clave de sesión'"></span>
                            </button>
                        </div>

                        <div x-show="claveActiva" style="display:none;">
                            <div class="clave-display">
                                <span class="clave-codigo" x-text="codigoClave"></span>
                                <button type="button" class="btn-copy" title="Copiar clave" @click="copiarClave()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="countdown-wrapper">
                                <div class="countdown-ring">
                                    <svg class="countdown-svg" viewBox="0 0 80 80">
                                        <circle class="ring-bg" cx="40" cy="40" r="34"/>
                                        <circle class="ring-progress" cx="40" cy="40" r="34"
                                                :style="{ strokeDasharray: circunferencia, strokeDashoffset: ringOffset }"/>
                                    </svg>
                                    <div class="countdown-time" x-text="countdownLabel">00:00</div>
                                </div>
                                <p class="countdown-label">tiempo restante</p>
                            </div>
                            <div class="sesion-stats">
                                <div class="sesion-stat">
                                    <span class="sesion-stat-value" x-text="contadorAsistencias">{{ $stats['present_today'] }}</span>
                                    <span class="sesion-stat-label">registrados</span>
                                </div>
                                <div class="sesion-stat-divider"></div>
                                <div class="sesion-stat">
                                    <span class="sesion-stat-value">{{ $stats['enrolled_count'] }}</span>
                                    <span class="sesion-stat-label">esperados</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline btn-md btn-full" style="margin-bottom:.5rem;" @click="generarClave()">
                                <i class="fas fa-redo"></i> Regenerar clave
                            </button>
                            <button type="button" class="btn btn-danger btn-md btn-full"
                                    @click="confirmarDetener = true"
                                    :disabled="deteniendo || cerrando">
                                <i class="fas fa-stop-circle"></i>
                                <span x-text="deteniendo ? 'Deteniendo...' : 'Detener clave de sesión'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> Configuración del Aula</h3>
                </div>
                <div class="card-body">
                    <div class="config-row">
                        <span class="config-label">Asistencia mínima requerida</span>
                        <span class="config-value">{{ $classroom->min_attendance_pct }}%</span>
                    </div>
                    <div class="config-row">
                        <span class="config-label">Capacidad del aula</span>
                        <span class="config-value">{{ $classroom->max_capacity }} alumnos</span>
                    </div>
                    <div class="config-row">
                        <span class="config-label">Periodo</span>
                        <span class="config-value">{{ $classroom->period }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-right">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users"></i> Alumnos — Sesión de hoy</h3>
                    <div class="card-actions">
                        <div class="search-bar">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" placeholder="Buscar alumno..." x-model="busqueda">
                        </div>
                        <select class="filter-select" x-model="filtroEstado">
                            <option value="">Todos</option>
                            <option value="present">Asistencia</option>
                            <option value="absent">Falta</option>
                            <option value="pending">Pendiente</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="dynamic-table">
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th>% Asistencia</th>
                                    <th>Estado hoy</th>
                                    <th>Hora registro</th>
                                    <th>Riesgo</th>
                                    @if($todaySession)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $row)
                                    @php
                                        $barClass = match($row['light']) {
                                            'green' => '',
                                            'amber' => 'progress-warning',
                                            'red'   => 'progress-risk',
                                            default => '',
                                        };
                                        $riesgoClass = match($row['light']) {
                                            'green' => 'riesgo-ok',
                                            'amber' => 'riesgo-medio',
                                            'red'   => 'riesgo-alto',
                                            default => 'riesgo-ok',
                                        };
                                        $riesgoLabel = match($row['light']) {
                                            'green' => 'Bajo',
                                            'amber' => 'Medio',
                                            'red'   => 'Alto',
                                            default => '—',
                                        };
                                        $todayStatus = $row['today_status'] ?? 'pending';
                                    @endphp
                                    <tr data-student-id="{{ $row['id'] }}"
                                        data-estado="{{ $todayStatus ?? 'pending' }}"
                                        x-show="filtrarFila(@js($row['name']), $el.dataset.estado)">
                                        <td>
                                            <div class="alumno-cell">
                                                <div class="avatar-sm">{{ $row['initials'] }}</div>
                                                <span>{{ $row['name'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="progress-mini">
                                                <div class="progress-bar-mini {{ $barClass }}" style="width:{{ min($row['pct'], 100) }}%"></div>
                                                <span class="pct-cell">{{ $row['pct'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="estado-cell">
                                            @if($todayStatus === 'present')
                                                <span class="status status-active">Asistencia</span>
                                            @elseif($todayStatus === 'absent')
                                                <span class="status status-absent">Falta</span>
                                            @else
                                                <span class="status status-pending">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="hora-cell">{{ $row['today_time'] ?? '—' }}</td>
                                        <td>
                                            <span class="riesgo {{ $riesgoClass }}">
                                                <i class="fas fa-circle"></i> {{ $riesgoLabel }}
                                            </span>
                                        </td>
                                        @if($todaySession)
                                            <td class="estatus-actions-cell">
                                                <div class="estatus-actions" title="Corrección manual del docente">
                                                    <button type="button"
                                                            class="estatus-btn estatus-btn--present"
                                                            title="Marcar asistencia"
                                                            :disabled="cambiandoEstatus === '{{ $row['id'] }}'"
                                                            @click="cambiarEstatus('{{ $row['id'] }}', 'present')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="estatus-btn estatus-btn--absent"
                                                            title="Marcar falta (habilita justificante 72 h)"
                                                            :disabled="cambiandoEstatus === '{{ $row['id'] }}'"
                                                            @click="cambiarEstatus('{{ $row['id'] }}', 'absent')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="estatus-btn estatus-btn--pending"
                                                            title="Dejar pendiente (sin registro)"
                                                            :disabled="cambiandoEstatus === '{{ $row['id'] }}' || {{ ($row['has_approved_justification'] ?? false) ? 'true' : 'false' }}"
                                                            @click="cambiarEstatus('{{ $row['id'] }}', 'pending')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Notificaciones en pantalla (solo front, sin BD) --}}
    <div class="toast-stack" aria-live="polite" aria-atomic="true">
        <template x-for="(toast, index) in toasts" :key="toast.id">
            <div class="toast-item" :class="'toast-item--' + toast.type" x-show="toast.visible"
                 x-transition.opacity.duration.200ms>
                <i class="fas" :class="toast.icon"></i>
                <span x-text="toast.message"></span>
                <button type="button" class="toast-close" @click="quitarToast(index)"><i class="fas fa-times"></i></button>
            </div>
        </template>
    </div>

    {{-- Modal detener clave --}}
    <div class="modal-overlay" :class="{ 'active': confirmarDetener }" @keydown.escape.window="confirmarDetener = false">
        <div class="modal modal-md" @click.outside="confirmarDetener = false">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Detener clave de sesión</h3>
                    <p class="modal-subtitle">Finalizar el registro de asistencia antes de tiempo</p>
                </div>
                <button type="button" class="modal-close" @click="confirmarDetener = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Desea detener la clave ahora? Los alumnos <strong>no podrán registrar</strong> asistencia con el código actual.</p>
                <p style="margin-top:.75rem;font-size:.9rem;color:#6b7280;">
                    Los alumnos sin registro quedarán marcados con <strong>falta</strong> y podrán enviar justificante (ventana de 72 h).
                    Puede generar una nueva clave después si lo necesita.
                </p>
            </div>
            <div class="modal-footer" style="flex-wrap:wrap;gap:.5rem;">
                <button type="button" class="btn btn-outline btn-md" @click="confirmarDetener = false">Cancelar</button>
                <button type="button" class="btn btn-danger btn-md" @click="detenerClave()" :disabled="deteniendo">
                    <i class="fas fa-stop-circle"></i>
                    <span x-text="deteniendo ? 'Deteniendo...' : 'Detener clave'"></span>
                </button>
                <button type="button" class="btn btn-outline btn-md" @click="confirmarDetener = false; confirmarCierre = true" :disabled="cerrando">
                    Cerrar sesión y marcar faltas…
                </button>
            </div>
        </div>
    </div>

    {{-- Modal cierre de sesión --}}
    <div class="modal-overlay" :class="{ 'active': confirmarCierre }" @keydown.escape.window="confirmarCierre = false">
        <div class="modal modal-md" @click.outside="confirmarCierre = false">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Cerrar sesión del aula</h3>
                    <p class="modal-subtitle">Esta acción no puede deshacerse</p>
                </div>
                <button type="button" class="modal-close" @click="confirmarCierre = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Se detendrá la clave y se cerrará la sesión de hoy.</p>
                <p>Los alumnos que no hayan registrado su asistencia quedarán marcados como <strong>Falta</strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" @click="confirmarCierre = false">Cancelar</button>
                <button type="button" class="btn btn-danger btn-md" @click="cerrarSesion()" :disabled="cerrando">
                    <i class="fas fa-lock"></i>
                    <span x-text="cerrando ? 'Cerrando...' : 'Cerrar sesión'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal expiración --}}
    <div class="modal-overlay" :class="{ 'active': modalExpirado }">
        <div class="modal modal-md">
            <div class="modal-header">
                <h3 class="modal-title">Clave expirada</h3>
                <button type="button" class="modal-close" @click="modalExpirado = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>El tiempo de registro terminó. Se marcaron faltas a quienes no registraron asistencia (pueden justificar en 72 h).</p>
                <p style="margin-top:.5rem;font-size:.9rem;color:#6b7280;">Puede generar una nueva clave o cerrar la sesión del día.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" @click="modalExpirado = false">Entendido</button>
                <button type="button" class="btn btn-primary btn-md" @click="modalExpirado = false; generarClave()">
                    <i class="fas fa-key"></i> Nueva clave
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
@if($classroom && $todaySession)
<script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
@endif
<script>
function docenteAsistencias() {
    return {
        duracion: 60,
        generando: false,
        claveActiva: @json((bool) ($activeKey && $activeKey->isValid())),
        codigoClave: @json($activeKey?->access_key ?? ''),
        expiresAt: @json($activeKey?->expires_at?->toIso8601String()),
        sesionActiva: @json((bool) $activeSession),
        sessionId: @json($todaySession?->id),
        classroomId: @json($classroom?->id),
        contadorAsistencias: {{ $stats['present_today'] ?? 0 }},
        segundosRestantes: 0,
        totalSegundos: 0,
        countdownLabel: '00:00',
        circunferencia: 2 * Math.PI * 34,
        ringOffset: 0,
        confirmarDetener: false,
        confirmarCierre: false,
        modalExpirado: false,
        deteniendo: false,
        cerrando: false,
        finalizandoExpiracion: false,
        busqueda: '',
        filtroEstado: '',
        timer: null,
        pollTimer: null,
        toasts: [],
        csrf: '{{ csrf_token() }}',
        urlEstatusBase: @json($todaySession ? url('/asistencias/docente/sesiones/'.$todaySession->id.'/alumnos') : null),
        cambiandoEstatus: null,

        urlDetenerClave() {
            return this.sessionId
                ? `/asistencias/docente/sesiones/${this.sessionId}/clave/detener`
                : null;
        },
        urlCerrarSesion() {
            return this.sessionId
                ? `/asistencias/docente/sesiones/${this.sessionId}/cerrar`
                : null;
        },
        urlRoster() {
            return this.sessionId
                ? `/asistencias/docente/sesiones/${this.sessionId}/roster`
                : null;
        },

        init() {
            if (this.claveActiva && this.expiresAt) {
                this.iniciarCountdown(new Date(this.expiresAt));
            }
            if (this.sessionId && this.sesionActiva) {
                this.iniciarPollingRoster();
            }
            @if($classroom && $todaySession && config('broadcasting.connections.reverb.key'))
            this.initEcho();
            @endif
        },

        initEcho() {
            const EchoCtor = (typeof window.Echo === 'function')
                ? window.Echo
                : (window.Echo && typeof window.Echo.default === 'function' ? window.Echo.default : null);
            if (!EchoCtor) return;
            const echoClient = new EchoCtor({
                broadcaster: 'reverb',
                key: '{{ config('broadcasting.connections.reverb.key') }}',
                wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
                wsPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
                wssPort: {{ config('broadcasting.connections.reverb.options.port', 443) }},
                forceTLS: {{ config('broadcasting.connections.reverb.options.useTLS') ? 'true' : 'false' }},
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: { headers: { 'X-CSRF-TOKEN': this.csrf } },
            });
            window.EchoClient = echoClient;
            echoClient.private('attendance.' + this.classroomId)
                .listen('.attendance.registered', (e) => this.onAttendance(e));
        },

        onAttendance(e) {
            const status = e.status || 'present';
            if (status === 'present') {
                this.contadorAsistencias++;
            }
            const hora = e.registered_at
                ? new Date(e.registered_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
                : null;
            this.actualizarFilaAlumno(e.student_id, status, hora);
            this.sincronizarRoster();
            if (status === 'present') {
                this.notify('success', `${e.student_name || 'Alumno'} registró asistencia.`, 'fa-user-check');
            } else if (status === 'absent') {
                this.notify('warning', `Falta registrada: ${e.student_name || 'Alumno'}.`, 'fa-user-times');
            }
        },

        notify(type, message, icon = 'fa-info-circle') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message, icon, visible: true });
            setTimeout(() => this.quitarToastPorId(id), 6000);
        },
        quitarToast(index) {
            if (this.toasts[index]) this.toasts[index].visible = false;
            setTimeout(() => this.toasts.splice(index, 1), 200);
        },
        quitarToastPorId(id) {
            const i = this.toasts.findIndex(t => t.id === id);
            if (i >= 0) this.quitarToast(i);
        },

        iniciarPollingRoster() {
            clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => this.sincronizarRoster(), 2500);
        },

        async sincronizarRoster() {
            if (!this.urlRoster()) return;
            try {
                const res = await fetch(this.urlRoster(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const json = await res.json();
                this.aplicarRosterPayload(json.data || {});
            } catch (e) { /* silencioso */ }
        },

        aplicarRosterPayload(payload) {
            if (typeof payload.present_count === 'number') {
                this.contadorAsistencias = payload.present_count;
            }
            (payload.students || []).forEach(s => {
                const status = s.today_status || 'pending';
                this.actualizarFilaAlumno(s.student_id, status, s.today_time);
                this.actualizarMetricasAlumno(s.student_id, s);
            });
            (payload.updates || []).forEach(u => {
                this.actualizarFilaAlumno(u.student_id, u.status, u.registered_at);
            });
        },

        actualizarFilaAlumno(studentId, status, registeredAt) {
            const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
            if (!row) return;
            const st = status || 'pending';
            row.dataset.estado = st;
            this.animarActualizacion(row);
            const estadoCell = row.querySelector('.estado-cell');
            if (!estadoCell) return;
            if (st === 'present') {
                estadoCell.innerHTML = '<span class="status status-active">Asistencia</span>';
            } else if (st === 'absent') {
                estadoCell.innerHTML = '<span class="status status-absent">Falta</span>';
            } else {
                estadoCell.innerHTML = '<span class="status status-pending">Pendiente</span>';
            }
            const horaCell = row.querySelector('.hora-cell');
            if (horaCell) {
                if (st === 'pending') {
                    horaCell.textContent = '—';
                } else if (registeredAt) {
                    const d = typeof registeredAt === 'string' && registeredAt.includes(':') && registeredAt.length <= 5
                        ? registeredAt
                        : new Date(registeredAt).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                    horaCell.textContent = d;
                }
            }
        },

        actualizarMetricasAlumno(studentId, data) {
            const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
            if (!row) return;

            const pct = Number(data.pct ?? 0);
            const light = (data.light || 'green').toLowerCase();

            const pctCell = row.querySelector('.pct-cell');
            const bar = row.querySelector('.progress-bar-mini');
            if (pctCell) pctCell.textContent = `${pct}%`;
            if (bar) {
                bar.style.width = `${Math.min(pct, 100)}%`;
                bar.classList.remove('progress-warning', 'progress-risk');
                if (light === 'amber') bar.classList.add('progress-warning');
                if (light === 'red') bar.classList.add('progress-risk');
            }

            const risk = row.querySelector('.riesgo');
            if (risk) {
                risk.classList.remove('riesgo-ok', 'riesgo-medio', 'riesgo-alto');
                if (light === 'green') {
                    risk.classList.add('riesgo-ok');
                    risk.innerHTML = '<i class="fas fa-circle"></i> Bajo';
                } else if (light === 'amber') {
                    risk.classList.add('riesgo-medio');
                    risk.innerHTML = '<i class="fas fa-circle"></i> Medio';
                } else {
                    risk.classList.add('riesgo-alto');
                    risk.innerHTML = '<i class="fas fa-circle"></i> Alto';
                }
            }
        },

        animarActualizacion(row) {
            row.classList.remove('row-updated');
            void row.offsetWidth;
            row.classList.add('row-updated');
            setTimeout(() => row.classList.remove('row-updated'), 850);
        },

        async cambiarEstatus(studentId, status) {
            if (!this.urlEstatusBase || this.cambiandoEstatus) return;
            this.cambiandoEstatus = studentId;
            try {
                const res = await fetch(`${this.urlEstatusBase}/${studentId}/estatus`, {
                    method: 'PATCH',
                    headers: this.headersJson(),
                    credentials: 'same-origin',
                    body: JSON.stringify({ status }),
                });
                let data = {};
                try {
                    data = await res.json();
                } catch (e) { /* noop */ }
                if (!res.ok) {
                    throw new Error(data.message || `Error ${res.status}`);
                }
                const payload = data.data || {};
                this.actualizarFilaAlumno(
                    studentId,
                    payload.status || status,
                    payload.registered_at
                );
                if (typeof payload.present_count === 'number') {
                    this.contadorAsistencias = payload.present_count;
                }
                this.notify('info', data.message || 'Estado actualizado.', 'fa-pen');
            } catch (err) {
                this.notify('error', err.message, 'fa-exclamation-circle');
            } finally {
                this.cambiandoEstatus = null;
            }
        },

        async generarClave() {
            if (!this.sessionId) return;
            this.generando = true;
            try {
                const res = await fetch(`{{ url('/asistencias/docente/sesiones') }}/${this.sessionId}/clave`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ duration_seconds: this.duracion }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Error al generar clave');
                this.codigoClave = data.data.access_key;
                this.claveActiva = true;
                this.sesionActiva = true;
                this.totalSegundos = this.duracion;
                this.iniciarCountdown(new Date(data.data.expires_at));
                this.iniciarPollingRoster();
                this.notify('success', 'Clave generada. Los alumnos pueden registrar asistencia.', 'fa-key');
            } catch (err) {
                this.notify('error', err.message, 'fa-exclamation-circle');
            } finally {
                this.generando = false;
            }
        },

        iniciarCountdown(expiresDate) {
            clearInterval(this.timer);
            const end = expiresDate.getTime();
            const tick = () => {
                const diff = Math.max(0, Math.floor((end - Date.now()) / 1000));
                this.segundosRestantes = diff;
                if (this.totalSegundos === 0) this.totalSegundos = diff || 1;
                const m = String(Math.floor(diff / 60)).padStart(2, '0');
                const s = String(diff % 60).padStart(2, '0');
                this.countdownLabel = `${m}:${s}`;
                const prog = diff / this.totalSegundos;
                this.ringOffset = this.circunferencia * (1 - prog);
                if (diff <= 0) {
                    clearInterval(this.timer);
                    this.claveActiva = false;
                    this.finalizarVentanaRegistro(true);
                }
            };
            tick();
            this.timer = setInterval(tick, 1000);
        },

        copiarClave() {
            navigator.clipboard.writeText(this.codigoClave);
        },

        headersJson() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            };
        },

        async postJson(url, body = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: this.headersJson(),
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            let data = {};
            try {
                data = await res.json();
            } catch (e) {
                /* respuesta no JSON */
            }
            if (!res.ok) {
                throw new Error(data.message || `Error ${res.status}`);
            }
            return data;
        },

        aplicarClaveDetenida() {
            clearInterval(this.timer);
            this.claveActiva = false;
            this.segundosRestantes = 0;
            this.countdownLabel = '00:00';
            this.ringOffset = this.circunferencia;
        },

        async finalizarVentanaRegistro(mostrarModalExpirado = false) {
            if (this.finalizandoExpiracion) return;
            const url = this.urlDetenerClave();
            if (!url) {
                if (mostrarModalExpirado) this.modalExpirado = true;
                return;
            }
            this.finalizandoExpiracion = true;
            try {
                const data = await this.postJson(url);
                this.aplicarClaveDetenida();
                if (data.data) this.aplicarRosterPayload(data.data);
                const n = (data.data?.updates || []).length;
                if (n > 0) {
                    this.notify('warning', data.message || `Se registraron ${n} falta(s).`, 'fa-user-times');
                } else if (!mostrarModalExpirado) {
                    this.notify('info', data.message || 'Clave detenida.', 'fa-stop-circle');
                }
                if (mostrarModalExpirado) this.modalExpirado = true;
            } catch (e) {
                this.aplicarClaveDetenida();
                if (mostrarModalExpirado) this.modalExpirado = true;
            } finally {
                this.finalizandoExpiracion = false;
            }
        },

        async detenerClave() {
            if (!this.urlDetenerClave() || this.deteniendo) return;
            this.deteniendo = true;
            try {
                const data = await this.postJson(this.urlDetenerClave());
                this.aplicarClaveDetenida();
                if (data.data) this.aplicarRosterPayload(data.data);
                this.confirmarDetener = false;
                const n = (data.data?.updates || []).filter(u => u.status === 'absent').length;
                this.notify(
                    n > 0 ? 'warning' : 'info',
                    data.message || (n > 0 ? `${n} falta(s) registrada(s).` : 'Clave detenida.'),
                    n > 0 ? 'fa-user-times' : 'fa-stop-circle'
                );
            } catch (err) {
                this.notify('error', err.message, 'fa-exclamation-circle');
            } finally {
                this.deteniendo = false;
            }
        },

        async cerrarSesion() {
            if (!this.urlCerrarSesion() || this.cerrando) return;
            this.cerrando = true;
            try {
                const data = await this.postJson(this.urlCerrarSesion());
                if (data.data) this.aplicarRosterPayload(data.data);
                this.sesionActiva = false;
                this.aplicarClaveDetenida();
                clearInterval(this.pollTimer);
                this.confirmarCierre = false;
                this.confirmarDetener = false;
                this.notify('success', data.message || 'Sesión cerrada.', 'fa-lock');
            } catch (err) {
                this.notify('error', err.message, 'fa-exclamation-circle');
            } finally {
                this.cerrando = false;
            }
        },

        filtrarFila(nombre, estado) {
            const matchNombre = !this.busqueda || nombre.toLowerCase().includes(this.busqueda.toLowerCase());
            const matchEstado = !this.filtroEstado || estado === this.filtroEstado;
            return matchNombre && matchEstado;
        },
    };
}
</script>
@endpush
@endsection
