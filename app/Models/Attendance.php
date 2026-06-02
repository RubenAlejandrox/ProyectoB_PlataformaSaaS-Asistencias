<?php

/**
 * @descripcion  Modelo Eloquent Attendance: representa entidad y relaciones del dominio.
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

class Attendance extends Model
{
    use HasUuidKey;

    protected $fillable = ['session_id', 'student_id', 'status'];

    /**
     * Sesión de clase a la que corresponde el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session()       { return $this->belongsTo(Session::class); }

    /**
     * Estudiante al que se registra la asistencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()       { return $this->belongsTo(User::class, 'student_id'); }

    /**
     * Justificación vinculada a esta falta o retardo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function justification() { return $this->hasOne(Justification::class); }

    /**
     * Indica si el estudiante fue marcado como presente.
     *
     * @return bool
     */
    public function isPresent(): bool { return $this->status === 'present'; }
}
