<?php

return [
    'batch_size' => (int) env('PUSH_BATCH_SIZE', 500),
    'channel' => env('PUSH_LOG_CHANNEL', 'push'),
];

