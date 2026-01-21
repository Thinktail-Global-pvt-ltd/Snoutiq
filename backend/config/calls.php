<?php

return [
    'ring_timeout' => env('CALL_RING_TIMEOUT', 30),
    'presence_ttl' => env('CALL_PRESENCE_TTL', 70),
    // Keep busy TTL short in dev so doctors free up quickly even if jobs aren’t running
    'busy_ttl' => env('CALL_BUSY_TTL', 60),
];
