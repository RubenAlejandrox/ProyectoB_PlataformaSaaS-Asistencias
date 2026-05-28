<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionCode;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InstitutionController extends Controller
{
    public function __construct(
        private SupabaseStorageService $storage
    ) {}

    // ── index ─────────────────────────────────────────────────────────────────
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

    // ── store ─────────────────────────────────────────────────────────────────
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
            $logoUrl = $this->storage->upload(
                $request->file('logo'),
                'institution-logos'
            );

            if (!$logoUrl) {
                return back()
                    ->withInput()
                    ->withErrors(['logo' => 'Error al subir el logo. Intenta de nuevo.']);
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

    // ── update ────────────────────────────────────────────────────────────────
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
            // Eliminar logo anterior si existe
            if ($institution->logo_url) {
                $oldPath = str_replace(
                    config('services.supabase.url') . '/storage/v1/object/public/institution-logos/',
                    '',
                    $institution->logo_url
                );
                $this->storage->delete('institution-logos', $oldPath);
            }

            $logoUrl = $this->storage->upload(
                $request->file('logo'),
                'institution-logos'
            );

            if (!$logoUrl) {
                return back()
                    ->withInput()
                    ->withErrors(['logo' => 'Error al subir el logo. Intenta de nuevo.']);
            }
        }

        $institution->update([
            'name'     => $request->name,
            'logo_url' => $logoUrl,
        ]);

        return back()->with('success', "Institución \"{$request->name}\" actualizada.");
    }

    // ── toggleStatus ──────────────────────────────────────────────────────────
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

    // ── generateCode — código de invitación por institución y rol ────────────
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