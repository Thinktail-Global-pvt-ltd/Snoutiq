<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorStatusController extends Controller
{
    // POST /api/doctor/update-status
    // Body: { vet_id: int, toggle_availability?: bool }
    public function updateByVet(Request $request)
    {
        $data = $request->validate([
            'vet_id'             => 'required|integer|min:1',
            'doctor_status'      => 'sometimes|string|in:available,busy,on_leave,absent,in_surgery',
        ]);

        $vetId = (int) $data['vet_id'];

        $doctors = Doctor::where('vet_registeration_id', $vetId)->get();
        if ($doctors->isEmpty()) {
            return response()->json([
                'message' => 'No doctor found for given vet_registerations_temp.id',
            ], 404);
        }

        $updates = [];
        if (array_key_exists('doctor_status', $data)) {
            $updates['doctor_status'] = $data['doctor_status'];
        }

        $count = 0;
        foreach ($doctors as $doc) {
            $doc->fill($updates);
            $doc->save();
            $count++;
        }

        return response()->json([
            'message'         => 'Doctor records updated',
            'updated_records' => $count,
            'updates'         => $updates,
        ]);
    }

    // PATCH /api/doctors/{doctor}/status
    public function updateDoctor(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'doctor_status'      => 'sometimes|string|in:available,busy,on_leave,absent,in_surgery',
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'No fields provided'], 422);
        }

        if (array_key_exists('doctor_status', $data)) {
            $doctor->doctor_status = $data['doctor_status'];
        }

        $doctor->save();

        return response()->json([
            'message' => 'Doctor status updated',
            'doctor'  => [
                'id' => $doctor->id,
                'doctor_status' => $doctor->doctor_status,
            ],
        ]);
    }
}
