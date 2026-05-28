<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogAuditoria
{
    // Métodos que se auditan
    private array $auditMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    // Rutas excluidas de auditoría
    private array $excludeRoutes = [
        'api/login',
        'api/logout',
        'api/register',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!in_array($request->method(), $this->auditMethods)) {
            return $response;
        }

        if (!auth()->check()) {
            return $response;
        }

        foreach ($this->excludeRoutes as $route) {
            if ($request->is($route)) {
                return $response;
            }
        }

        // Solo auditar respuestas exitosas (2xx)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            try {
                AuditLog::create([
                    'user_id'    => auth()->user()->id,
                    'entity'     => $this->resolveEntity($request),
                    'entity_id'  => $this->resolveEntityId($request, $response),
                    'action'     => $this->resolveAction($request),
                    'old_value'  => null,
                    'new_value'  => $request->except(['password', 'password_hash']),
                ]);
            } catch (\Exception $e) {
                // Si el log falla, no interrumpir el flujo
                \Log::error('AuditLog failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    private function resolveEntity(Request $request): string
    {
        $segments = $request->segments();
        // api/classrooms/uuid → classrooms
        return $segments[1] ?? 'unknown';
    }

    private function resolveEntityId(Request $request, $response): string
    {
        // Intentar obtener el ID del segmento de la URL
        $segments = $request->segments();
        if (isset($segments[2])) {
            return $segments[2];
        }

        // Si es un POST nuevo, intentar leer el ID de la respuesta
        try {
            $data = json_decode($response->getContent(), true);
            return $data['id'] ?? $data['data']['id'] ?? 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    private function resolveAction(Request $request): string
    {
        return match($request->method()) {
            'POST'   => 'create',
            'PUT',
            'PATCH'  => 'update',
            'DELETE' => 'delete',
            default  => 'unknown',
        };
    }
}