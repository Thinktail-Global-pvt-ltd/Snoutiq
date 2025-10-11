<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    
    // GET /api/doctors
    public function index()
    {
        $doctors = DB::table('doctors')
            ->select('id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'doctor_license', 'vet_registeration_id')
            ->orderBy('doctor_name')
            ->get();

        return response()->json([
            'status'  => 'success',
            'count'   => $doctors->count(),
            'doctors' => $doctors,
        ]);
    }
}

