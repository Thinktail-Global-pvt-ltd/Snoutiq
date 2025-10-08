<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VetRegisterationTemp;
use App\Models\BusinessHour;
use App\Models\Doctor;

class BusinessHourController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'vet_id'             => 'nullable|integer',
            'clinic_slug'        => 'nullable|string',
            'open_time'          => 'required|array',
            'open_time.*'        => 'nullable|date_format:H:i',
            'close_time'         => 'required|array',
            'close_time.*'       => 'nullable|date_format:H:i',
            'closed'             => 'nullable|array',
            'closed.*'           => 'nullable|boolean',
        ]);

        // Resolve clinic: prefer explicit vet_id, then slug, then session user -> doctor -> clinic
        $clinic = null;
        if (!empty($data['vet_id'])) {
            $clinic = VetRegisterationTemp::find($data['vet_id']);
        }
        if (!$clinic && !empty($data['clinic_slug'])) {
            $clinic = VetRegisterationTemp::where('slug', $data['clinic_slug'])->first();
        }
        if (!$clinic) {
            $sessionUserId = session('user_id');
            if ($sessionUserId) {
                $doctor = Doctor::find($sessionUserId);
                if ($doctor) {
                    $clinic = VetRegisterationTemp::find($doctor->vet_registeration_id);
                }
            }
        }
        if (!$clinic) {
            return back()->withInput()->with('error', 'Unable to resolve clinic. Provide vet_id or login as a doctor.');
        }

        // Save 1..7 (Mon..Sun)
        for ($day = 1; $day <= 7; $day++) {
            $isClosed = isset($data['closed'][$day]) && (int)$data['closed'][$day] === 1;
            BusinessHour::updateOrCreate(
                [
                    'vet_registeration_id' => $clinic->id,
                    'day_of_week'          => $day,
                ],
                [
                    'open_time' => $isClosed ? null : ($data['open_time'][$day] ?? null),
                    'close_time'=> $isClosed ? null : ($data['close_time'][$day] ?? null),
                    'closed'    => $isClosed,
                ]
            );
        }

        return back()->with('success', 'Business hours saved');
    }
}
