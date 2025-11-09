<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\DashboardRequest;
use App\Services\Founder\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(DashboardRequest $request): JsonResponse
    {
        return $this->success(
            $this->dashboard->build(
                $request->user(),
                $request->mode(),
                $request->period()
            )
        );
    }
}
