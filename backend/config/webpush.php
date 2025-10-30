<?php

return [
    'public_key' => env('WEB_PUSH_PUBLIC_KEY'),
    'private_key' => env('WEB_PUSH_PRIVATE_KEY'),
    'subject' => env('WEB_PUSH_SUBJECT', 'mailto:admin@snoutiq.com'),
    'ttl' => env('WEB_PUSH_TTL', 60),
];
