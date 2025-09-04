<?php 
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // ❌ yaha '*' mat rakho, frontend domain add karo
    'allowed_origins' => [
        'http://localhost:5173',
        'http://10.163.123.215:3000',
        'https://snoutiq.com'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // ✅ ye true hona chahiye
];

