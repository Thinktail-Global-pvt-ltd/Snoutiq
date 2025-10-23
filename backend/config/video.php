<?php

declare(strict_types=1);

return [
    'night' => [
        // Default number of consecutive IST calendar days to publish when an admin runs the publish action.
        'publish_span_days' => (int) env('VIDEO_NIGHT_PUBLISH_SPAN_DAYS', 60),
        // Hard cap to avoid accidentally generating an excessively large range.
        'publish_span_max_days' => (int) env('VIDEO_NIGHT_PUBLISH_SPAN_MAX_DAYS', 180),
        // Window (in days) for mirroring commitments/releases forward so slots stay visible each day.
        'recurring_commit_days' => (int) env('VIDEO_NIGHT_RECURRING_COMMIT_DAYS', 60),
    ],
];

