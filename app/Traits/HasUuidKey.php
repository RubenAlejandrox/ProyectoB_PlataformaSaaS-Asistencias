<?php

/**
 * @descripcion  Trait reutilizable HasUuidKey para modelos o controladores.
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

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuidKey
{
    protected static function bootHasUuidKey(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Configura el modelo para usar clave primaria UUID no autoincremental.
     *
     * @return void
     */
    public function initializeHasUuidKey(): void
    {
        $this->keyType      = 'string';
        $this->incrementing = false;
    }
}