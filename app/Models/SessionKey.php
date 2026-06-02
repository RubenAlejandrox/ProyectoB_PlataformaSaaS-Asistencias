<?php

/**
 * @descripcion  Modelo Eloquent SessionKey: representa entidad y relaciones del dominio.
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

class SessionKey extends Model
{
    use HasUuidKey;

    protected $fillable = ['session_id', 'access_key', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    /**
     * Sesión de clase asociada a la clave.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session() { return $this->belongsTo(Session::class); }

    /**
     * Indica si la clave ya expiró.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    /**
     * Indica si la clave está activa y dentro de vigencia.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
