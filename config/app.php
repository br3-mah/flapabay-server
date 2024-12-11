<?php

return [
    'name' => env('APP_NAME', 'Lumen'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'UTC',
    'locale' => 'en',

    'key' => env('APP_KEY'),

    'providers' => [
        // Add any Service Providers here
        // App\Providers\AppServiceProvider::class,
        // App\Providers\AuthServiceProvider::class,
    ],
];
