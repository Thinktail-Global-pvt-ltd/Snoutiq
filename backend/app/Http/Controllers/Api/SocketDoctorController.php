<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

class SocketDoctorController extends Controller
{
    public function show(Doctor $doctor): JsonResponse
    {
        $doctor->loadMissing('clinic');

        return response()->json([
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->doctor_name,
            'clinic_id' => $doctor->clinic?->id,
            'clinic_name' => $doctor->clinic->name ?? null,
            'clinic_city' => $doctor->clinic->city ?? null,
        ]);
    }
}

