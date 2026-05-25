<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    /**
     * Atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id', 
        'classroom_id', 
        'type', 
        'sent_at'
    ];
    /**
     * Obtiene el estudiante (usuario) asociado a la alerta.
     * Relación inversa utilizando una clave foránea personalizada (student_id).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Obtiene el salón de clases asociado a la alerta.
     * Relación inversa de uno a muchos (BelongsTo).
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}