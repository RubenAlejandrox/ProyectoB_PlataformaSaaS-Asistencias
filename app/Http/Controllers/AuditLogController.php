<?php

/**
 * @descripcion  Controlador HTTP del módulo AuditLog: expone acciones web/API del dominio.
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

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Muestra la bitácora de auditoría paginada con estadísticas y entidades.
     *
     * @param Request $request Filtros: action, entity, search, from_date, to_date
     * @return View Vista bitacora.index
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $query = $this->buildFilteredQuery($request);

        $logs = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => AuditLog::withoutGlobalScopes()->count(),
            'create' => AuditLog::withoutGlobalScopes()->where('action', 'create')->count(),
            'update' => AuditLog::withoutGlobalScopes()->where('action', 'update')->count(),
            'delete' => AuditLog::withoutGlobalScopes()->where('action', 'delete')->count(),
        ];

        $entities = AuditLog::withoutGlobalScopes()
            ->select('entity')
            ->distinct()
            ->orderBy('entity')
            ->pluck('entity');

        return view('bitacora.index', compact('logs', 'stats', 'entities'));
    }

    /**
     * Exporta la bitácora filtrada como archivo CSV en streaming.
     *
     * @param Request $request Mismos filtros que index
     * @return StreamedResponse Descarga del archivo CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);

        $filename = 'bitacora_auditoria_' . now()->format('Ymd_His') . '.csv';
        $query = $this->buildFilteredQuery($request);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'fecha',
                'usuario_nombre',
                'usuario_correo',
                'accion',
                'entidad',
                'entidad_id',
                'datos_nuevos_json',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    $name = trim(($log->user?->first_name ?? '') . ' ' . ($log->user?->last_name ?? ''));
                    $email = $log->user?->email ?? '';

                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $name,
                        $email,
                        $log->action,
                        $log->entity,
                        $log->entity_id,
                        json_encode($log->new_value, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = AuditLog::withoutGlobalScopes()
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('entity')) {
            $query->where('entity', $request->string('entity'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->date('to_date'));
        }

        return $query;
    }
}
