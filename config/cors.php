<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi CORS agar frontend upbs_biogen-client ({{APP_URL}})
    | dapat mengakses API di upbs_biogen-admin ({{APP_URL}}).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Izinkan semua metode untuk fleksibilitas (GET/POST/OPTIONS, dll.)
    'allowed_methods' => ['*'],

    // Frontend client yang diizinkan
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => [],

    // Header yang diizinkan
    'allowed_headers' => ['*'],

    // Header yang diekspos ke client
    'exposed_headers' => [],

    'max_age' => 0,

    // Tidak menggunakan credential (cookie) untuk endpoint publik API
    'supports_credentials' => false,
];