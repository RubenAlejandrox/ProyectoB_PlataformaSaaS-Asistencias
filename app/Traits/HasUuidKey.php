<?php

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

    public function initializeHasUuidKey(): void
    {
        $this->keyType      = 'string';
        $this->incrementing = false;
    }
}