<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FormatJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->is('api/founder*')) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (is_array($payload) && array_key_exists('success', $payload)) {
                return $response;
            }

            $timestamp = now()->toIso8601String();

            if ($response->isSuccessful()) {
                $response->setData([
                    'success' => true,
                    'data' => $payload,
                    'timestamp' => $timestamp,
                ]);
            } else {
                $errorMessage = 'Unexpected error';
                $errorCode = 'ERROR';

                if (is_array($payload)) {
                    $errorCode = Str::of($payload['code'] ?? $payload['error']['code'] ?? 'ERROR')->upper();
                    $errorMessage = $payload['message']
                        ?? $payload['error']['message']
                        ?? Response::$statusTexts[$response->getStatusCode()]
                        ?? $errorMessage;
                }

                $response->setData([
                    'success' => false,
                    'error' => [
                        'code' => (string) $errorCode,
                        'message' => (string) $errorMessage,
                    ],
                    'timestamp' => $timestamp,
                ]);
            }
        }

        return $response;
    }
}

