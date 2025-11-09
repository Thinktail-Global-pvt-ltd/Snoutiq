<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    protected function success(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    protected function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}

