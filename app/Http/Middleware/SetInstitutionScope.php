<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetInstitutionScope
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            DB::statement("SET app.institution_id = '{$user->institution_id}'");
            DB::statement("SET app.user_id = '{$user->id}'");
        }

        return $next($request);
    }
}