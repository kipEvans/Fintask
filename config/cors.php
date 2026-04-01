<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Update allowed_origins to match your frontend URL in production.
    | e.g. 'allowed_origins' => ['https://fintrack.yourdomain.com']
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',   // React / Next.js dev
        'http://localhost:5173',   // Vite dev server
        'http://localhost:8080',   // Vue CLI
        env('FRONTEND_URL', '*'),  // Set FRONTEND_URL= in .env for production
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
