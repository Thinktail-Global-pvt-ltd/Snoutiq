<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminOnboardingStatusService;

class AdminOnboardingStatusController extends Controller
{
    public function __construct(private AdminOnboardingStatusService $statusService)
    {
    }

    /**
     * GET /api/admin/onboarding/services
     */
    public function services()
    {
        return response()->json([
            'success' => true,
            'clinics' => $this->statusService->getServicesData(),
        ]);
    }

    /**
     * GET /api/admin/onboarding/video
     */
    public function video()
    {
        return response()->json([
            'success' => true,
            'clinics' => $this->statusService->getVideoData(),
        ]);
    }

    /**
     * GET /api/admin/onboarding/clinic-hours
     */
    public function clinicHours()
    {
        return response()->json([
            'success' => true,
            'clinics' => $this->statusService->getClinicHoursData(),
        ]);
    }

    /**
     * GET /api/admin/onboarding/emergency
     */
    public function emergency()
    {
        return response()->json([
            'success' => true,
            'clinics' => $this->statusService->getEmergencyData(),
        ]);
    }
}
