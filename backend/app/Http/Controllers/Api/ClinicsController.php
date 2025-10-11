<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ClinicsController extends Controller
{
    // GET /api/clinics
    public function index()
    {
        // Ensure "name" is always populated for frontend sorting/display
        $rows = DB::table('vet_registerations_temp')
            ->select(
                'id',
                DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'),
                'slug',
                'address'
            )
            ->orderByRaw('COALESCE(name, slug) ASC')
            ->limit(500)
            ->get();

        return response()->json(['clinics' => $rows]);
    }
    // GET /api/clinics/{id}/doctors
    public function doctors(string $id)
    {
        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', (int)$id)
            ->first();
        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
        $doctors = DB::table('doctors')
            ->where('vet_registeration_id', (int)$id)
            ->select('id', 'doctor_name as name', 'doctor_email as email', 'doctor_mobile as phone')
            ->orderBy('doctor_name')
            ->get();
        return response()->json(['clinic' => $clinic, 'doctors' => $doctors]);
    }

    // GET /api/clinics/{id}/availability
    public function availability(string $id)
    {
        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', (int)$id)
            ->first();
        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
        $doctors = DB::table('doctors')
            ->where('vet_registeration_id', (int)$id)
            ->select('id', 'doctor_name as name')
            ->get();
        $doctorIds = $doctors->pluck('id')->all();
        $availability = [];
        if ($doctorIds) {
            $availability = DB::table('doctor_availability')
                ->whereIn('doctor_id', $doctorIds)
                ->orderBy('doctor_id')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        }
        return response()->json([
            'clinic' => $clinic,
            'doctors' => $doctors,
            'availability' => $availability,
        ]);
    }
}
