<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait UsesAuthedExclusive
{

    /**
     * The "booting" method of the model, This help to magically create code for all new models
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
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
}
