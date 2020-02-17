<?php

namespace App\Traits;

use App\Counter;
//import auth facades
use Illuminate\Support\Facades\Auth;

trait UsesCodeAndAuthedExclusive
{

    /**
     * The "booting" method of the model, This help to magically create code for all new models
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
                $model->user_id = Auth::guard('users')->user()->id;
            }
        );

        self::retrieved(
            function ($model) {
                if ($model->is_exclusive && !Auth::guard('customers')->check() && !Auth::guard('users')->check()) {
                    $model->price && ($model->price = null);
                    $model->price_lower_range && ($model->price_lower_range = null);
                    $model->price_upper_range && ($model->price_upper_range = null);
                }
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