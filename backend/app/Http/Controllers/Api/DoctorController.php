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
}

