<?php

namespace App\Traits;

use App\Counter;

trait UsesCode
{

    /**
     * The "booting" method of the model, This help to magically create uuid for all new models
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        self::creating(
            function ($model) {
                $codeId = $model->country === 'NG' ? 'PROPERTYNG' : 'PROPERTYNOTNG';

                $counter = Counter::findOrFail($codeId);
                $codeCount = $counter->count;
                $codeCode = $counter->code;
                $counter->count += 1;
                $counter->save();

                $model->code = $codeCode . $codeCount;
            }
        );
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'code';
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
}