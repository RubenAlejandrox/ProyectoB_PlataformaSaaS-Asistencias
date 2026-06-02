<?php

/**
 * @descripcion  Modelo Eloquent InstitutionCode: representa entidad y relaciones del dominio.
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

class InstitutionCode extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'institution_id', 'code', 'role', 'expires_at', 'is_used',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used'    => 'boolean',
        ];
    }

    /**
     * Institución que emitió el código de registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

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
     * Indica si el código puede usarse (no usado y no expirado).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }
}
