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
            'toggle_availability'=> 'sometimes|boolean',
        ]);

        $vetId = (int) $data['vet_id'];

        $doctors = Doctor::where('vet_registeration_id', $vetId)->get();
        if ($doctors->isEmpty()) {
            return response()->json([
                'message' => 'No doctor found for given vet_registerations_temp.id',
            ], 404);
        }

        $updates = [];
        if (array_key_exists('toggle_availability', $data)) {
            $updates['toggle_availability'] = (bool) $data['toggle_availability'];
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
}
