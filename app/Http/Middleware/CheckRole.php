<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
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