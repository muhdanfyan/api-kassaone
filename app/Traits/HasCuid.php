<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Visus\Cuid2\Cuid2;

trait HasCuid
{
    /**
     * Boot the trait.
     */
    protected static function bootHasCuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) new Cuid2();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
