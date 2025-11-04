<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi CORS agar frontend upbs_biogen-client (localhost:8001)
    | dapat mengakses API di upbs_biogen-admin (localhost:8000).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Izinkan semua metode untuk fleksibilitas (GET/POST/OPTIONS, dll.)
    'allowed_methods' => ['*'],

    // Frontend client yang diizinkan
    'allowed_origins' => [
        'http://localhost:8001',
    ],

    'allowed_origins_patterns' => [],

    // Header yang diizinkan
    'allowed_headers' => ['*'],

    // Header yang diekspos ke client
    'exposed_headers' => [],

    'max_age' => 0,

    // Tidak menggunakan credential (cookie) untuk endpoint publik API
    'supports_credentials' => false,
];