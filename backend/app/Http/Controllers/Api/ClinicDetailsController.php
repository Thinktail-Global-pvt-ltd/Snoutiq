<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;

class ClinicDetailsController extends Controller
{
    /**
     * Return clinic profile plus its doctors for a given clinic/vet id.
     */
    public function show(Request $request, $clinicId = null)
    {
        $clinicId = $clinicId ?? $request->input('clinic_id');

        if (!$clinicId || !is_numeric($clinicId) || (int) $clinicId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing clinic_id',
            ], 422);
        }

        $clinicId = (int) $clinicId;

        $clinic = VetRegisterationTemp::with(['doctors' => function ($q) {
            $q->orderBy('doctor_name');
        }])->find($clinicId);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $doctors = $clinic->doctors ?? collect();

        return response()->json([
            'success'      => true,
            'clinic_id'    => $clinicId,
            'clinic'       => $clinic,
            'doctors'      => $doctors->values(),
            'doctor_count' => $doctors->count(),
        ]);
    }
}

