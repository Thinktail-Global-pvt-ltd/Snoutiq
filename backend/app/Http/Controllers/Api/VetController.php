<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VetController extends Controller
{
    // 🔹 All vets list
    // public function index()
    // {
    //     $vets = DB::table('vet_registerations_temp')->get();
    //     return response()->json($vets);
    // }


public function index()
{
    try {
        $vetsWithDoctors = DB::table('vet_registerations_temp as v')
            ->leftJoin('doctors as d', 'd.vet_registeration_id', '=', 'v.id')
            ->select(
                'v.*',                  // ✅ saare vet columns
                'd.id as doctor_id',    // ✅ doctor id alias
                'd.doctor_name',
                'd.doctor_email',
                'd.doctor_mobile',
                'd.doctor_license',
                'd.doctor_image',
                'd.created_at as doctor_created_at',
                'd.updated_at as doctor_updated_at'
            )
            ->get();

        // group vets and doctors
        $grouped = $vetsWithDoctors->groupBy('id')->map(function ($items) {
            $vet = (array) $items[0]; // convert object to array
            $vet['doctors'] = [];

            foreach ($items as $row) {
                if ($row->doctor_id) {
                    $vet['doctors'][] = [
                        'doctor_id'      => $row->doctor_id,
                        'doctor_name'    => $row->doctor_name,
                        'doctor_email'   => $row->doctor_email,
                        'doctor_mobile'  => $row->doctor_mobile,
                        'doctor_license' => $row->doctor_license,
                        'doctor_image'   => $row->doctor_image,
                        'created_at'     => $row->doctor_created_at,
                        'updated_at'     => $row->doctor_updated_at,
                    ];
                }
            }

            return $vet;
        })->values();

        return response()->json([
            'status'  => 'success',
            'message' => 'Vets fetched successfully',
            'data'    => $grouped
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while fetching vets',
            'error'   => $e->getMessage()
        ], 500);
    }
}





    // 🔹 View single vet by ID
    public function show($id)
    {
        $vet = DB::table('vet_registerations_temp')->where('id', $id)->first();

        if (!$vet) {
            return response()->json(['message' => 'Vet not found'], 404);
        }

        return response()->json($vet);
    }

    /**
     * Fetch a clinic directly from its referral code (e.g., SN-PA0000T).
     */
    public function showByReferral(Request $request, string $code)
    {
        $referral = strtoupper(trim($code));

        if (! preg_match('/^SN-([A-Z]{2})([0-9A-Z]{5})$/', $referral, $matches)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid referral format. Expected SN-XX00000.',
            ], 422);
        }

        $idBase36 = $matches[2];
        $clinicId = (int) base_convert($idBase36, 36, 10);

        if ($clinicId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral could not be resolved to a clinic.',
            ], 404);
        }

        $clinic = DB::table('vet_registerations_temp')->where('id', $clinicId)->first();

        if (! $clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found for the given referral.',
            ], 404);
        }

        $slugFragment = Str::upper(Str::substr(Str::slug($clinic->slug ?? $clinic->name ?? ''), 0, 2));
        if ($slugFragment === '') {
            $slugFragment = 'CL';
        }

        if ($slugFragment !== $matches[1]) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral prefix does not match clinic slug.',
            ], 422);
        }

        $lastVetUpdated = false;
        $userId = $request->query('user_id');
        if (! empty($userId) && ctype_digit((string) $userId)) {
            $user = User::find((int) $userId);
            if ($user) {
                $user->last_vet_id = $clinic->id;
                $user->save();
                $lastVetUpdated = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'referral' => $referral,
            'data' => $clinic,
            'last_vet_id_updated' => $lastVetUpdated,
        ]);
    }

    // 🔹 Delete vet by ID
    public function destroy($id)
    {
        $deleted = DB::table('vet_registerations_temp')->where('id', $id)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Vet deleted successfully']);
        }

        return response()->json(['message' => 'Vet not found'], 404);
    }
}
