<?php

/**
 * @descripcion  Middleware HTTP SetInstitutionScope: filtra o enriquece solicitudes entrantes.
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
use Illuminate\Support\Facades\DB;

class SetInstitutionScope
{
    /**
     * Establece variables de sesión PostgreSQL para el aislamiento por institución y usuario.
     *
     * @param Request $request Solicitud HTTP entrante
     * @param Closure $next Siguiente middleware o controlador
     * @return mixed Respuesta de la cadena de middleware
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            DB::select('SELECT set_config(?, ?, false)', ['app.user_id', (string) $user->id]);

            if (! empty($user->institution_id)) {
                DB::select('SELECT set_config(?, ?, false)', [
                    'app.institution_id',
                    (string) $user->institution_id,
                ]);
            }
        }

        return $next($request);
    }
}