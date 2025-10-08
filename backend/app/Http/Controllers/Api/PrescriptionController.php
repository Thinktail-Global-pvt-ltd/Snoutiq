<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    // GET /api/prescriptions?user_id=&doctor_id=
    public function index(Request $request)
    {
        $query = Prescription::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->query('doctor_id'));
        }

        $prescriptions = $query->paginate(20);
        return response()->json($prescriptions);
    }

    // GET /api/prescriptions/{id}
    public function show($id)
    {
        $prescription = Prescription::find($id);
        if (!$prescription) {
            return response()->json(['message' => 'Prescription not found'], 404);
        }
        return response()->json($prescription);
    }

    // POST /api/prescriptions
    public function store(Request $request)
    {
        $data = $request->only(['doctor_id', 'user_id', 'content_html']);

        $validator = Validator::make($data, [
            'doctor_id'    => 'required|integer|min:1',
            'user_id'      => 'required|integer|min:1',
            'content_html' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $prescription = Prescription::create($data);

        return response()->json([
            'message' => 'Prescription created',
            'data'    => $prescription,
        ], 201);
    }
}

