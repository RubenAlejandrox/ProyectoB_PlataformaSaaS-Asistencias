<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->statefulApi();
        // Global — se ejecuta en cada request que entra a la aplicación
        $middleware->append(\App\Http\Middleware\SetInstitutionScope::class);

        // Aliases — se usan por su nombre clave en los archivos de rutas (ej. 'role:Teacher')
        $middleware->alias([
            'role'      => \App\Http\Middleware\CheckRole::class,
            'plan'      => \App\Http\Middleware\CheckPlanAccess::class,
            'auditoria' => \App\Http\Middleware\LogAuditoria::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
