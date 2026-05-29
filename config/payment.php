<?php

return [

    'gateway' => env('PAYMENT_GATEWAY', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'MXN'),

    'manual' => [
        'enabled' => env('PAYMENT_MANUAL_ENABLED', true),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    ],

];
