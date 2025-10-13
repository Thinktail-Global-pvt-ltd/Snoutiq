<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;

class VideoSchedulePageController extends Controller
{
    // Pet parent-facing viewer (read-only)
    public function petIndex(Request $request)
    {
        $doctors = Doctor::orderBy('doctor_name')->get(['id','doctor_name']);
        $readonly = true;
        $page_title = 'Video Calling Schedule by Doctor';
        return view('snoutiq.video-calling-schedule', compact('doctors','readonly','page_title'));
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
        $readonly = false;
        $page_title = 'Manage Video Calling Schedule (Separate)';
        return view('snoutiq.video-calling-schedule', compact('doctors','readonly','page_title'));
    }
}
