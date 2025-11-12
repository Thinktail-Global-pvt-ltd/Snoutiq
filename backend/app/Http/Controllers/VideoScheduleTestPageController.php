<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Services\OnboardingProgressService;

class VideoScheduleTestPageController extends Controller
{
    protected OnboardingProgressService $progressService;

    public function __construct(OnboardingProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    // Pet parent-facing viewer (read-only)
    public function petIndex(Request $request)
    {
        $doctors = Doctor::orderBy('doctor_name')->get(['id','doctor_name']);
        $readonly = true;
        $page_title = 'Video Calling Schedule Test (Read-only)';
        $stepStatus = $this->progressService->getStatusForRequest($request);
        return view('snoutiq.video-calling-test', compact('doctors','readonly','page_title','stepStatus'));
    }

    // Optional: provider/editor view (write-enabled) – not used by pet sidebar
    public function editor(Request $request)
    {
      
        $vetId = $request->session()->get('user_id') ?? data_get($request->session()->get('user'), 'id');
        $doctors = collect();
        if ($vetId) {
            $doctors = Doctor::where('vet_registeration_id', $vetId)
                ->orderBy('doctor_name')
                ->get(['id','doctor_name']);
        }
        
        // Fallback: if no doctors resolved for this session, show a global list
        if ($doctors->isEmpty()) {
            $doctors = Doctor::orderBy('doctor_name')->limit(300)->get(['id','doctor_name']);
        }
        $readonly = false;
        $page_title = 'Manage Video Calling Schedule Test (Separate)';
        $stepStatus = $this->progressService->getStatusForRequest($request);
        return view('snoutiq.video-calling-test', compact('doctors','readonly','page_title','stepStatus'));
    }
}
