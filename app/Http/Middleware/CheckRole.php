<?php

/**
 * @descripcion  Middleware HTTP CheckRole: filtra o enriquece solicitudes entrantes.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Permite el acceso solo si el usuario autenticado tiene alguno de los roles indicados.
     *
     * @param Request $request Solicitud HTTP entrante
     * @param Closure $next Siguiente middleware o controlador
     * @param string ...$roles Nombres de roles permitidos (Spatie)
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Redirección a login, JSON 401/403 o continúa la cadena
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            if (!$request->expectsJson()) {
                return redirect()->route('login');
            }

            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        foreach ($roles as $role) {
            if (auth()->user()->hasRole($role)) {
                return $next($request);
            }
        }

        if (!$request->expectsJson()) {
            abort(403, 'Unauthorized. Required role: ' . implode(' or ', $roles));
        }

        return response()->json([
            'message' => 'Unauthorized. Required role: ' . implode(' or ', $roles)
        ], 403);
    }
}