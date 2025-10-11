<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PetsController extends Controller
{
    // GET /api/users/{id}/pets
    public function byUser(string $id)
    {
        $pets = DB::table('user_pets')
            ->where('user_id', (int) $id)
            ->select('id','name','type','breed')
            ->orderBy('id','desc')
            ->get();
        return response()->json(['pets' => $pets]);
    }
}

