<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('perfil.index', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'email.unique' => 'Este correo ya está en uso.',
        ]);

        $user->update($validated);

        return back()->with('success', 'Datos actualizados correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password_hash)) {
            return back()->withErrors([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        $user->update([
            'password_hash' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
