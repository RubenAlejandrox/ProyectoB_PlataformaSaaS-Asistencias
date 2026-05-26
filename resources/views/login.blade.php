{{--
/**
 * G.A.M.A. SOLUTIONS S.A. de C.V.
 * "El factor de cambio en tu tecnología"
 *
 * @descripcion    Interfaz de autenticación dual (Login y Registro) con validación de fuerza de contraseña.
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 * @version        2.0.0
 * @creado         11/04/2026
 * @modificado     26/05/2026
 *
 * @cambios
 * Fecha       | Autor             | Descripción
 * ------------|-------------------|------------------------------------------
 * 11/04/2026  | Rubén Alejandro   | Implementación de vista de Login/Registro con validaciones JS.
 * 11/04/2026  | Rubén Alejandro   | Estandarización de prólogo según manual GAMA-MPL-03.
 * 26/05/2026  | Claude Web        | Conexión con AuthController: login + register funcionales.
 *             |                   | Campos: first_name, last_name, role, invitation_code.
 *             |                   | CSRF, mensajes de error y redirección por rol implementados.
 */
--}}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - G.A.M.A Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/gama-login.css') }}">
    <script src="{{ asset('js/auth-tabs.js') }}" defer></script>
</head>
<body class="auth-page">

    {{-- ===================== LEFT SIDE — BRANDING ===================== --}}
    <div class="auth-branding">
        <div class="branding-content">
            <div class="branding-logo">
                <img src="{{ asset('img/gama-logo.png') }}" alt="G.A.M.A Solutions" class="logo-image">
            </div>
            <h1 class="branding-title">"El factor de cambio en tu tecnología"</h1>
            <p class="branding-subtitle">
                Sistemas modulares diseñados para evolucionar al ritmo de su demanda
            </p>
            <div class="branding-features">
                <div class="branding-feature">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <span>Ética y Resguardo de Activos</span>
                </div>
                <div class="branding-feature">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <span>Manejo de datos en tiempo real y análisis</span>
                </div>
                <div class="branding-feature">
                    <div class="feature-icon"><i class="fas fa-desktop"></i></div>
                    <span>Fácil de utilizar</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== RIGHT SIDE — FORMS ===================== --}}
    <div class="auth-form-container">
        <div class="auth-form-wrapper">

            <div class="form-header">
                <h1>Bienvenido de nuevo</h1>
                <p>Ingresa tus credenciales para acceder a tu cuenta</p>
            </div>

            {{-- Tab Switcher --}}
            <div class="auth-tabs">
                <button class="auth-tab active" data-form="login">Iniciar sesión</button>
                <button class="auth-tab" data-form="register">Registrarse</button>
            </div>

            {{-- ── LOGIN FORM ──────────────────────────────────────── --}}
            <form class="auth-form active"
      id="loginForm"
      method="POST"
      action="{{ route('auth.login') }}">
    @csrf

                {{-- Error global --}}
                @if($errors->has('email') || $errors->has('password') || $errors->has('general'))
                    <div class="alert-error" id="loginError">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>
                            {{ $errors->first('general') ?: $errors->first('email') ?: $errors->first('password') }}
                        </span>
                    </div>
                @endif

                {{-- Cuenta bloqueada --}}
                @if(session('locked_until'))
                    <div class="alert-error">
                        <i class="fas fa-lock"></i>
                        <span>Cuenta bloqueada hasta las {{ session('locked_until') }}. Intenta más tarde.</span>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Dirección de correo electrónico</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email"
                               name="email"
                               class="form-input"
                               placeholder="Ingresa tu correo electrónico"
                               value="{{ old('email') }}"
                               required
                               autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password"
                               name="password"
                               class="form-input"
                               id="loginPassword"
                               placeholder="Ingresa tu contraseña"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="checkbox-wrapper" onclick="toggleCheckbox(this)">
                        <div class="checkbox"><i class="fas fa-check"></i></div>
                        <span class="checkbox-label">Recordarme</span>
                    </div>
                    <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar sesión
                </button>

                <p class="form-footer" style="margin-top:1rem;">
                    ¿No tienes una cuenta? <a href="#" id="switchToRegister">Regístrate gratis</a>
                </p>
            </form>

            {{-- ── REGISTER FORM ───────────────────────────────────── --}}
            <form class="auth-form"
                  id="registerForm"
                  method="POST"
                  action="{{ route('auth.register') }}">
                @csrf

                {{-- Error global --}}
                @if($errors->any() && old('_form') === 'register')
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Campo oculto para saber qué form envió los errores --}}
                <input type="hidden" name="_form" value="register">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Nombre(s)</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text"
                                   name="first_name"
                                   class="form-input"
                                   placeholder="Nombre(s)"
                                   value="{{ old('first_name') }}"
                                   required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido(s)</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text"
                                   name="last_name"
                                   class="form-input"
                                   placeholder="Apellido(s)"
                                   value="{{ old('last_name') }}"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección de correo electrónico</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email"
                               name="email"
                               class="form-input"
                               placeholder="Ingresa tu correo electrónico"
                               value="{{ old('email') }}"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <select name="role"
                                class="form-input"
                                id="roleSelect"
                                required
                                style="padding-left:50px; appearance:none; cursor:pointer;">
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Selecciona tu rol</option>
                            <option value="Teacher" {{ old('role') === 'Teacher' ? 'selected' : '' }}>Docente</option>
                            <option value="Student" {{ old('role') === 'Student' ? 'selected' : '' }}>Alumno</option>
                        </select>
                    </div>
                </div>

                {{-- Código de invitación — solo visible para alumnos --}}
                <div class="form-group" id="invitationGroup" style="display:none;">
                    <label class="form-label">
                        Código de invitación
                        <span style="font-size:.75rem; color:var(--soft-steel); font-weight:400;">(opcional — para unirte a un aula)</span>
                    </label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-ticket-alt input-icon"></i>
                        <input type="text"
                               name="invitation_code"
                               class="form-input"
                               placeholder="Ej: ABC12345"
                               value="{{ old('invitation_code') }}"
                               maxlength="8"
                               style="text-transform:uppercase; letter-spacing:.1rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password"
                               name="password"
                               class="form-input"
                               id="registerPassword"
                               placeholder="Crea una contraseña"
                               required
                               autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar"></div>
                        <div class="strength-bar"></div>
                        <div class="strength-bar"></div>
                        <div class="strength-bar"></div>
                    </div>
                    <p class="strength-text">Usa 8+ caracteres con una mezcla de letras, números y símbolos</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar contraseña</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password"
                               name="password_confirmation"
                               class="form-input"
                               id="confirmPassword"
                               placeholder="Repite tu contraseña"
                               required
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-wrapper" onclick="toggleCheckbox(this)">
                        <div class="checkbox"><i class="fas fa-check"></i></div>
                        <span class="checkbox-label">
                            Acepto los <a href="{{ route('terms') }}">Términos de Servicio</a>
                            y la <a href="{{ route('privacy') }}">Política de Privacidad</a>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Crear cuenta
                </button>

                <p class="form-footer" style="margin-top:1rem;">
                    ¿Ya tienes una cuenta? <a href="#" id="switchToLogin">Inicia sesión</a>
                </p>
            </form>

        </div>

        <p class="copyright">
            Copyright &copy; 2026 G.A.M.A Solutions. Todos los derechos reservados.
        </p>
    </div>

    <script>
        // ── Toggle password ──────────────────────────────────────────────────
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon  = input.parentElement.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // ── Password strength ────────────────────────────────────────────────
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8)  strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/\d/.test(password))    strength++;
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrength(inputId) {
            const input    = document.getElementById(inputId);
            const bars     = input.parentElement.parentElement.querySelectorAll('.strength-bar');
            if (!bars.length) return;
            const strength = calculatePasswordStrength(input.value);
            bars.forEach(b => b.classList.remove('weak','medium','strong'));
            let cls = '', filled = 0;
            if      (strength <= 2) { cls = 'weak';   filled = Math.min(strength, 2); }
            else if (strength <= 4) { cls = 'medium'; filled = strength - 2; }
            else                    { cls = 'strong'; filled = 4; }
            for (let i = 0; i < filled; i++) bars[i].classList.add(cls);
        }

        document.getElementById('registerPassword')?.addEventListener('input', function () {
            updatePasswordStrength('registerPassword');
        });

        // ── Checkbox toggle ──────────────────────────────────────────────────
        function toggleCheckbox(wrapper) {
            wrapper.querySelector('.checkbox').classList.toggle('checked');
        }

        // ── Mostrar/ocultar código de invitación según rol ───────────────────
        document.getElementById('roleSelect')?.addEventListener('change', function () {
            const group = document.getElementById('invitationGroup');
            group.style.display = this.value === 'Student' ? 'block' : 'none';
        });

        // ── Si hay errores del register, abrir ese tab automáticamente ───────
        @if(old('_form') === 'register' && $errors->any())
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelector('[data-form="register"]')?.click();
            });
        @endif
    </script>

</body>
</html>