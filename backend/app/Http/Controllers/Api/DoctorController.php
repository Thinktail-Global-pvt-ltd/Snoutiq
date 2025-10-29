<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    
    // GET /api/doctors
    public function index(Request $request)
    {
        $query = DB::table('doctors')
            ->select('id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'doctor_license', 'vet_registeration_id')
            ->orderBy('doctor_name');

        $vetId = $request->input('vet_id');
        $clinicId = $request->input('clinic_id');

        if ($vetId !== null && $vetId !== '') {
            $query->where('vet_registeration_id', (int) $vetId);
        } elseif ($clinicId !== null && $clinicId !== '') {
            $query->where('vet_registeration_id', (int) $clinicId);
        }

        $doctors = $query->orderBy('id')->get();

        return response()->json([
            'status'  => 'success',
            'count'   => $doctors->count(),
            'doctors' => $doctors,
        ]);
    }

    // GET /api/doctors/{id}
    public function show(int $id)
    {
        $doctor = DB::table('doctors')
            ->select('id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'doctor_license', 'vet_registeration_id')
            ->where('id', $id)
            ->first();

        // If not found by doctor primary key, treat the id as vet_registeration_id (legacy behaviour)
        if (!$doctor) {
            $doctor = DB::table('doctors')
                ->select('id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'doctor_license', 'vet_registeration_id')
                ->where('vet_registeration_id', $id)
                ->orderBy('id')
                ->first();
        }

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Doctor not found',
            ], 404);
        }

        $clinic = DB::table('vet_registerations_temp')
            ->select('id', 'name', 'clinic_profile', 'email', 'mobile')
            ->where('id', $doctor->vet_registeration_id)
            ->first();

        return response()->json([
            'status' => 'success',
            'doctor' => $doctor,
            'clinic' => $clinic,
        ]);
    }
}
