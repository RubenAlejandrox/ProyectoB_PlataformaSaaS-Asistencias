<?php

/**
 * @descripcion  Modelo de aula/materia con grupo, periodo y umbral mínimo de asistencia.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.1.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios      2026-06-02 - Atributo grupo y displayName()
 *               2026-06-02 - Incorporación de cabecera de prólogo
 */


declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuidKey;
use App\Traits\HasInstitutionScope;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasUuidKey, HasInstitutionScope;

    protected $fillable = [
        'institution_id', 'teacher_id', 'subject_name',
        'period', 'grupo', 'min_attendance_pct', 'max_capacity', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Docente responsable del aula.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teacher()        { return $this->belongsTo(User::class, 'teacher_id'); }

    /**
     * Inscripciones de estudiantes en el aula.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments()    { return $this->hasMany(Enrollment::class); }

    /**
     * Sesiones de asistencia programadas o realizadas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sessions()       { return $this->hasMany(Session::class); }

    /**
     * Códigos de invitación para unirse al aula.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invitationCodes(){ return $this->hasMany(InvitationCode::class); }

    /**
     * Ciclos académicos del aula.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function academicCycles() { return $this->hasMany(AcademicCycle::class); }

    /**
     * Alertas de asistencia asociadas al aula.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function alerts()         { return $this->hasMany(Alert::class); }

    /**
     * Indica si el aula alcanzó su capacidad máxima de estudiantes activos.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->enrollments()
            ->where('is_active', true)
            ->count() >= $this->max_capacity;
    }

    /**
     * Etiqueta legible con materia y grupo para interfaces.
     *
     * @return string
     */
    public function displayName(): string
    {
        return "{$this->subject_name} — Grupo {$this->grupo}";
    }
}
