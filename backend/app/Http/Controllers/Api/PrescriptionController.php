<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

    // GET /api/doctors/{doctorId}/prescriptions?user_id=
    public function forDoctor(Request $request, int $doctorId)
    {
        $query = Prescription::query()
            ->where('doctor_id', $doctorId)
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        return response()->json($query->paginate(20));
    }

    // POST /api/prescriptions
    public function store(Request $request)
    {
        $data = $request->only([
            'doctor_id',
            'user_id',
            'content_html',
            'next_medicine_day',
            'next_visit_day',
            'temperature',
            'temperature_unit',
        ]);

        $validator = Validator::make(array_merge($data, ['image' => $request->file('image')]), [
            'doctor_id'    => 'required|integer|min:1',
            'user_id'      => 'required|integer|min:1',
            'content_html' => 'required|string',
            'next_medicine_day' => 'nullable|date',
            'next_visit_day'    => 'nullable|date',
            'temperature'       => 'nullable|numeric',
            'temperature_unit'  => 'nullable|string|in:C,F,c,f',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('temperature_unit', $data) && $data['temperature_unit'] !== null) {
            $data['temperature_unit'] = strtoupper($data['temperature_unit']);
        }
        if (array_key_exists('temperature', $data) && $data['temperature'] !== null && empty($data['temperature_unit'])) {
            $data['temperature_unit'] = 'C';
        }

        // Handle file upload (optional) -> save directly under public/prescriptions
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext  = strtolower($file->getClientOriginalExtension() ?: 'png');
            $name = Str::random(40) . '.' . $ext;
            $dest = public_path('prescriptions');
            File::ensureDirectoryExists($dest);
            $file->move($dest, $name);
            $data['image_path'] = 'prescriptions/' . $name; // web-accessible path
        }

        $prescription = Prescription::create($data);

        // Build URL with optional backend prefix
        $appUrl = rtrim(config('app.url') ?? env('APP_URL', ''), '/');
        $prefix = trim((config('app.path_prefix') ?? env('APP_PATH_PREFIX', '')), '/');
        $base   = $prefix ? ($appUrl . '/' . $prefix) : $appUrl;

        $imageUrl = null;
        if (!empty($prescription->image_path)) {
            // If saved under public/, serve directly; else fall back to Storage public disk
            if (str_starts_with($prescription->image_path, 'prescriptions/')) {
                $imageUrl = rtrim($base, '/') . '/' . ltrim($prescription->image_path, '/');
            } else {
                $imageUrl = Storage::disk('public')->url($prescription->image_path);
            }
        }

        return response()->json([
            'message' => 'Prescription created',
            'data'    => array_merge($prescription->toArray(), [
                'image_url' => $imageUrl,
            ]),
        ], 201);
    }
}
