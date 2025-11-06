<?php

$credentialsPath = env('FIREBASE_CREDENTIALS');
if (empty($credentialsPath)) {
    $credentialsPath = storage_path('app/firebase/service-account.json');
}

$projectId = env('FIREBASE_PROJECT_ID', 'snoutiqapp');

return [
    'default' => env('FIREBASE_PROJECT', 'app'),

    'projects' => [
        'app' => [
            'credentials' => [
                'file' => $credentialsPath,
            ],
            'project_id' => $projectId,
            'database' => [
                'url' => env('FIREBASE_DATABASE_URL'),
            ],
            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
            ],
        ],
    ],
];
