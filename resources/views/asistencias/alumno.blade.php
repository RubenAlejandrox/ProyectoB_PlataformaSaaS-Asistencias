@extends('layouts.app')

@section('title', 'Mi Asistencia - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencias-alumno.css') }}">
@endpush

@section('content')
<div class="main-content" x-data="alumnoAsistencias()" x-init="init()">

    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Mi Asistencia</h1>
                @if($selected)
                    <p>{{ $selected['classroom']->subject_name ?? '' }} — {{ $selected['classroom']->period ?? '' }}</p>
                @else
                    <p>Inscríbete a un aula para ver tu progreso</p>
                @endif
            </div>
            <div class="header-actions">
                <span class="badge badge-info">
                    <i class="fas fa-graduation-cap"></i>
                    {{ $user->first_name }} {{ $user->last_name }}
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->has('access_key'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#fee2e2;border-left:4px solid #DC3545;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#991b1b;">
            <i class="fas fa-exclamation-circle"></i><span>{{ $errors->first('access_key') }}</span>
        </div>
    @endif

    <div class="kpi-grid">
        <div class="kpi-card kpi-card--highlight">
            <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" x-text="globalPct + '%'">{{ $globalPct }}%</span>
                <span class="kpi-label">Mi asistencia global</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $totalPresent }}</span>
                <span class="kpi-label">Asistencias</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $totalAbsent }}</span>
                <span class="kpi-label">Faltas</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $totalJustif }}</span>
                <span class="kpi-label">Justificantes aprobados</span>
            </div>
        </div>
    </div>

    @if(count($subjects) === 0)
        <div class="card">
            <div class="card-body">
                <p>No estás inscrito en ningún aula. Usa un código de invitación en <a href="{{ route('aulas.index') }}">Aulas</a>.</p>
            </div>
        </div>
    @else

    <div class="panel-grid">
        <div class="panel-left">

            <div class="card card--registro">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key"></i> Registrar Asistencia</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('asistencias.alumno.registrar') }}" id="formClave">
                        @csrf
                        <p class="registro-desc">Ingresa la clave de 8 caracteres que tu docente proyectó en clase.</p>
                        <div class="clave-inputs">
                            @for($i = 1; $i <= 8; $i++)
                                <input class="clave-char" type="text" maxlength="1" name="c{{ $i }}"
                                       id="c{{ $i }}" autocomplete="off"
                                       x-ref="c{{ $i }}" @input="onInput($event, {{ $i }})" @keydown="onKeydown($event, {{ $i }})">
                            @endfor
                        </div>
                        <input type="hidden" name="access_key" x-model="claveCompleta">
                        <p class="clave-hint" x-text="hint"></p>
                        <button type="submit" class="btn btn-primary btn-lg btn-full" :disabled="claveCompleta.length !== 8">
                            <i class="fas fa-paper-plane"></i> Registrar asistencia
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Progreso por materia</h3>
                </div>
                <div class="card-body">
                    @foreach($subjects as $subject)
                        @php
                            $barClass = match($subject['light']) {
                                'green' => 'progreso-ok',
                                'amber' => 'progreso-warning',
                                'red'   => 'progreso-risk',
                                default => '',
                            };
                            $semClass = match($subject['light']) {
                                'green' => 'semaforo-verde',
                                'amber' => 'semaforo-ambar',
                                'red'   => 'semaforo-rojo',
                                default => '',
                            };
                        @endphp
                        <div class="materia-progreso" data-classroom-id="{{ $subject['classroom_id'] }}">
                            <div class="materia-header">
                                <span class="materia-nombre">{{ $subject['classroom']->subject_name }}</span>
                                <span class="semaforo {{ $semClass }}" title="Semáforo {{ $subject['light'] }}">
                                    <i class="fas fa-circle"></i>
                                </span>
                            </div>
                            <div class="progreso-barra-wrapper">
                                <div class="progreso-barra {{ $barClass }} pct-bar" style="width: {{ min($subject['attendance_pct'], 100) }}%"></div>
                            </div>
                            <div class="progreso-meta">
                                <span class="pct-label">{{ $subject['attendance_pct'] }}%</span>
                                <span>Meta: {{ $subject['threshold'] }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel-right">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Historial de Asistencias</h3>
                    @if(count($subjects) > 1)
                        <form method="GET" action="{{ route('asistencias.alumno') }}">
                            <select name="classroom" class="filter-select" onchange="this.form.submit()">
                                @foreach($subjects as $s)
                                    <option value="{{ $s['classroom_id'] }}"
                                        @selected($selected && $selected['classroom_id'] === $s['classroom_id'])>
                                        {{ $s['classroom']->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="dynamic-table" id="tablaHistorial">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $att)
                                    @php
                                        $estado = $att->status;
                                        if ($estado === 'absent' && $att->justification?->status === 'approved') {
                                            $estado = 'justified';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $att->session->session_date?->format('d/m/Y') }}</td>
                                        <td>{{ $att->status === 'present' ? $att->created_at->format('H:i') : '—' }}</td>
                                        <td>
                                            @if($estado === 'present')
                                                <span class="status status-active">Asistencia</span>
                                            @elseif($estado === 'justified')
                                                <span class="status status-justified">Justificante</span>
                                            @else
                                                <span class="status status-absent">Falta</span>
                                            @endif
                                        </td>
                                        <td>{{ $att->justification?->reason ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" style="text-align:center;padding:1.5rem;color:#888;">Sin registros aún</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
@if(count($subjects) > 0 && config('broadcasting.connections.reverb.key'))
<script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
@endif
<script>
function alumnoAsistencias() {
    return {
        claveCompleta: '',
        hint: '0 / 8 caracteres',
        globalPct: {{ $globalPct }},
        csrf: '{{ csrf_token() }}',
        studentId: '{{ auth()->user()->id }}',

        init() {
            @if(count($subjects) > 0 && config('broadcasting.connections.reverb.key'))
            const EchoCtor = (typeof window.Echo === 'function')
                ? window.Echo
                : (window.Echo && typeof window.Echo.default === 'function' ? window.Echo.default : null);
            if (EchoCtor) {
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
                echoClient.private('progress.' + this.studentId)
                    .listen('.traffic.light', (e) => this.onTrafficLight(e));
            }
            @endif
        },

        onTrafficLight(e) {
            const block = document.querySelector(`[data-classroom-id="${e.classroom_id}"]`);
            if (!block) return;
            const pct = e.percentage;
            block.querySelector('.pct-label').textContent = pct + '%';
            block.querySelector('.pct-bar').style.width = Math.min(pct, 100) + '%';
            const sem = block.querySelector('.semaforo');
            sem.className = 'semaforo semaforo-' + (e.light === 'green' ? 'verde' : e.light === 'amber' ? 'ambar' : 'rojo');
            const bar = block.querySelector('.progreso-barra');
            bar.className = 'progreso-barra pct-bar ' + (e.light === 'green' ? 'progreso-ok' : e.light === 'amber' ? 'progreso-warning' : 'progreso-risk');
            this.recalcGlobal();
        },

        recalcGlobal() {
            const pcts = [...document.querySelectorAll('.pct-label')].map(el => parseFloat(el.textContent));
            if (pcts.length) this.globalPct = Math.round(pcts.reduce((a,b)=>a+b,0) / pcts.length * 10) / 10;
        },

        onInput(e, idx) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (e.target.value && idx < 8) this.$refs['c' + (idx + 1)]?.focus();
            this.syncClave();
        },

        onKeydown(e, idx) {
            if (e.key === 'Backspace' && !e.target.value && idx > 1) {
                this.$refs['c' + (idx - 1)]?.focus();
            }
        },

        syncClave() {
            let clave = '';
            for (let i = 1; i <= 8; i++) clave += (this.$refs['c' + i]?.value || '');
            this.claveCompleta = clave;
            this.hint = clave.length === 8 ? '' : `${clave.length} / 8 caracteres`;
        },
    };
}
</script>
@endpush
@endsection
