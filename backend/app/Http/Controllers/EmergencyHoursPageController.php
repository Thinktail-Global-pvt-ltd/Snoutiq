<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class EmergencyHoursPageController extends Controller
{
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

        return view('snoutiq.emergency-hours', compact('doctors', 'page_title', 'clinicId'));
    }
}
