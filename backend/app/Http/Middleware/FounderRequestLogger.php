<?php

namespace App\Http\Middleware;

use App\Services\Logging\FounderAudit;
use App\Support\QueryTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FounderRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/founder*')) {
            return $next($request);
        }

        $tracker = new QueryTracker();
        $tracker->start();

        $startedAt = microtime(true);
        $response = null;
        $exception = null;

        try {
            $response = $next($request);
            return $response;
        } catch (Throwable $e) {
            $exception = $e;
            throw $e;
        } finally {
            $metrics = $tracker->finish();
            $context = [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response?->getStatusCode() ?? 500,
                'user_id' => optional($request->user())->id,
                'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
            ];

            $context = array_merge($context, $metrics);

            if ($exception) {
                FounderAudit::error('api.request.failed', $exception, $context);
            } else {
                FounderAudit::info('api.request', $context);
            }
        }
    }
}
