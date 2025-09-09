<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Fetch all users
    public function getUsers(Request $request)
    {
        if ($request->email !== 'adminsnoutiq@gmail.com') {
            return response()->json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $users = User::all();
        return response()->json(['status' => 'success', 'data' => $users]);
    }

    // Fetch all vets
    public function getVets(Request $request)
    {
        if ($request->email !== 'adminsnoutiq@gmail.com') {
            return response()->json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $vets = DB::table('vet_registerations_temp')->get();
        return response()->json(['status' => 'success', 'data' => $vets]);
    }
}
