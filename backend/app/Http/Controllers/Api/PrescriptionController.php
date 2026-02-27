<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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

        // Cache schema checks so we don't hit information_schema repeatedly per row
        $hasFollowUpDate  = Schema::hasColumn('prescriptions', 'follow_up_date');
        $hasFollowUpType  = Schema::hasColumn('prescriptions', 'follow_up_type');
        $hasFollowUpNotes = Schema::hasColumn('prescriptions', 'follow_up_notes');
        $hasPetsTable = Schema::hasTable('pets');
        $hasPetDogDiseasePayload = $hasPetsTable && Schema::hasColumn('pets', 'dog_disease_payload');
        $hasPetIsNeutered = $hasPetsTable && Schema::hasColumn('pets', 'is_neutered');
        $hasPetVaccinated = $hasPetsTable && Schema::hasColumn('pets', 'vaccenated_yes_no');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->query('doctor_id'));
        }
        if ($request->filled('pet_id') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $query->where('pet_id', (int) $request->query('pet_id'));
        }

        if ($hasPetsTable) {
            $petSelect = ['id'];
            if ($hasPetDogDiseasePayload) {
                $petSelect[] = 'dog_disease_payload';
            }
            if ($hasPetIsNeutered) {
                $petSelect[] = 'is_neutered';
            }
            if ($hasPetVaccinated) {
                $petSelect[] = 'vaccenated_yes_no';
            }

            $query->with(['pet' => function ($petQuery) use ($petSelect) {
                $petQuery->select($petSelect);
            }]);
        }

        $prescriptions = $query->paginate(20);
        $prescriptions->getCollection()->transform(function ($prescription) use ($hasFollowUpDate, $hasFollowUpType, $hasFollowUpNotes) {
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

            // Surface follow-up details even on older schemas; always include consistent keys
            if (isset($hasFollowUpDate) && $hasFollowUpDate) {
                $prescription->follow_up_date = $prescription->follow_up_date
                    ? Carbon::parse($prescription->follow_up_date)->toDateString()
                    : null;
            } else {
                $prescription->follow_up_date = null;
            }
            if (isset($hasFollowUpType) && $hasFollowUpType) {
                $prescription->follow_up_type = $prescription->follow_up_type ?? null;
            } else {
                $prescription->follow_up_type = null;
            }
            if (isset($hasFollowUpNotes) && $hasFollowUpNotes) {
                $prescription->follow_up_notes = $prescription->follow_up_notes ?? null;
            } else {
                $prescription->follow_up_notes = null;
            }

            $pet = $prescription->pet;
            $dogDisease = null;
            $isNeutered = null;
            $vaccinated = null;
            if ($pet) {
                $payload = $pet->dog_disease_payload ?? null;
                if (is_string($payload)) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $payload = $decoded;
                    }
                }
                $dogDisease = data_get($payload, 'dog_disease')
                    ?? data_get($payload, 'suggested_disease')
                    ?? data_get($payload, 'disease')
                    ?? null;
                $isNeutered = $pet->is_neutered ?? null;
                $vaccinated = isset($pet->vaccenated_yes_no) ? (bool) $pet->vaccenated_yes_no : null;
            }

            // Backward-compatible shape for clients expecting pets.* on each prescription item.
            $prescription->pets = [
                'dog_disease' => $dogDisease,
                'is_neutered' => $isNeutered,
                'vaccenated_yes_no' => $vaccinated,
            ];

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

        return response()->json([
            'success' => true,
            'data' => $this->medicalSummaryPayload($user),
        ]);
    }

    // PUT/PATCH /api/users/medical-summary
    public function updateUserData(Request $request)
    {
        // Backward compatibility: allow nested payload like { "pets": { "pet_dob": "2026-02-10" } }.
        $nestedPetDob = data_get($request->input('pets'), 'pet_dob');
        if (! $request->has('pet_dob') && $nestedPetDob !== null && $nestedPetDob !== '') {
            $request->merge(['pet_dob' => $nestedPetDob]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'pet_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'pet_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'pet_gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'pet_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'pet_age_months' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'pet_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'pet_dob' => ['sometimes', 'nullable', 'date'],
            'breed' => ['sometimes', 'nullable', 'string', 'max:120'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'temprature' => ['sometimes', 'nullable', 'numeric'],
            'vaccenated_yes_no' => ['sometimes', 'nullable', 'boolean'],
            'vaccination_date' => ['sometimes', 'nullable', 'date'],
            'last_vaccenated_date' => ['sometimes', 'nullable', 'date'],
            'microchip_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mcd_registration_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_neutered' => ['sometimes', 'nullable', 'boolean'],
            'reported_symptom' => ['sometimes', 'nullable', 'string'],
            'suggested_disease' => ['sometimes', 'nullable', 'string', 'max:255'],
            'health_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ai_summary' => ['sometimes', 'nullable', 'string'],
            'dog_disease_payload' => ['sometimes', 'nullable'],
            'pet_card_for_ai' => ['sometimes', 'nullable', 'string'],
            'pet_doc1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'pet_doc2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'user_pet_doc1' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'user_pet_doc2' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $user = User::find((int) $validated['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $userUpdates = [];
        foreach (['name', 'summary', 'pet_name', 'pet_gender', 'pet_age', 'breed'] as $field) {
            if (array_key_exists($field, $validated) && Schema::hasColumn('users', $field)) {
                $userUpdates[$field] = $validated[$field];
            }
        }

        if ($request->hasFile('user_pet_doc1') && Schema::hasColumn('users', 'pet_doc1')) {
            $storedPath = $this->storePetDocUploadSafely($request->file('user_pet_doc1'));
            if ($storedPath !== null) {
                $userUpdates['pet_doc1'] = $storedPath;
            }
        }
        if ($request->hasFile('user_pet_doc2')) {
            $userPetDoc2 = $request->file('user_pet_doc2');
            $userPetDoc2BlobPayload = $this->extractUploadedFileBlobPayload($userPetDoc2);

            if (Schema::hasColumn('users', 'pet_doc2')) {
                $storedPath = $this->storePetDocUploadSafely($userPetDoc2);
                if ($storedPath !== null) {
                    $userUpdates['pet_doc2'] = $storedPath;
                }
            }
            if (Schema::hasColumn('users', 'pet_doc2_blob') && $userPetDoc2BlobPayload !== null) {
                $userUpdates['pet_doc2_blob'] = $userPetDoc2BlobPayload['blob'];
                if (Schema::hasColumn('users', 'pet_doc2_mime')) {
                    $userUpdates['pet_doc2_mime'] = $userPetDoc2BlobPayload['mime'];
                }
            }
        }

        if (!empty($userUpdates)) {
            if (Schema::hasColumn('users', 'updated_at')) {
                $userUpdates['updated_at'] = now();
            }
            DB::table('users')->where('id', $user->id)->update($userUpdates);
        }

        $petFieldPresent = $request->hasFile('pet_doc1') || $request->hasFile('pet_doc2');
        foreach ([
            'pet_name',
            'pet_gender',
            'pet_age',
            'pet_age_months',
            'pet_type',
            'pet_dob',
            'breed',
            'weight',
            'temprature',
            'vaccenated_yes_no',
            'vaccination_date',
            'last_vaccenated_date',
            'microchip_number',
            'mcd_registration_number',
            'is_neutered',
            'reported_symptom',
            'suggested_disease',
            'health_state',
            'ai_summary',
            'dog_disease_payload',
            'pet_card_for_ai',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $petFieldPresent = true;
                break;
            }
        }

        $pet = null;
        if (Schema::hasTable('pets')) {
            $petQuery = Pet::query()->where('user_id', $user->id);
            if (!empty($validated['pet_id'])) {
                $petQuery->where('id', (int) $validated['pet_id']);
            }
            $pet = $petQuery->orderByDesc('id')->first();
        }

        if ($petFieldPresent && !$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found for this user',
            ], 404);
        }

        if ($pet) {
            $petUpdates = [];
            if (array_key_exists('pet_name', $validated) && Schema::hasColumn('pets', 'name')) {
                $petUpdates['name'] = $validated['pet_name'];
            }
            if (array_key_exists('breed', $validated) && Schema::hasColumn('pets', 'breed')) {
                $petUpdates['breed'] = $validated['breed'];
            }
            if (array_key_exists('pet_age', $validated) && Schema::hasColumn('pets', 'pet_age')) {
                $petUpdates['pet_age'] = $validated['pet_age'];
            }
            if (array_key_exists('pet_age_months', $validated) && Schema::hasColumn('pets', 'pet_age_months')) {
                $petUpdates['pet_age_months'] = $validated['pet_age_months'];
            }
            if (array_key_exists('pet_gender', $validated)) {
                if (Schema::hasColumn('pets', 'pet_gender')) {
                    $petUpdates['pet_gender'] = $validated['pet_gender'];
                }
                if (Schema::hasColumn('pets', 'gender')) {
                    $petUpdates['gender'] = $validated['pet_gender'];
                }
            }
            if (array_key_exists('pet_type', $validated)) {
                if (Schema::hasColumn('pets', 'pet_type')) {
                    $petUpdates['pet_type'] = $validated['pet_type'];
                }
                if (Schema::hasColumn('pets', 'type')) {
                    $petUpdates['type'] = $validated['pet_type'];
                }
            }
            if (array_key_exists('pet_dob', $validated)) {
                if (Schema::hasColumn('pets', 'pet_dob')) {
                    $petUpdates['pet_dob'] = $validated['pet_dob'];
                } elseif (Schema::hasColumn('pets', 'dob')) {
                    $petUpdates['dob'] = $validated['pet_dob'];
                }
            }
            foreach ([
                'weight',
                'temprature',
                'vaccination_date',
                'last_vaccenated_date',
                'microchip_number',
                'mcd_registration_number',
                'reported_symptom',
                'suggested_disease',
                'health_state',
                'ai_summary',
                'pet_card_for_ai',
            ] as $field) {
                if (array_key_exists($field, $validated) && Schema::hasColumn('pets', $field)) {
                    $petUpdates[$field] = $validated[$field];
                }
            }
            foreach (['vaccenated_yes_no', 'is_neutered'] as $field) {
                if (array_key_exists($field, $validated) && Schema::hasColumn('pets', $field)) {
                    $petUpdates[$field] = $validated[$field] === null ? null : (bool) $validated[$field];
                }
            }
            if (array_key_exists('dog_disease_payload', $validated) && Schema::hasColumn('pets', 'dog_disease_payload')) {
                $petUpdates['dog_disease_payload'] = $this->normalizeJsonPayload($validated['dog_disease_payload']);
            }

            if ($request->hasFile('pet_doc1')) {
                $petDoc1Path = $this->storePetDocUploadSafely($request->file('pet_doc1'));
                if ($petDoc1Path !== null && Schema::hasColumn('pets', 'pet_doc1')) {
                    $petUpdates['pet_doc1'] = $petDoc1Path;
                } elseif ($petDoc1Path !== null && Schema::hasColumn('pets', 'pic_link')) {
                    $petUpdates['pic_link'] = $petDoc1Path;
                }
            }

            if ($request->hasFile('pet_doc2')) {
                $petDoc2 = $request->file('pet_doc2');
                $petDoc2BlobPayload = $this->extractUploadedFileBlobPayload($petDoc2);

                if (Schema::hasColumn('pets', 'pet_doc2')) {
                    $storedPath = $this->storePetDocUploadSafely($petDoc2);
                    if ($storedPath !== null) {
                        $petUpdates['pet_doc2'] = $storedPath;
                    }
                }
                if (Schema::hasColumn('pets', 'pet_doc2_blob') && $petDoc2BlobPayload !== null) {
                    $petUpdates['pet_doc2_blob'] = $petDoc2BlobPayload['blob'];
                    if (Schema::hasColumn('pets', 'pet_doc2_mime')) {
                        $petUpdates['pet_doc2_mime'] = $petDoc2BlobPayload['mime'];
                    }
                }
            }

            if (!empty($petUpdates)) {
                if (Schema::hasColumn('pets', 'updated_at')) {
                    $petUpdates['updated_at'] = now();
                }
                DB::table('pets')->where('id', $pet->id)->update($petUpdates);
            }
        }

        $freshUser = User::find($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Medical summary updated successfully',
            'data' => $this->medicalSummaryPayload($freshUser),
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
        $hasPetWeightColumn = Schema::hasColumn('pets', 'weight');
        if ($hasPetWeightColumn) {
            $petColumns[] = 'weight';
        }

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
            ->map(function ($pet) use ($hasPetWeightColumn) {
                // Surface vaccination info from dog_disease_payload if present.
                $payload = $pet->dog_disease_payload ?? null;
                if (is_string($payload)) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $payload = $decoded;
                    }
                }
                $pet->vaccination = data_get($payload, 'vaccination');
                if (!$hasPetWeightColumn) {
                    $pet->weight = null;
                }
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

        $prescriptionColumns = [
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
        ];
        if (Schema::hasColumn('prescriptions', 'follow_up_date')) {
            $prescriptionColumns[] = 'follow_up_date';
        }
        if (Schema::hasColumn('prescriptions', 'follow_up_type')) {
            $prescriptionColumns[] = 'follow_up_type';
        }
        if (Schema::hasColumn('prescriptions', 'follow_up_notes')) {
            $prescriptionColumns[] = 'follow_up_notes';
        }

        $prescriptions = Prescription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->when(
                $petId && Schema::hasColumn('prescriptions', 'pet_id'),
                fn ($query) => $query->where('pet_id', $petId)
            )
            ->get($prescriptionColumns)
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
            'video_appointment_id',
            'content_html',
            'next_medicine_day',
            'next_visit_day',
            'temperature',
            'temperature_unit',
        ]);

        $validator = Validator::make(array_merge($data, ['image' => $request->file('image')]), [
            'doctor_id'    => 'required|integer|min:1',
            'user_id'      => 'required|integer|min:1',
            'video_appointment_id' => 'nullable|integer|exists:video_apointment,id',
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
        $this->markVideoApointmentCompleted($prescription->video_appointment_id ?? null);

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

    private function medicalSummaryPayload(User $user): array
    {
        $prescriptions = Prescription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get(array_values(array_filter([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'content_html',
                'image_path',
                'next_medicine_day',
                'next_visit_day',
                Schema::hasColumn('prescriptions', 'follow_up_date')  ? 'follow_up_date'  : null,
                Schema::hasColumn('prescriptions', 'follow_up_type')  ? 'follow_up_type'  : null,
                Schema::hasColumn('prescriptions', 'follow_up_notes') ? 'follow_up_notes' : null,
                'created_at',
            ])));

        $pets = Pet::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Pet $pet) {
                $blobUrl = $this->petDoc2BlobUrl($pet);
                $pet->setAttribute('pet_doc2_blob_url', $blobUrl);
                $pet->setAttribute('pet_image_url', $blobUrl ?: ($pet->pet_doc1 ?? $pet->pet_doc2 ?? $pet->pic_link ?? null));
                return $pet;
            });

        $primaryPet = $pets->first();
        $userBlobUrl = $this->userPetDoc2BlobUrl($user);

        $prescriptions = $prescriptions->map(function ($prescription) {
            $prescription->image_url = $this->buildPrescriptionUrl($prescription->image_path);
            return $prescription;
        });

        return [
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
                'pet_doc2_blob_url' => $userBlobUrl ?: data_get($primaryPet, 'pet_doc2_blob_url'),
                'pet_image_url' => $userBlobUrl
                    ?: ($user->pet_doc1 ?? $user->pet_doc2 ?? data_get($primaryPet, 'pet_image_url')),
                'pet_name' => $user->pet_name ?? data_get($primaryPet, 'name'),
                'pet_gender' => $user->pet_gender ?? data_get($primaryPet, 'pet_gender'),
                'pet_age' => $user->pet_age ?? data_get($primaryPet, 'pet_age'),
                'breed' => $user->breed ?? data_get($primaryPet, 'breed'),
            ],
            'pets' => $pets,
            'prescriptions' => $prescriptions,
        ];
    }

    private function storePetDocUpload(UploadedFile $file): string
    {
        $uploadPath = public_path('uploads/pet_docs');
        File::ensureDirectoryExists($uploadPath);

        // Keep path short/safe to avoid DB column overflow with long original filenames.
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
        $ext = preg_replace('/[^a-z0-9]+/', '', $ext) ?: 'bin';
        $docName = now()->format('YmdHis') . '_' . Str::random(16) . '.' . $ext;
        $file->move($uploadPath, $docName);

        return 'backend/uploads/pet_docs/' . $docName;
    }

    private function storePetDocUploadSafely(?UploadedFile $file): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }

        try {
            return $this->storePetDocUpload($file);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function extractUploadedFileBlobPayload(?UploadedFile $file): ?array
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }

        try {
            return [
                'blob' => $file->get(),
                'mime' => $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream'),
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function normalizeJsonPayload($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            return json_decode(json_encode($value), true);
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function userPetDoc2BlobUrl(?User $user): ?string
    {
        if (! $user
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'pet_doc2_blob')
            || ! Schema::hasColumn('users', 'pet_doc2_mime')) {
            return null;
        }

        $blob = $user->getRawOriginal('pet_doc2_blob');
        if ($blob === null || $blob === '') {
            return null;
        }

        return route('api.users.pet-doc2-blob', ['user' => $user->id]);
    }

    private function petDoc2BlobUrl(?Pet $pet): ?string
    {
        if (! $pet
            || ! Schema::hasTable('pets')
            || ! Schema::hasColumn('pets', 'pet_doc2_blob')
            || ! Schema::hasColumn('pets', 'pet_doc2_mime')) {
            return null;
        }

        $blob = $pet->getRawOriginal('pet_doc2_blob');
        if ($blob === null || $blob === '') {
            return null;
        }

        return route('api.pets.pet-doc2-blob', ['pet' => $pet->id]);
    }

    private function markVideoApointmentCompleted($videoApointmentId): void
    {
        $videoApointmentId = (int) $videoApointmentId;
        if ($videoApointmentId <= 0) {
            return;
        }
        if (!Schema::hasTable('video_apointment')) {
            return;
        }

        $updates = [];
        if (Schema::hasColumn('video_apointment', 'is_completed')) {
            $updates['is_completed'] = 1;
        }
        if (Schema::hasColumn('video_apointment', 'is_complete')) {
            $updates['is_complete'] = 1;
        }
        if (empty($updates)) {
            return;
        }

        DB::table('video_apointment')
            ->where('id', $videoApointmentId)
            ->update($updates);
    }
}
