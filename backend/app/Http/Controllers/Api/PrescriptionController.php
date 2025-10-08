<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

        $validator = Validator::make(array_merge($data, ['image' => $request->file('image')]), [
            'doctor_id'    => 'required|integer|min:1',
            'user_id'      => 'required|integer|min:1',
            'content_html' => 'required|string',
            'image'        => 'sometimes|file|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Handle file upload (optional)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('prescriptions', 'public');
            $data['image_path'] = $path;
        }

        $prescription = Prescription::create($data);

        return response()->json([
            'message' => 'Prescription created',
            'data'    => array_merge($prescription->toArray(), [
                'image_url' => ($prescription->image_path ?? null)
                    ? Storage::disk('public')->url($prescription->image_path)
                    : null,
            ]),
        ], 201);
    }
}
