<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY', 'rzp_test_1nhE9190sR3rkP'),
        'secret' => env('RAZORPAY_SECRET', 'L6CPZlUwrKQpdC9N3TRX8gIh'),
    ],

    'notifications' => [
        'secret' => env('DOCTOR_NOTIFICATION_SECRET'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-2.0-flash'),
    ],

    'agora' => [
        'app_id' => env('AGORA_APP_ID'),
        'certificate' => env('AGORA_APP_CERTIFICATE'),
        'customer_id' => env('AGORA_CUSTOMER_ID'),
        'customer_secret' => env('AGORA_CUSTOMER_SECRET'),
        'rest_endpoint' => env('AGORA_REST_ENDPOINT', 'https://api.agora.io'),
        'mode' => env('AGORA_RECORDING_MODE', 'mix'),
        'recording' => [
            'uid' => env('AGORA_RECORDING_UID', '10000'),
            'resource_expire_hours' => (int) env('AGORA_RECORDING_RESOURCE_EXPIRE_HOURS', 24),
            'max_idle_time' => (int) env('AGORA_RECORDING_MAX_IDLE', 30),
            'stream_types' => (int) env('AGORA_RECORDING_STREAM_TYPES', 2),
            'channel_type' => (int) env('AGORA_RECORDING_CHANNEL_TYPE', 1),
            'subscribe_uids' => explode(',', env('AGORA_RECORDING_SUBSCRIBE_UIDS', '#allstream#')),
            'token_ttl' => (int) env('AGORA_RECORDING_TOKEN_TTL', 3600),
            'disk' => env('AGORA_RECORDING_DISK', env('FILESYSTEM_DISK', 'local')),
            'auto_start' => filter_var(env('AGORA_AUTO_START_RECORDING', true), FILTER_VALIDATE_BOOLEAN),
            'auto_stop' => filter_var(env('AGORA_AUTO_STOP_RECORDING', true), FILTER_VALIDATE_BOOLEAN),
            'vendor' => (int) env('AGORA_STORAGE_VENDOR', 3), // 3 => AWS S3
            'region' => (int) env('AGORA_STORAGE_REGION', 14),
            'bucket' => env('AGORA_STORAGE_BUCKET', env('AWS_BUCKET')),
            'access_key' => env('AGORA_STORAGE_ACCESS_KEY', env('AWS_ACCESS_KEY_ID')),
            'secret_key' => env('AGORA_STORAGE_SECRET_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'file_path' => env('AGORA_STORAGE_FILE_PREFIX', 'agora/recordings'),
            'callback_url' => env('AGORA_RECORDING_CALLBACK_URL'),
            'auto_transcribe' => filter_var(env('AGORA_AUTO_TRANSCRIBE', false), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    'transcript' => [
        'provider' => env('TRANSCRIPT_PROVIDER', 'gemini'),
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_TRANSCRIPT_MODEL', 'gpt-4o-mini-transcribe'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_TRANSCRIPT_MODEL', env('GEMINI_MODEL', 'gemini-1.5-flash')),
        ],
    ],

    'google_identity' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],
];
