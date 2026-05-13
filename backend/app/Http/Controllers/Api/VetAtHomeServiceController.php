<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\VetAtHomeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VetAtHomeServiceController extends Controller
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
        ]);

        $service = VetAtHomeService::query()
            ->where('clinic_id', $data['clinic_id'])
            ->where('doctor_id', $data['doctor_id'] ?? null)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'is_enabled' => ['nullable', 'boolean'],
            'service_hours' => ['nullable', 'string', 'max:255'],
            'response_time' => ['nullable', 'string', 'max:255'],
            'base_payout' => ['nullable', 'numeric', 'min:0'],
            'protocol_label' => ['nullable', 'string', 'max:255'],
        ]);

        $doctorId = $data['doctor_id'] ?? null;
        if ($doctorId) {
            $doctorClinicId = Doctor::query()
                ->whereKey($doctorId)
                ->value('vet_registeration_id');

            if ((int) $doctorClinicId !== (int) $data['clinic_id']) {
                throw ValidationException::withMessages([
                    'doctor_id' => ['The selected doctor does not belong to the selected clinic.'],
                ]);
            }
        }

        $service = VetAtHomeService::updateOrCreate(
            [
                'clinic_id' => $data['clinic_id'],
                'doctor_id' => $doctorId,
            ],
            [
                'is_enabled' => (bool) ($data['is_enabled'] ?? false),
                'service_hours' => $data['service_hours'] ?? null,
                'response_time' => $data['response_time'] ?? null,
                'base_payout' => $data['base_payout'] ?? null,
                'protocol_label' => $data['protocol_label'] ?? 'Doorstep Protocol',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Vet at home service saved successfully.',
            'data' => $service,
        ], $service->wasRecentlyCreated ? 201 : 200);
    }
}
