<?php

return [
    // Midtrans Server Key (source of truth for signature verification)
    'gateway_secret' => env('MIDTRANS_SERVER_KEY', env('PAYMENT_GATEWAY_SECRET')),

    // Base URL for Midtrans API. Defaults to sandbox when not production.
    'midtrans' => [
        'base_url' => env('MIDTRANS_BASE_URL') ?? (env('APP_ENV') === 'production'
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com'),
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    ],
];

