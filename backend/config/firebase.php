<?php

// Keep the credentials as a file path so config caching does not bake in a stale
// service account. The path should point to a real Firebase service-account JSON.
$defaultCredentialsPath = storage_path('app/firebase/service-account.json');

$looksLikeClientFirebaseConfig = static function (string $path): bool {
    $normalizedPath = str_replace('\\', '/', $path);
    $basename = strtolower(basename($normalizedPath));

    if (in_array($basename, ['google-services.json', 'firebaseconfig.js', 'firebase-config.js'], true)) {
        return true;
    }

    if (!is_file($path) || !is_readable($path)) {
        return false;
    }

    $content = @file_get_contents($path);
    if (!is_string($content) || trim($content) === '') {
        return false;
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return false;
    }

    $isServiceAccount = ($decoded['type'] ?? null) === 'service_account'
        && !empty($decoded['private_key'])
        && !empty($decoded['client_email']);

    $looksLikeClientConfig = isset($decoded['project_info'])
        || isset($decoded['client'])
        || isset($decoded['apiKey'])
        || isset($decoded['messagingSenderId'])
        || isset($decoded['appId']);

    return $looksLikeClientConfig && !$isServiceAccount;
};

$credentialsPath = env('FIREBASE_CREDENTIALS', $defaultCredentialsPath);

if (!is_string($credentialsPath) || trim($credentialsPath) === '') {
    $credentialsPath = $defaultCredentialsPath;
}

$credentialsPath = trim($credentialsPath);

if ($looksLikeClientFirebaseConfig($credentialsPath)) {
    $credentialsPath = $defaultCredentialsPath;
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
