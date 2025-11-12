<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Services\OnboardingProgressService;

class EmergencyHoursPageController extends Controller
{
    protected OnboardingProgressService $progressService;

    public function __construct(OnboardingProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function editor(Request $request)
    {
        $vetId = $request->session()->get('user_id')
            ?? data_get($request->session()->get('user'), 'id');

        $doctors = collect();
        if ($vetId) {
            $doctors = Doctor::where('vet_registeration_id', $vetId)
                ->orderBy('doctor_name')
                ->get(['id', 'doctor_name']);
        }

        if ($doctors->isEmpty()) {
            $doctors = Doctor::orderBy('doctor_name')
                ->limit(200)
                ->get(['id', 'doctor_name', 'vet_registeration_id']);
        }

        $page_title = 'Emergency Coverage Hours';
        $clinicId = $vetId ? (int) $vetId : null;
        $stepStatus = $this->progressService->getStatusForRequest($request);

        return view('snoutiq.emergency-hours', compact('doctors', 'page_title', 'clinicId', 'stepStatus'));
    }
}
