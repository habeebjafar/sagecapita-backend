<?php

return [
    'defaults' => [
        'guard' => 'api',
        // 'passwords' => 'users',
        'passwords' => 'customers'
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'users' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'customers' => [
            'driver' => 'jwt',
            'provider' => 'customers',
        ]
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\User::class
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => \App\Customer::class
        ]
    ],
    'passwords' => [
        'customers' => [
            'provider' => 'customers',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ], 'password_timeout' => 10800,
];
