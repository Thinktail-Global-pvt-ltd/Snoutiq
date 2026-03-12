<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffectedSystem;
use Illuminate\Http\JsonResponse;

class AffectedSystemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AffectedSystem::query()
            ->select(['id', 'code', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ]);
    }
}

