<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
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
    ]
];
