<?php

/**
 * @descripcion  Modelo Eloquent InvitationCode: representa entidad y relaciones del dominio.
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

class InvitationCode extends Model
{
    use HasUuidKey;

    protected $fillable = ['classroom_id', 'code', 'expires_at', 'is_used'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_used' => 'boolean'];
    }

    /**
     * Aula a la que permite acceder el código.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom() { return $this->belongsTo(Classroom::class); }

    /**
     * Indica si el código ya expiró.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    /**
     * Indica si el código puede usarse (no expirado).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}
