<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FounderAudit
{
    public static function info(string $event, array $context = []): void
    {
        Log::channel('founder')->info($event, $context + [
            'ts' => now()->toIso8601String(),
        ]);
    }

    public static function error(string $event, Throwable $e, array $context = []): void
    {
        Log::channel('founder')->error($event, $context + [
            'error' => $e->getMessage(),
            'trace' => Str::of($e->getTraceAsString())->limit(2000),
            'ts' => now()->toIso8601String(),
        ]);
    }
}

