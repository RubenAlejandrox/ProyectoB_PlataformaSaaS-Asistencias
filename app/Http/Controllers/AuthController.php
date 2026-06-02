<?php

/**
 * @descripcion  Autenticación, bloqueo por intentos y recuperación de contraseña sin correo (contacto admin).
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.1.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios      2026-06-02 - Flujo forgot-password y búsqueda de administrador
 *               2026-06-02 - Incorporación de cabecera de prólogo
 */


declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Institution;
use App\Models\InstitutionCode;
use App\Models\InvitationCode;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const RESET_PASSWORD_CONTACT_MESSAGE = 'Contacta a tu administrador para restablecer tu contraseña.';

    /**
     * Autentica un usuario vía API (Sanctum) con bloqueo por intentos fallidos.
     *
     * @param LoginRequest $request Credenciales validadas (email y contraseña)
     * @return JsonResponse Token de acceso y datos del usuario, o error 401/423
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        if (!$user || !$user->is_active)
            return response()->json(['message' => 'Invalid credentials.'], 401);

        if ($user->isLocked())
            return response()->json([
                'message'      => 'Account locked. Try again after ' . $user->locked_until->format('H:i'),
                'locked_until' => $user->locked_until,
            ], 423);

        if (!Hash::check($request->password, $user->password_hash)) {
            $attempts = $user->failed_login_attempts + 1;
            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until'          => $attempts >= 5 ? now()->addMinutes(15) : null,
            ]);
            $remaining = 5 - $attempts;
            return response()->json([
                'message'            => $attempts >= 5
                    ? 'Account locked for 15 minutes.'
                    : "Invalid credentials. {$remaining} attempts remaining.",
                'attempts_remaining' => max(0, $remaining),
            ], 401);
        }

        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);
        $user->tokens()->delete();
        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'role'       => $user->getRoleNames()->first(),
            ],
        ]);
    }

    /**
     * Revoca el token Sanctum actual del usuario autenticado (API).
     *
     * @return JsonResponse Mensaje de cierre de sesión exitoso
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Muestra el formulario web de recuperación de contraseña (sin envío de correo).
     *
     * @return \Illuminate\View\View Vista auth.forgot-password
     */
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    /**
     * Procesa la solicitud de recuperación y muestra contacto del administrador.
     *
     * @param Request $request Correo electrónico del usuario
     * @return \Illuminate\View\View Vista auth.forgot-result con datos de contacto
     */
    public function processForgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email'    => 'Ingresa un correo válido.',
        ]);

        $user = User::withoutGlobalScopes()
            ->with('institution:id,name')
            ->where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return view('auth.forgot-result', [
                'found'          => false,
                'adminName'      => null,
                'adminEmail'     => null,
                'adminPhone'     => null,
                'userName'       => null,
                'institutionName'=> null,
                'message'        => self::RESET_PASSWORD_CONTACT_MESSAGE,
            ]);
        }

        $adminRoleQuery = function ($q) {
            $q->whereIn('name', ['Administrator', 'Administrador'])
                ->orWhere('name', 'ILIKE', '%admin%');
        };

        $admin = User::withoutGlobalScopes()
            ->where('institution_id', $user->institution_id)
            ->where('is_active', true)
            ->whereHas('roles', $adminRoleQuery)
            ->first();

        $adminSource = 'institution';

        // Si el mismo usuario es admin, úsalo como contacto válido.
        if (! $admin && $user->hasRole('Administrator')) {
            $admin = $user;
        }

        // Fallback: primer admin activo global del sistema.
        if (! $admin) {
            $admin = User::withoutGlobalScopes()
                ->where('is_active', true)
                ->whereHas('roles', $adminRoleQuery)
                ->first();

            if ($admin) {
                $adminSource = 'global';
            } else {
                $adminSource = 'none';
            }
        }

        return view('auth.forgot-result', [
            'found'           => true,
            'userName'        => $user->first_name,
            'institutionName' => $user->institution?->name,
            'adminName'       => trim(($admin?->first_name ?? '').' '.($admin?->last_name ?? '')),
            'adminEmail'      => $admin?->email,
            'adminPhone'      => null,
            'adminSource'     => $adminSource,
            'message'         => self::RESET_PASSWORD_CONTACT_MESSAGE,
        ]);
    }

    /**
     * Inicia sesión web con cookie de sesión y redirige al panel según el rol.
     *
     * @param Request $request Email y contraseña
     * @return RedirectResponse Redirección al dashboard o vuelta con errores
     */
    public function loginWeb(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        // Usuario no existe o inactivo
        if (!$user || !$user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['general' => 'Credenciales incorrectas.']);
        }

        // Cuenta bloqueada
        if ($user->isLocked()) {
            return back()
                ->withInput($request->only('email'))
                ->with('locked_until', $user->locked_until->format('H:i'));
        }

        // Contraseña incorrecta
        if (!Hash::check($request->password, $user->password_hash)) {
            $attempts = $user->failed_login_attempts + 1;
            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until'          => $attempts >= 5 ? now()->addMinutes(15) : null,
            ]);
            $remaining = 5 - $attempts;
            $msg = $attempts >= 5
                ? 'Cuenta bloqueada por 15 minutos.'
                : "Credenciales incorrectas. {$remaining} intentos restantes.";
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['general' => $msg]);
        }

        // Login exitoso
        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        // Iniciar sesión con Sanctum (stateful)
        Auth::login($user);
        $request->session()->regenerate();

        // Redirección por rol
        return match(true) {
            $user->hasRole('Administrator') => redirect()->route('dashboard'),
            $user->hasRole('Teacher')       => redirect()->route('dashboard'),
            $user->hasRole('Student')       => redirect()->route('dashboard'),
            default                         => redirect()->route('dashboard'),
        };
    }

    /**
     * Registra un docente o alumno con código de invitación e inicia sesión.
     *
     * @param Request $request Datos de registro, rol y código opcional
     * @return RedirectResponse Redirección al dashboard o vuelta con errores de validación
     */
    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name'  => trim((string) $request->input('last_name', '')),
        ]);

        $nameRule = ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[\s\'-][\p{L}]+)*$/u'];

        $request->validate([
            'first_name'      => $nameRule,
            'last_name'       => $nameRule,
            'email'           => 'required|email|unique:users,email',
            'role'            => 'required|in:Teacher,Student',
            'password'        => 'required|string|min:8|confirmed',
            'invitation_code' => 'nullable|string',
            'privacy_accepted' => 'accepted',
        ], [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.regex'    => 'El nombre solo puede contener letras, espacios, guiones o apóstrofes.',
            'last_name.required'  => 'El apellido es obligatorio.',
            'last_name.regex'     => 'El apellido solo puede contener letras, espacios, guiones o apóstrofes.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'privacy_accepted.accepted' => 'Debes aceptar el Aviso de Privacidad para registrarte.',
        ]);

        $institutionId   = null;
        $institutionCode = null;
        $aulaCode        = null;

        if ($request->role === 'Teacher') {
            // ── Docente: requiere código de institución ───────────────────────
            if (!$request->filled('invitation_code')) {
                return back()->withInput()
                    ->withErrors(['invitation_code' => 'Los docentes deben ingresar un código de institución.'])
                    ->with('_form', 'register');
            }

            $institutionCode = InstitutionCode::withoutGlobalScopes()
                ->where('code', strtoupper($request->invitation_code))
                ->where('role', 'Teacher')
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$institutionCode) {
                return back()->withInput()
                    ->withErrors(['invitation_code' => 'Código de institución inválido o expirado.'])
                    ->with('_form', 'register');
            }

            $institutionId = $institutionCode->institution_id;

        } elseif ($request->role === 'Student') {
            // ── Alumno: código de aula (opcional) o institución demo ─────────
            if ($request->filled('invitation_code')) {
                $aulaCode = app(EnrollmentService::class)
                    ->findValidInvitationCode($request->invitation_code);

                if (! $aulaCode) {
                    return back()->withInput()
                        ->withErrors(['invitation_code' => 'Código de aula inválido o expirado.'])
                        ->with('_form', 'register');
                }

                if (!$aulaCode->classroom->is_active) {
                    return back()->withInput()
                        ->withErrors(['invitation_code' => 'El aula no está activa.'])
                        ->with('_form', 'register');
                }

                if ($aulaCode->classroom->isFull()) {
                    return back()->withInput()
                        ->withErrors(['invitation_code' => 'El aula ha alcanzado su capacidad máxima.'])
                        ->with('_form', 'register');
                }

                $institutionId = $aulaCode->classroom->institution_id;
            } else {
                $institutionId = Institution::withoutGlobalScopes()
                    ->where('name', 'GAMA Demo')
                    ->first()?->id;
            }
        }

        if (!$institutionId) {
            return back()->withInput()
                ->withErrors(['general' => 'No se pudo determinar la institución. Contacta al administrador.'])
                ->with('_form', 'register');
        }

        try {
            $user = DB::transaction(function () use (
                $request,
                $institutionId,
                $institutionCode,
                $aulaCode
            ) {
                $user = User::create([
                    'institution_id'        => $institutionId,
                    'first_name'            => $request->first_name,
                    'last_name'             => $request->last_name,
                    'email'                 => $request->email,
                    'password_hash'         => bcrypt($request->password),
                    'is_active'             => true,
                    'failed_login_attempts' => 0,
                ]);

                $user->assignRole($request->role);

                if ($institutionCode) {
                    $institutionCode->update(['is_used' => true]);
                }

                if ($aulaCode) {
                    app(EnrollmentService::class)->enrollFromInvitationCode($aulaCode, $user);
                }

                return $user;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()
                ->withErrors(['invitation_code' => $e->getMessage()])
                ->with('_form', 'register');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Cierra la sesión web, invalida la sesión y regenera el token CSRF.
     *
     * @param Request $request Solicitud HTTP actual
     * @return RedirectResponse Redirección a la ruta de login
     */
    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}