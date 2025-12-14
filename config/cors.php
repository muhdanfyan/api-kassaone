<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | ⚠️ TIDAK BOLEH HARDCODE ORIGIN DI SINI!
    | Semua origin harus dari .env untuk flexibility
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'login',
        'register',
        'logout',
        'refresh-token',
        'refresh-csrf',
        'member/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    /*
     * ✅ AMBIL DARI .env - TIDAK HARDCODE!
     * Format: "http://localhost:5173,https://app.com"
     */
    'allowed_origins' => array_filter(
        array_map(
            'trim',
            explode(',', env('CORS_ALLOWED_ORIGINS', ''))
        )
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'X-CSRF-Token',
    ],

    'max_age' => 0,

    /*
     * ⚠️ HARUS true jika frontend pakai withCredentials: true
     */
    'supports_credentials' => true,

];
