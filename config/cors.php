<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',           // Development
        'http://localhost:5174',           // Development
        'http://localhost:5175',           // Development
        'http://localhost:5176',           // Development
        'http://localhost:5177',           // Development
        'http://localhost:3000',           // Development alternative
        'https://crm.ltm.studio',          // Production
        'https://www.crm.ltm.studio',      // Production www
    ],

    'allowed_origins_patterns' => [
        'http://localhost:*',              // Все localhost порты
        'https://*.ltm.studio',            // Все поддомены ltm.studio
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
    ],

    'max_age' => 86400, // 24 часа

    'supports_credentials' => true,

];
