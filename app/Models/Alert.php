<?php

/**
 * @descripcion  Modelo Eloquent Alert: representa entidad y relaciones del dominio.
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

class Alert extends Model
{
    use HasUuidKey;

    protected $fillable = ['student_id', 'classroom_id', 'type', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /**
     * Estudiante destinatario de la alerta.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()   { return $this->belongsTo(User::class, 'student_id'); }

    /**
     * Aula relacionada con la alerta.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom() { return $this->belongsTo(Classroom::class); }
}
