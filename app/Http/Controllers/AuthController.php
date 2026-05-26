<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        // Usuario no existe
        if (!$user || !$user->is_active) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // Cuenta bloqueada
        if ($user->isLocked()) {
            return response()->json([
                'message'    => 'Account locked. Try again after ' . $user->locked_until->format('H:i'),
                'locked_until' => $user->locked_until,
            ], 423);
        }

        // Contraseña incorrecta
        if (!Hash::check($request->password, $user->password_hash)) {
            $attempts = $user->failed_login_attempts + 1;

            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until'          => $attempts >= 5 ? now()->addMinutes(15) : null,
            ]);

            $remaining = 5 - $attempts;

            return response()->json([
                'message'           => $attempts >= 5
                    ? 'Account locked for 15 minutes.'
                    : "Invalid credentials. {$remaining} attempts remaining.",
                'attempts_remaining' => max(0, $remaining),
            ], 401);
        }

        // Login exitoso — resetear intentos
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        // Prevenir sesión doble — revocar tokens anteriores
        $user->tokens()->delete();

        // Crear nuevo token Sanctum
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
        ], 200);
    }

    public function logout(): JsonResponse
    {
        // Revocar token actual
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ], 200);
    }
}