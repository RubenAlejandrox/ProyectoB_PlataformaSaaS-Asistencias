<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - G.A.M.A Solutions</title>
    <link rel="stylesheet" href="{{ asset('css/gama-login.css') }}">
</head>
<body class="auth-page">
    <div class="auth-form-container" style="margin:auto;max-width:520px;">
        <div class="auth-form-wrapper">
            <div class="form-header">
                <h1>Recuperar contraseña</h1>
                <p>Ingresa tu correo para mostrar los datos de tu administrador institucional.</p>
            </div>

            <form class="auth-form active" method="POST" action="{{ route('password.forgot') }}">
                @csrf
                @if($errors->has('email'))
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first('email') }}</span>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email"
                               name="email"
                               class="form-input"
                               placeholder="Ingresa tu correo"
                               value="{{ old('email') }}"
                               required
                               autocomplete="email">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i>
                    Consultar administrador
                </button>

                <p class="form-footer" style="margin-top:1rem;">
                    <a href="{{ route('login') }}">Volver a iniciar sesión</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
