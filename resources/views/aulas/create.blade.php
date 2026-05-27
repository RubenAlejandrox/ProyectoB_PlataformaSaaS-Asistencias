{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Módulo de Aulas — Formulario de creación de aula y
 *                 generación automática del código de invitación al guardar
 *                 (RF-04 / RF-05).
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
 * 07/05/2026  | Rubén Alejandro   | Implementación inicial Crear Aula (RF-04/RF-05).
 * 26/05/2026  | Claude Web        | Conexión con ClassroomController@store + límite plan.
 */
--}}

@extends('layouts.app')

@section('title', 'Nueva Aula - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/aulas.css') }}">
@endpush

@section('content')
<div class="main-content">

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Nueva Aula</h1>
                <p>Configura los datos del aula y genera el código de invitación</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('aulas.index') }}" class="btn btn-outline btn-md">
                    <i class="fas fa-arrow-left"></i>
                    Volver a aulas
                </a>
            </div>
        </div>
    </div>

    {{-- LÍMITE DEL PLAN --}}
    <div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem 1rem;margin-bottom:1.2rem;font-size:.85rem;color:#555;">
        <i class="fas fa-info-circle" style="color:#134474;"></i>
        Plan <strong>{{ $activePlan->name }}</strong>:
        <strong>{{ $totalClassrooms }} / {{ $activePlan->max_classrooms }}</strong> aulas utilizadas.
    </div>

    <form method="POST" action="{{ route('aulas.store') }}" id="formCrearAula">
        @csrf

        <div class="create-grid">

            {{-- FORMULARIO PRINCIPAL --}}
            <div class="create-main">

                {{-- Datos generales --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Datos generales
                        </h3>
                    </div>
                    <div class="card-body">

                        @if($errors->any())
                            <div style="display:flex;align-items:center;gap:.5rem;background:#fee2e2;border-left:4px solid #DC3545;padding:.6rem 1rem;border-radius:6px;color:#991b1b;font-size:.9rem;margin-bottom:1rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="form-grid">
                            <div class="form-group form-group--full">
                                <label class="form-label">Nombre del aula / materia <span class="required">*</span></label>
                                <input type="text"
                                       name="subject_name"
                                       class="form-input"
                                       id="nombreAula"
                                       placeholder="Ej. Matemáticas"
                                       value="{{ old('subject_name') }}"
                                       oninput="actualizarPreview()"
                                       required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Período <span class="required">*</span></label>
                                <select name="period" class="form-input" id="ciclo"
                                        onchange="actualizarPreview()" required>
                                    <option value="">Selecciona...</option>
                                    <option value="2026-A" {{ old('period') === '2026-A' ? 'selected' : '' }}>Enero – Junio 2026</option>
                                    <option value="2026-B" {{ old('period') === '2026-B' ? 'selected' : '' }}>Agosto – Diciembre 2026</option>
                                    <option value="2027-A" {{ old('period') === '2027-A' ? 'selected' : '' }}>Enero – Junio 2027</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Capacidad máxima <span class="required">*</span></label>
                                <input type="number"
                                       name="max_capacity"
                                       class="form-input"
                                       placeholder="30"
                                       value="{{ old('max_capacity', 30) }}"
                                       min="1"
                                       max="{{ $activePlan->max_students }}"
                                       required>
                                <span style="font-size:.75rem;color:var(--text-secondary);">
                                    Máx. {{ $activePlan->max_students }} alumnos según tu plan
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Asistencia mínima requerida <span class="required">*</span></label>
                                <div class="input-suffix-wrapper">
                                    <input type="number"
                                           name="min_attendance_pct"
                                           class="form-input"
                                           value="{{ old('min_attendance_pct', 80) }}"
                                           min="1"
                                           max="100"
                                           required>
                                    <span class="input-suffix">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="create-actions">
                    <a href="{{ route('aulas.index') }}" class="btn btn-outline btn-md">
                        Cancelar
                    </a>
                    <button type="button" class="btn btn-primary btn-md"
                            onclick="abrirModal('modalConfirmar')">
                        <i class="fas fa-save"></i>
                        Crear aula y generar código
                    </button>
                </div>

            </div>

            {{-- PANEL LATERAL --}}
            <div class="create-sidebar">

                <div class="card card--codigo">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-key"></i>
                            Código de invitación
                        </h3>
                    </div>
                    <div class="card-body codigo-body">
                        <p class="codigo-desc">
                            Se generará automáticamente al crear el aula.
                            Los alumnos lo usarán para inscribirse.
                        </p>
                        <div class="codigo-preview">
                            <span class="codigo-texto" id="codigoTexto">????????</span>
                        </div>
                        <p class="codigo-nota">
                            <i class="fas fa-info-circle"></i>
                            El código aleatorio de 8 caracteres se genera al guardar (vence en 48h).
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Antes de crear
                        </h3>
                    </div>
                    <div class="card-body">
                        <ul class="pre-checklist">
                            <li class="pre-check" id="checkNombre">
                                <i class="fas fa-circle pre-dot"></i>
                                Nombre del aula
                            </li>
                            <li class="pre-check" id="checkCiclo">
                                <i class="fas fa-circle pre-dot"></i>
                                Período seleccionado
                            </li>
                            <li class="pre-check">
                                <i class="fas fa-circle pre-dot pre-dot--ok"></i>
                                Capacidad y asistencia mínima
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

    </form>

</div>

{{-- MODAL: CONFIRMAR --}}
<div class="modal-overlay" id="modalConfirmar">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Confirmar creación</h3>
                <p class="modal-subtitle">Se generará el código de invitación automáticamente</p>
            </div>
            <button class="modal-close" onclick="cerrarModal('modalConfirmar')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-resumen">
                <div class="conf-row">
                    <span class="conf-label">Aula</span>
                    <span class="conf-value" id="confNombre">—</span>
                </div>
                <div class="conf-row">
                    <span class="conf-label">Período</span>
                    <span class="conf-value" id="confCiclo">—</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-md" onclick="cerrarModal('modalConfirmar')">Cancelar</button>
            <button class="btn btn-primary btn-md" onclick="document.getElementById('formCrearAula').submit()">
                <i class="fas fa-save"></i>
                Crear aula
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function actualizarPreview() {
        const nombre = document.getElementById('nombreAula').value.trim();
        const ciclo  = document.getElementById('ciclo').value;

        document.getElementById('confNombre').textContent = nombre || '—';
        document.getElementById('confCiclo').textContent  = ciclo  || '—';

        toggleCheck('checkNombre', nombre.length > 0);
        toggleCheck('checkCiclo',  ciclo.length > 0);
    }

    function toggleCheck(id, ok) {
        const dot = document.getElementById(id).querySelector('.pre-dot');
        ok ? dot.classList.add('pre-dot--ok') : dot.classList.remove('pre-dot--ok');
    }

    function abrirModal(id) { document.getElementById(id).classList.add('active'); }
    function cerrarModal(id){ document.getElementById(id).classList.remove('active'); }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal('modalConfirmar'); });

    // Estado inicial (si hay old() valores tras error de validación)
    document.addEventListener('DOMContentLoaded', actualizarPreview);
</script>
@endpush

@endsection
