<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminOnboardingStatusService;

class AdminVideoSlotOverviewController extends Controller
{
    protected AdminOnboardingStatusService $statusService;

    public function __construct(AdminOnboardingStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function __invoke()
    {
        $clinics = collect($this->statusService->getVideoData());

        $stats = [
            'total_clinics' => $clinics->count(),
            'clinics_with_config' => $clinics->where('has_any_video_config', true)->count(),
            'total_doctors' => $clinics->sum('doctor_count'),
            'doctors_with_config' => $clinics->sum('doctors_with_video'),
        ];

        return view('admin.video-slot-overview', [
            'clinics' => $clinics,
            'stats' => $stats,
        ]);
    }
}
