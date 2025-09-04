<?php 
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // all HTTP methods (GET, POST, etc.)

    'allowed_origins' => [
        'http://localhost:5173',
        'http://10.163.123.215:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

