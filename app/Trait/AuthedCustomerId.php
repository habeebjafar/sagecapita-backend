<?php

namespace App\Traits;

//import auth facades
use Illuminate\Support\Facades\Auth;

trait AuthedCustomerId
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
                if (Auth::guard('customers')->check()) {
                    $model->customer_id = Auth::guard('customers')->user()->id;
                }
            }
        );
    }
}