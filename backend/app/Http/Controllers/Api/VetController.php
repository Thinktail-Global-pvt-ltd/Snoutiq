<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'v.*',
                'd.id as doctor_id',
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
            // ✅ start vet only with v.* fields
            $vet = [
                'id'         => $items[0]->id,
                'name'       => $items[0]->name,
                'email'      => $items[0]->email,
                'phone'      => $items[0]->phone,
                'created_at' => $items[0]->created_at,
                'updated_at' => $items[0]->updated_at,
                'doctors'    => []
            ];

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
