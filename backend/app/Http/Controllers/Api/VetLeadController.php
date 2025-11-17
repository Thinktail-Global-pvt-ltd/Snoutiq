<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetLead;
use Illuminate\Http\Request;

class VetLeadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'vet_name' => ['required', 'string', 'max:255'],
            'vet_phone' => ['required', 'string', 'max:32'],
        ]);

        $lead = VetLead::create($data);

        return response()->json([
            'success' => true,
            'data' => $lead,
        ], 201);
    }
}
