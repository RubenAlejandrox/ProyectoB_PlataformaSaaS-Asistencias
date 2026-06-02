<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado recuperación - G.A.M.A Solutions</title>
    <link rel="stylesheet" href="{{ asset('css/gama-login.css') }}">
</head>
<body class="auth-page">
    <div class="auth-form-container" style="margin:auto;max-width:620px;">
        <div class="auth-form-wrapper">
            <div class="form-header">
                <h1>Recuperación de contraseña</h1>
                <p>{{ $message ?? 'Contacta a tu administrador para restablecer tu contraseña.' }}</p>
            </div>

            @if($found)
                <div style="background:#eff6ff;border-left:4px solid #2563eb;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                    <p style="margin:0 0 .6rem 0;"><strong>Usuario:</strong> {{ $userName ?? '—' }}</p>
                    <p style="margin:0 0 .6rem 0;"><strong>Institución:</strong> {{ $institutionName ?? '—' }}</p>
                    <p style="margin:0 0 .6rem 0;"><strong>Administrador:</strong> {{ $adminName ?: 'No disponible' }}</p>
                    <p style="margin:0 0 .6rem 0;"><strong>Correo administrador:</strong> {{ $adminEmail ?: 'No disponible' }}</p>
                    <p style="margin:0;"><strong>Teléfono:</strong> {{ $adminPhone ?: 'No disponible' }}</p>
                </div>
                @if(($adminSource ?? 'institution') === 'none')
                    <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        No hay administradores activos en el sistema. Contacta a soporte para reasignar un administrador institucional.
                    </div>
                @endif
                <div style="background:#fef3c7;border-left:4px solid #d97706;padding:1rem;border-radius:8px;">
                    Contacta a tu administrador para restablecer tu contraseña.
                </div>
            @else
                <div style="background:#f3f4f6;border-left:4px solid #6b7280;padding:1rem;border-radius:8px;">
                    Si tu cuenta existe y está activa, debes contactar a tu administrador institucional para el restablecimiento.
                </div>
            @endif

            <p class="form-footer" style="margin-top:1rem;">
                <a href="{{ route('password.request') }}">Hacer otra consulta</a> ·
                <a href="{{ route('login') }}">Volver a login</a>
            </p>
        </div>
    </div>
</body>
</html>
