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
                    <p>{{ $classroom->subject_name }} — {{ $classroom->period }}</p>
                @else
                    <p>Selecciona un aula para comenzar</p>
                @endif
            </div>
            <div class="header-actions">
                @if($classrooms->count() > 1)
                    <form method="GET" action="{{ route('asistencias.docente') }}" style="display:inline;">
                        <select name="classroom" class="filter-select" onchange="this.form.submit()">
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" @selected($classroom && $classroom->id === $c->id)>
                                    {{ $c->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
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

                    @if(!$activeSession)
                        <p class="clave-desc">Abre una sesión de hoy para generar la clave de asistencia.</p>
                        <form method="POST" action="{{ route('asistencias.docente.sesion') }}">
                            @csrf
                            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                            <button type="submit" class="btn btn-primary btn-lg btn-full">
                                <i class="fas fa-play-circle"></i> Abrir sesión de hoy
                            </button>
                        </form>
                    @else
                        <div x-show="!claveActiva">
                            <p class="clave-desc">Genera una clave alfanumérica de 8 caracteres para que tus alumnos registren su asistencia.</p>
                            <div class="clave-config">
                                <label class="form-label">Duración de la clave</label>
                                <div class="duracion-options">
                                    @foreach($keyDurations as $min)
                                        <button type="button" class="duracion-btn" :class="{ 'active': duracion === {{ $min }} }"
                                                @click="duracion = {{ $min }}">{{ $min }} min</button>
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
                            <button type="button" class="btn btn-danger btn-md btn-full" @click="confirmarCierre = true">
                                <i class="fas fa-stop-circle"></i> Cerrar registro manualmente
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
                                        data-estado="{{ $todayStatus }}"
                                        x-show="filtrarFila('{{ $row['name'] }}', '{{ $todayStatus }}')">
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

    {{-- Modal cierre --}}
    <div class="modal-overlay" :class="{ 'active': confirmarCierre }" @keydown.escape.window="confirmarCierre = false">
        <div class="modal modal-md" @click.outside="confirmarCierre = false">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Cerrar registro de asistencia</h3>
                    <p class="modal-subtitle">Esta acción no puede deshacerse</p>
                </div>
                <button type="button" class="modal-close" @click="confirmarCierre = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea cerrar el registro antes de que expire la clave?</p>
                <p>Los alumnos que no hayan registrado su asistencia quedarán marcados como <strong>Falta</strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-md" @click="confirmarCierre = false">Cancelar</button>
                <button type="button" class="btn btn-danger btn-md" @click="cerrarSesion()">
                    <i class="fas fa-stop-circle"></i> Cerrar registro
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
                <p>El tiempo de la clave ha terminado. Puedes generar una nueva clave sin recargar la página.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-md" @click="modalExpirado = false; generarClave()">
                    <i class="fas fa-key"></i> Regenerar clave
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
@if($classroom && $activeSession)
<script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
@endif
<script>
function docenteAsistencias() {
    return {
        duracion: 15,
        generando: false,
        claveActiva: @json((bool) ($activeKey && $activeKey->isValid())),
        codigoClave: @json($activeKey?->access_key ?? ''),
        expiresAt: @json($activeKey?->expires_at?->toIso8601String()),
        sesionActiva: @json((bool) $activeSession),
        sessionId: @json($activeSession?->id),
        classroomId: @json($classroom?->id),
        contadorAsistencias: {{ $stats['present_today'] ?? 0 }},
        segundosRestantes: 0,
        totalSegundos: 0,
        countdownLabel: '00:00',
        circunferencia: 2 * Math.PI * 34,
        ringOffset: 0,
        confirmarCierre: false,
        modalExpirado: false,
        busqueda: '',
        filtroEstado: '',
        timer: null,
        csrf: '{{ csrf_token() }}',

        init() {
            if (this.claveActiva && this.expiresAt) {
                this.iniciarCountdown(new Date(this.expiresAt));
            }
            @if($classroom && $activeSession && config('broadcasting.connections.reverb.key'))
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
            this.contadorAsistencias++;
            const row = document.querySelector(`tr[data-student-id="${e.student_id}"]`);
            if (!row) return;
            row.dataset.estado = 'present';
            row.querySelector('.estado-cell').innerHTML = '<span class="status status-active">Asistencia</span>';
            const hora = e.registered_at ? new Date(e.registered_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '—';
            row.querySelector('.hora-cell').textContent = hora;
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
                    body: JSON.stringify({ duration_minutes: this.duracion }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Error al generar clave');
                this.codigoClave = data.data.access_key;
                this.claveActiva = true;
                this.sesionActiva = true;
                this.iniciarCountdown(new Date(data.data.expires_at));
            } catch (err) {
                alert(err.message);
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
                    this.modalExpirado = true;
                }
            };
            tick();
            this.timer = setInterval(tick, 1000);
        },

        copiarClave() {
            navigator.clipboard.writeText(this.codigoClave);
        },

        async cerrarSesion() {
            const res = await fetch(`{{ url('/asistencias/docente/sesiones') }}/${this.sessionId}/cerrar`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
            });
            if (res.ok) window.location.reload();
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
