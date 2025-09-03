<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactRequest;

class ContactRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'best_time_to_connect' => 'nullable|string|max:255',
        ]);

        $contact = ContactRequest::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact request saved successfully',
            'data' => $contact,
        ], 201);
    }
}
