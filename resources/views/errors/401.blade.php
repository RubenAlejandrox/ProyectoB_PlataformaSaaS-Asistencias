<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No autenticado</title>
    <link rel="stylesheet" href="{{ asset('css/gama-dashboard.css') }}">
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;">
        <div style="max-width:560px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:2rem;box-shadow:0 6px 24px rgba(15,23,42,.08);text-align:center;">
            <div style="font-size:2.6rem;color:#f59e0b;margin-bottom:.5rem;">
                <i class="fas fa-user-lock"></i>
            </div>
            <h1 style="margin:0 0 .4rem;font-size:1.5rem;color:#111827;">Sesión requerida</h1>
            <p style="margin:0 0 1.2rem;color:#4b5563;">
                Necesitas iniciar sesión para continuar.
            </p>
            <a href="{{ route('login') }}" style="display:inline-block;background:#134474;color:#fff;text-decoration:none;padding:.65rem 1rem;border-radius:8px;font-weight:600;">
                Ir al login
            </a>
        </div>
    </div>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
