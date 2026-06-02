<?php

/**
 * @descripcion  Modelo Eloquent Institution: representa entidad y relaciones del dominio.
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

namespace App\Models;

use App\Traits\HasUuidKey;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasUuidKey;

    protected $fillable = ['name', 'logo_url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Usuarios registrados en la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()        { return $this->hasMany(User::class); }

    /**
     * Aulas o materias de la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classrooms()   { return $this->hasMany(Classroom::class); }

    /**
     * Suscripciones contratadas por la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(){ return $this->hasMany(Subscription::class); }

    /**
     * Pagos realizados por la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()     { return $this->hasMany(Payment::class); }

    /**
     * Ciclos académicos asociados a la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function academicCycles(){ return $this->hasMany(AcademicCycle::class); }

    /**
     * Obtiene la suscripción vigente con plan cargado, si existe.
     *
     * @return Subscription|null
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->with('plan')
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * Plan del paquete activo de la institución.
     *
     * @return Plan|null
     */
    public function activePlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    /**
     * Códigos de invitación o registro emitidos por la institución.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function institutionCodes()
    {
    return $this->hasMany(InstitutionCode::class);
    }
}
