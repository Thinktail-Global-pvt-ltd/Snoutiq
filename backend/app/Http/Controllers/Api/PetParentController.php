<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetParentController extends Controller
{
    // 🔹 View single Pet Parent (User) by ID
    public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Pet Parent not found'], 404);
        }

        return response()->json($user);
    }

    // 🔹 Delete Pet Parent (User) by ID
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Pet Parent not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Pet Parent deleted successfully']);
    }
}
