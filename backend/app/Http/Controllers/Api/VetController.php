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
    // pehle vets + doctors join karke fetch karo
    $vetsWithDoctors = DB::table('vet_registerations_temp as v')
        ->leftJoin('doctors as d', 'd.vet_registeration_id', '=', 'v.id')
        ->select(
            'v.id as vet_id',
            'v.name as vet_name',
            'v.email as vet_email',
            'v.phone as vet_phone',
            'v.created_at as vet_created_at',
            'v.updated_at as vet_updated_at',
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
    $grouped = $vetsWithDoctors->groupBy('vet_id')->map(function ($items) {
        $vet = [
            'vet_id'   => $items[0]->vet_id,
            'vet_name' => $items[0]->vet_name,
            'vet_email'=> $items[0]->vet_email,
            'vet_phone'=> $items[0]->vet_phone,
            'created_at' => $items[0]->vet_created_at,
            'updated_at' => $items[0]->vet_updated_at,
            'doctors'  => []
        ];

        foreach ($items as $row) {
            if ($row->doctor_id) { // doctor exist karta hai
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

    return response()->json($grouped);
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
