<?php

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
    // ── API Login (para tests y app móvil) ───────────────────────────────────
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

    // ── API Logout ────────────────────────────────────────────────────────────
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ── WEB Login (Blade form → sesión + redirección por rol) ────────────────
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

    // ── WEB Register ──────────────────────────────────────────────────────────
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'role'            => 'required|in:Teacher,Student',
            'password'        => 'required|string|min:8|confirmed',
            'invitation_code' => 'nullable|string',
            'privacy_accepted' => 'accepted',
        ], [
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

    // ── WEB Logout ────────────────────────────────────────────────────────────
    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}