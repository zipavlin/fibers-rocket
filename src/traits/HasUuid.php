<?php

namespace Fibers\Rocket\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Used by Eloquent to get primary key type.
     * UUID Identified as a string.
     * @return string
     */
    public function getKeyType()
    {
        return 'char';
    }
    /**
     * Used by Eloquent to get if the primary key is auto increment value.
     * UUID is not.
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }
    /**
     * Add behavior to creating and saving Eloquent events.
     * @return void
     */
    public static function bootHasUuid()
    {
        // Create a UUID to the model if it does not have one
        static::creating(function ($model) {
            $model->keyType = 'char';
            $model->incrementing = false;
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });

        // Set original if someone try to change UUID on update/save existing model
        static::saving(function (Model $model) {
            $original_id = $model->getOriginal($model->getKeyName());
            if ($original_id !== $model->{$model->getKeyName()}) {
                $model->{$model->getKeyName()} = $original_id;
            }
        });
    }
}
