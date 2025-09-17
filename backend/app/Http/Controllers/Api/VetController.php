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
    $vets = DB::table('vet_registerations_temp as v')
        ->leftJoin('doctors as d', 'd.vet_registeration_id', '=', 'v.id')
        ->select(
            'v.*',                  // saare vets columns
            'd.id as doctor_id',    // doctor id ka alias
            'd.doctor_name',
            'd.doctor_email',
            'd.doctor_mobile',
            'd.doctor_license',
            'd.doctor_image',
            'd.created_at as doctor_created_at',
            'd.updated_at as doctor_updated_at'
        )
        ->get();

    return response()->json($vets);
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
