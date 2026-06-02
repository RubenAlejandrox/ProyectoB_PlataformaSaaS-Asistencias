<?php

/**
 * @descripcion  Modelo Eloquent User: representa entidad y relaciones del dominio.
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

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;              // ← agregar
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuidKey;


class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens, HasUuidKey;

    protected $fillable = [
        'institution_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'is_active',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'locked_until'           => 'datetime',
            'failed_login_attempts'  => 'integer',
        ];
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    /**
     * Devuelve el hash de contraseña usado por el guard de autenticación.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Indica si la cuenta está bloqueada por intentos fallidos de inicio de sesión.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Institución a la que pertenece el usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Aulas impartidas por el usuario cuando actúa como docente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    /**
     * Inscripciones del usuario como estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Registros de asistencia del estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    /**
     * Justificaciones presentadas por el estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function justifications()
    {
        return $this->hasMany(Justification::class, 'student_id');
    }

    /**
     * Alertas generadas para el estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class, 'student_id');
    }
}
