<?php

return [
    'batch_size' => (int) env('PUSH_BATCH_SIZE', 500),
    'channel' => env('PUSH_LOG_CHANNEL', 'push'),
    'web_test_default_token' => env('PUSH_WEB_TEST_DEFAULT_TOKEN'),
    'marketing_test_token' => env('PUSH_MARKETING_TEST_TOKEN'),
];
