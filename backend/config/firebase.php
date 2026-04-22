<?php

// Keep the credentials as a file path so config caching does not bake in a stale
// service account. The path should point to a real Firebase service-account JSON.
$credentialsPath = env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json'));
if (!is_string($credentialsPath) || trim($credentialsPath) === '') {
    $credentialsPath = storage_path('app/firebase/service-account.json');
}

return [
    'default' => 'app',

    'projects' => [
        'app' => [
            'credentials' => ['file' => $credentialsPath],
            'project_id' => env('FIREBASE_PROJECT_ID', 'snoutiqapp'),
            'database' => [
                'url' => env('FIREBASE_DATABASE_URL', 'https://snoutiqapp-default-rtdb.firebaseio.com'),
            ],
            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_BUCKET', 'snoutiqapp.firebasestorage.app'),
            ],
        ],
    ],
];
