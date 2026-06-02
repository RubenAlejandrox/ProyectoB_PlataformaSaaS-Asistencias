<?php

/**
 * @descripcion  Controlador HTTP del módulo Profile: expone acciones web/API del dominio.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Muestra el perfil del usuario autenticado.
     *
     * @return View Vista perfil.index
     */
    public function index(): View
    {
        return view('perfil.index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Actualiza nombre y correo del perfil del usuario autenticado.
     *
     * @param Request $request first_name, last_name y email
     * @return RedirectResponse Vuelta atrás con mensaje de éxito
     */
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

    /**
     * Cambia la contraseña validando la contraseña actual.
     *
     * @param Request $request current_password, password y password_confirmation
     * @return RedirectResponse Vuelta atrás con éxito o error en current_password
     */
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
