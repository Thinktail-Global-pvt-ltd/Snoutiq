<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Pet;
use Carbon\Carbon;

class PrescriptionController extends Controller
{
    // GET /api/prescriptions?user_id=&doctor_id=&pet_id=
    public function index(Request $request)
    {
        $query = Prescription::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->query('doctor_id'));
        }
        if ($request->filled('pet_id') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $query->where('pet_id', (int) $request->query('pet_id'));
        }

        $prescriptions = $query->paginate(20);
        $prescriptions->getCollection()->transform(function ($prescription) {
            $prescription->image_url = $this->buildPrescriptionUrl($prescription->image_path);

            // Enrich medications_json with days_remaining based on created_at + duration
            $createdAt = $prescription->created_at ? Carbon::parse($prescription->created_at) : null;
            $meds = $prescription->medications_json ?? [];
            if (is_string($meds)) {
                $decoded = json_decode($meds, true);
                $meds = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            if (is_array($meds) && $createdAt) {
                $meds = array_map(function ($med) use ($createdAt) {
                    if (!is_array($med)) {
                        return $med;
                    }
                    $durationRaw = $med['duration'] ?? '';
                    // extract leading number for duration (e.g., "7 days" => 7)
                    preg_match('/(\\d+(?:\\.\\d+)?)/', (string) $durationRaw, $m);
                    $durationDays = isset($m[1]) ? (float) $m[1] : null;
                    if ($durationDays !== null) {
                        $elapsed = $createdAt->diffInDays(Carbon::now());
                        $remaining = max((int) ceil($durationDays - $elapsed), 0);
                        $med['days_remaining'] = $remaining;
                    }
                    return $med;
                }, $meds);
                $prescription->medications_json = $meds;
            }

            // Normalize treatment_plan phrasing
            if (!empty($prescription->treatment_plan)) {
                $tp = $prescription->treatment_plan;
                $tp = preg_replace('/(Dosage:\\s*\\d+(?:\\.\\d+)?)(?!\\s*times)/i', '$1 times', $tp);
                $tp = preg_replace('/(Duration:\\s*\\d+(?:\\.\\d+)?)(?!\\s*days)/i', '$1 days', $tp);
                $prescription->treatment_plan = $tp;
            }

            return $prescription;
        });
        return response()->json($prescriptions);
    }

    // GET /api/prescriptions/{id}
    public function show($id)
    {
        $prescription = Prescription::find($id);
        if (!$prescription) {
            return response()->json(['message' => 'Prescription not found'], 404);
        }
        return response()->json(array_merge($prescription->toArray(), [
            'image_url' => $this->buildPrescriptionUrl($prescription->image_path),
        ]));
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

        $prescriptions = $query->paginate(20);
        $prescriptions->getCollection()->transform(function ($prescription) {
            $prescription->image_url = $this->buildPrescriptionUrl($prescription->image_path);
            return $prescription;
        });

        return response()->json($prescriptions);
    }

    // GET /api/users/medical-summary?user_id=
    public function userData(Request $request)
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::find($payload['user_id']);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $prescriptions = Prescription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'content_html',
                'image_path',
                'next_medicine_day',
                'next_visit_day',
                'created_at',
            ]);

        $pets = Pet::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $primaryPet = $pets->first();

        $prescriptions = $prescriptions->map(function ($prescription) {
            $prescription->image_url = $this->buildPrescriptionUrl($prescription->image_path);
            return $prescription;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'summary' => $user->summary,
                    'pet_doc' => $user->pet_doc
                        ?? $user->pet_doc1
                        ?? $user->pet_doc2
                        ?? data_get($primaryPet, 'pet_doc1')
                        ?? data_get($primaryPet, 'pet_doc2'),
                    'pet_doc1' => $user->pet_doc1 ?? data_get($primaryPet, 'pet_doc1'),
                    'pet_doc2' => $user->pet_doc2 ?? data_get($primaryPet, 'pet_doc2'),
                    'pet_name' => $user->pet_name ?? data_get($primaryPet, 'name'),
                    'pet_gender' => $user->pet_gender ?? data_get($primaryPet, 'pet_gender'),
                    'pet_age' => $user->pet_age ?? data_get($primaryPet, 'pet_age'),
                    'breed' => $user->breed ?? data_get($primaryPet, 'breed'),
                ],
                'pets' => $pets,
                'prescriptions' => $prescriptions,
            ],
        ]);
    }

    // GET /api/users/pets-prescriptions?user_id=
    public function userPetsAndPrescriptions(Request $request)
    {
        $payload = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'pet_id'  => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = $payload['user_id'] ?? null;
        $petId = $payload['pet_id'] ?? null;

        if (!$userId && !$petId) {
            return response()->json([
                'success' => false,
                'message' => 'Provide either user_id or pet_id',
            ], 422);
        }

        // Resolve user via pet when only pet_id is provided.
        if (!$userId && $petId) {
            $petForUserLookup = Pet::find($petId);
            if (!$petForUserLookup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pet not found',
                ], 404);
            }
            $userId = $petForUserLookup->user_id;
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $petColumns = [
            'id',
            'user_id',
            'name',
            'pet_gender',
            'breed',
        ];

        // Include optional columns only if they exist to avoid runtime errors on older schemas.
        if (Schema::hasColumn('pets', 'video_calling_upload_file')) {
            $petColumns[] = 'video_calling_upload_file';
        }
        if (Schema::hasColumn('pets', 'reported_symptom')) {
            $petColumns[] = 'reported_symptom';
        }
        if (Schema::hasColumn('pets', 'pet_dob')) {
            $petColumns[] = 'pet_dob';
        }
        if (Schema::hasColumn('pets', 'dog_disease_payload')) {
            $petColumns[] = 'dog_disease_payload';
        }

        $petsQuery = Pet::query()
            ->where('user_id', $user->id);

        if ($petId) {
            $petsQuery->where('id', $petId);
        }

        $pets = $petsQuery
            ->orderByDesc('id')
            ->get($petColumns)
            ->map(function ($pet) {
                // Surface vaccination info from dog_disease_payload if present.
                $payload = $pet->dog_disease_payload ?? null;
                if (is_string($payload)) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $payload = $decoded;
                    }
                }
                $pet->vaccination = data_get($payload, 'vaccination');
                return $pet;
            });

        if ($petId && $pets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found for this user',
            ], 404);
        }

        // If both user_id and pet_id were provided, ensure ownership match.
        if ($petId && $userId) {
            $petOwnerMismatch = $pets->first()?->user_id !== $user->id;
            if ($petOwnerMismatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pet does not belong to this user',
                ], 404);
            }
        }

        $prescriptions = Prescription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->when(
                $petId && Schema::hasColumn('prescriptions', 'pet_id'),
                fn ($query) => $query->where('pet_id', $petId)
            )
            ->get([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'visit_category',
                'case_severity',
                'visit_notes',
                'content_html',
                'image_path',
                'next_medicine_day',
                'next_visit_day',
                'created_at',
            ])
            ->map(function ($prescription) {
                $prescription->image_url = $this->buildPrescriptionUrl($prescription->image_path);
                return $prescription;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'pets' => $pets,
                'prescriptions' => $prescriptions,
            ],
        ]);
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
            'visit_category'    => 'nullable|string|max:255',
            'case_severity'     => 'nullable|string|max:255',
            'visit_notes'       => 'nullable|string',
            'weight'            => 'nullable|numeric',
            'heart_rate'        => 'nullable|numeric',
            'exam_notes'        => 'nullable|string',
            'diagnosis'         => 'nullable|string|max:255',
            'diagnosis_status'  => 'nullable|string|max:255',
            'treatment_plan'    => 'nullable|string',
            'home_care'         => 'nullable|string',
            'follow_up_date'    => 'nullable|date',
            'follow_up_type'    => 'nullable|string|max:255',
            'follow_up_notes'   => 'nullable|string',
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

    private function buildPrescriptionUrl(?string $imagePath): ?string
    {
        $imagePath = trim((string) $imagePath);
        if ($imagePath === '' || $imagePath === '0') {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        $imagePath = ltrim($imagePath, '/');
        $diskUrl = Storage::disk('public')->url($imagePath);
        $path = parse_url($diskUrl, PHP_URL_PATH) ?? $diskUrl;
        $path = '/' . ltrim($path, '/');
        $prefix = trim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
        if ($prefix && $prefix !== '') {
            $path = '/' . trim($prefix, '/') . $path;
        }

        return url($path);
    }
}
