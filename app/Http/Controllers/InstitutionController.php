<?php

/**
 * @descripcion  Controlador HTTP del módulo Institution: expone acciones web/API del dominio.
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

use App\Models\Institution;
use App\Models\InstitutionCode;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InstitutionController extends Controller
{
    /**
     * @param SupabaseStorageService $storage Almacenamiento de logos en Supabase
     */
    public function __construct(
        private SupabaseStorageService $storage
    ) {}

    /**
     * Lista instituciones paginadas con estadísticas (vista web o JSON).
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse Vista instituciones.index o JSON paginado
     */
    public function index()
    {
        $institutions = Institution::withoutGlobalScopes()
            ->withCount('classrooms')
            ->orderBy('name')
            ->paginate(15);

        $stats = [
            'total'      => Institution::withoutGlobalScopes()->count(),
            'active'     => Institution::withoutGlobalScopes()->where('is_active', true)->count(),
            'inactive'   => Institution::withoutGlobalScopes()->where('is_active', false)->count(),
            'classrooms' => \App\Models\Classroom::withoutGlobalScopes()->count(),
        ];

        if (request()->expectsJson()) {
            return response()->json($institutions);
        }

        return view('instituciones.index', compact('institutions', 'stats'));
    }

    /**
     * Crea una institución con logo opcional en Supabase Storage.
     *
     * @param Request $request name, logo opcional e is_active
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse Redirección o JSON 201
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:institutions,name',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'name.unique' => 'Ya existe una institución con ese nombre.',
            'logo.max'    => 'El logo no debe superar 2MB.',
        ]);

        $logoUrl = null;

        if ($request->hasFile('logo')) {
            $logoBucket = $this->storage->institutionLogosBucket();
            $logoUrl    = $this->storage->upload($request->file('logo'), $logoBucket);

            if (! $logoUrl) {
                $detail = $this->storage->getLastError() ?? 'Error al subir el logo.';

                return back()
                    ->withInput()
                    ->withErrors(['logo' => config('app.debug') ? $detail : 'Error al subir el logo. Intenta de nuevo.']);
            }
        }

        $institution = Institution::create([
            'name'      => $request->name,
            'logo_url'  => $logoUrl,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json($institution, 201);
        }

        return back()->with('success', "Institución \"{$request->name}\" creada exitosamente.");
    }

    /**
     * Actualiza nombre y logo de una institución existente.
     *
     * @param Request $request name y logo opcional
     * @param Institution $institution Institución a modificar
     * @return \Illuminate\Http\RedirectResponse Vuelta atrás con mensaje de éxito
     */
    public function update(Request $request, Institution $institution)
    {
        $request->validate([
            'name' => "required|string|max:255|unique:institutions,name,{$institution->id}",
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'name.unique' => 'Ya existe una institución con ese nombre.',
            'logo.max'    => 'El logo no debe superar 2MB.',
        ]);

        $logoUrl = $institution->logo_url;

        if ($request->hasFile('logo')) {
            $logoBucket = $this->storage->institutionLogosBucket();

            if ($institution->logo_url) {
                $oldPath = str_replace(
                    config('services.supabase.url') . "/storage/v1/object/public/{$logoBucket}/",
                    '',
                    $institution->logo_url
                );
                $this->storage->delete($logoBucket, $oldPath);
            }

            $logoUrl = $this->storage->upload($request->file('logo'), $logoBucket);

            if (! $logoUrl) {
                $detail = $this->storage->getLastError() ?? 'Error al subir el logo.';

                return back()
                    ->withInput()
                    ->withErrors(['logo' => config('app.debug') ? $detail : 'Error al subir el logo. Intenta de nuevo.']);
            }
        }

        $institution->update([
            'name'     => $request->name,
            'logo_url' => $logoUrl,
        ]);

        return back()->with('success', "Institución \"{$request->name}\" actualizada.");
    }

    /**
     * Activa o desactiva una institución (bloquea si tiene suscripción activa).
     *
     * @param Institution $institution Institución objetivo
     * @return \Illuminate\Http\RedirectResponse Vuelta atrás con éxito o error
     */
    public function toggleStatus(Institution $institution)
    {
        if ($institution->is_active) {
            $activeSubs = $institution->subscriptions()
                ->where('status', 'active')
                ->exists();

            if ($activeSubs) {
                return back()->withErrors([
                    'general' => "No se puede desactivar \"{$institution->name}\" porque tiene una suscripción activa."
                ]);
            }
        }

        $institution->update(['is_active' => !$institution->is_active]);

        $msg = $institution->is_active
            ? "Institución \"{$institution->name}\" reactivada."
            : "Institución \"{$institution->name}\" desactivada.";

        return back()->with('success', $msg);
    }

    /**
     * Genera un código de invitación institucional para docente o alumno.
     *
     * @param Request $request role: Teacher o Student
     * @param Institution $institution Institución emisora del código
     * @return \Illuminate\Http\RedirectResponse Vuelta atrás con datos del código generado
     */
    public function generateCode(Request $request, Institution $institution)
    {
        $request->validate([
            'role' => 'required|in:Teacher,Student',
        ]);

        // Generar código único de 8 chars
        do {
            $code = strtoupper(Str::random(8));
        } while (InstitutionCode::withoutGlobalScopes()->where('code', $code)->exists());

        $institutionCode = InstitutionCode::create([
            'institution_id' => $institution->id,
            'code'           => $code,
            'role'           => $request->role,
            'expires_at'     => now()->addDays(7),
            'is_used'        => false,
        ]);

        return back()->with('generated_code', [
            'code'        => $institutionCode->code,
            'role'        => $institutionCode->role,
            'expires_at'  => $institutionCode->expires_at->format('d/m/Y H:i'),
            'institution' => $institution->name,
        ]);
    }
}