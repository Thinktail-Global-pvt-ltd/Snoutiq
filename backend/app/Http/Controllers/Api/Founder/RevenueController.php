<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\RevenueRequest;
use App\Services\Founder\RevenueService;
use Illuminate\Http\JsonResponse;

class RevenueController extends BaseController
{
    public function __construct(private readonly RevenueService $revenue)
    {
    }

    public function index(RevenueRequest $request): JsonResponse
    {
        [$from, $to] = $request->range();

        $result = $this->revenue->build(
            $request->grouping(),
            $from,
            $to,
            $request->includeProjections()
        );

        return $this->success([
            'group' => $request->grouping(),
            'range' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'buckets' => $result['buckets'],
            'projectionNextMonthPaise' => $result['projectionNextMonthPaise'],
        ]);
    }
}

