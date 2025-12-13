<?php

// Hardcode Firebase configuration to avoid .env influence for FCM
$credentialsPath = storage_path('app/firebase/service-account.json');

return [
    'default' => 'app',

    'projects' => [
        'app' => [
            'credentials' => [
                'file' => $credentialsPath,
            ],
            'project_id' => 'snoutiqapp',
            'database' => [
                'url' => 'https://snoutiqapp-default-rtdb.firebaseio.com',
            ],
            'storage' => [
                'default_bucket' => 'snoutiqapp.firebasestorage.app',
            ],
        ],
    ],
];
