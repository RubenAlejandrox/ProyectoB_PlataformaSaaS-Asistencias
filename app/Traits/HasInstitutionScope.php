<?php

/**
 * @descripcion  Trait reutilizable HasInstitutionScope para modelos o controladores.
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

use Illuminate\Database\Eloquent\Builder;

trait HasInstitutionScope
{
    protected static function booted(): void
    {
        static::addGlobalScope('institution', function (Builder $query) {
            if (app()->runningInConsole()) return;  // ← agregar
            if (!auth()->check()) return;           // ← agregar
            
            // ✅ Usar $query->getModel()->getTable() en lugar de static::getTable()
            $query->where(
                $query->getModel()->getTable() . '.institution_id',
                auth()->user()->institution_id
            );
        });
    }
}