<?php

/**
 * @descripcion  Modelo Eloquent Enrollment: representa entidad y relaciones del dominio.
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

class Enrollment extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'classroom_id', 'student_id', 'enrolled_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime', 'is_active' => 'boolean'];
    }

    /**
     * Aula en la que está inscrito el estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom() { return $this->belongsTo(Classroom::class); }

    /**
     * Estudiante inscrito.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()   { return $this->belongsTo(User::class, 'student_id'); }
}
