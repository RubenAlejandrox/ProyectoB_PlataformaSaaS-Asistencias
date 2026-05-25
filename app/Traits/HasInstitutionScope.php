<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasInstitutionScope
{
    protected static function booted(): void
    {
        static::addGlobalScope('institution', function (Builder $query) {
            if (auth()->check()) {
                $query->where(
                    static::getTable() . '.institution_id',
                    auth()->user()->institution_id
                );
            }
        });
    }
}