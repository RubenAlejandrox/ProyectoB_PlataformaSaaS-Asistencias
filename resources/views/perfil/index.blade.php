@extends('layouts.app')

@section('title', 'Mi Perfil - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/instituciones.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Mi Perfil</h1>
                <p>Actualiza tus datos personales y contraseña</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="panel-grid" style="grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user"></i> Datos personales</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('perfil.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="first_name">Nombre(s)</label>
                        <input type="text"
                               id="first_name"
                               name="first_name"
                               class="form-input @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name', $user->first_name) }}"
                               required>
                        @error('first_name')
                            <span style="color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="last_name">Apellidos</label>
                        <input type="text"
                               id="last_name"
                               name="last_name"
                               class="form-input @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $user->last_name) }}"
                               required>
                        @error('last_name')
                            <span style="color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-input @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               required>
                        @error('email')
                            <span style="color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Rol</label>
                        <input type="text"
                               class="form-input"
                               value="{{ $user->getRoleNames()->first() ?? '—' }}"
                               disabled>
                    </div>

                    <div style="margin-top:1.25rem;">
                        <button type="submit" class="btn btn-primary btn-md">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lock"></i> Cambiar contraseña</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('perfil.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="current_password">Contraseña actual</label>
                        <input type="password"
                               id="current_password"
                               name="current_password"
                               class="form-input @error('current_password') is-invalid @enderror"
                               autocomplete="current-password"
                               required>
                        @error('current_password')
                            <span style="color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Nueva contraseña</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input @error('password') is-invalid @enderror"
                               autocomplete="new-password"
                               minlength="8"
                               required>
                        @error('password')
                            <span style="color:#DC3545;font-size:.85rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="form-input"
                               autocomplete="new-password"
                               minlength="8"
                               required>
                    </div>

                    <p style="font-size:.85rem;color:var(--soft-steel);margin-bottom:1rem;">
                        Mínimo 8 caracteres.
                    </p>

                    <button type="submit" class="btn btn-outline btn-md">
                        <i class="fas fa-key"></i> Actualizar contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
