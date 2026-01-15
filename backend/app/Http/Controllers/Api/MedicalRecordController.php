<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class MedicalRecordController extends Controller
{
    public function index(Request $request, int $userId)
    {
        return $this->recordsForUser($this->resolveUser((string) $userId), $request->query('clinic_id'));
    }

    public function indexBySlug(Request $request, string $slug)
    {
        return $this->recordsForUser($this->resolveUser($slug), $request->query('clinic_id'));
    }

    public function userRecords(int $userId)
    {
        return $this->recordsForUser($this->resolveUser((string) $userId), null, true);
    }

    public function update(Request $request, MedicalRecord $record)
    {
        $validated = $request->validate([
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'visit_category' => ['nullable', 'string', 'max:255'],
            'case_severity' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'numeric'],
            'exam_notes' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'diagnosis_status' => ['nullable', 'string', 'max:255'],
            'disease_name' => ['nullable', 'string', 'max:255'],
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'medicines' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
            'pet_id' => ['nullable', 'integer'],
            'record_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $recordFilePath = null;
        $recordFileMime = null;
        $recordFileExt = null;

        $clinicId = (int) $validated['clinic_id'];
        if ((int) $record->vet_registeration_id !== $clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Record not linked to this clinic',
            ], 422);
        }

        $doctorId = $validated['doctor_id'] ?? null;
        $petId = $validated['pet_id'] ?? null;
        if ($doctorId) {
            $doctor = Doctor::query()->select('id', 'vet_registeration_id')->find($doctorId);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor not found',
                ], 404);
            }
            if ((int) $doctor->vet_registeration_id !== $clinicId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor is not part of this clinic',
                ], 422);
            }
        }

        if ($petId !== null) {
            $petId = $this->ensurePetBelongsToUser($record->user_id, $petId);
            if ($petId === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pet not found for this patient',
                ], 422);
            }
        }

        if ($request->hasFile('record_file')) {
            $file = $request->file('record_file');
            $storedPath = $file->store('medical-records', 'public');
            $record->file_path = $storedPath;
            $record->file_name = $file->getClientOriginalName();
            $record->mime_type = $file->getClientMimeType();
            $recordFilePath = $storedPath;
            $recordFileMime = $record->mime_type;
            $recordFileExt = strtolower($file->getClientOriginalExtension() ?: pathinfo($storedPath, PATHINFO_EXTENSION));
        }

        $record->notes = $validated['notes'] ?? $record->notes;
        $record->doctor_id = $doctorId ?? $record->doctor_id;
        $record->save();

        $prescription = Prescription::firstOrNew(['medical_record_id' => $record->id]);
        $medsJson = $this->maybeStructureMedicines($validated['medicines'] ?? null, $validated['diagnosis'] ?? null, $validated['notes'] ?? null);
        $diseaseName = $validated['disease_name'] ?? $validated['diagnosis'] ?? null;
        $isChronic = ($validated['diagnosis_status'] ?? '') === 'chronic';

        $prescription->fill([
            'medical_record_id' => $record->id,
            'user_id' => $record->user_id,
            'doctor_id' => $record->doctor_id ?? 0,
            'visit_category' => $validated['visit_category'] ?? $prescription->visit_category,
            'case_severity' => $validated['case_severity'] ?? $prescription->case_severity,
            'visit_notes' => $validated['notes'] ?? $prescription->visit_notes,
            'content_html' => $validated['notes'] ?? $prescription->content_html ?? 'Updated medical record',
            'temperature' => $validated['temperature'] ?? $prescription->temperature,
            'weight' => $validated['weight'] ?? $prescription->weight,
            'heart_rate' => $validated['heart_rate'] ?? $prescription->heart_rate,
            'exam_notes' => $validated['exam_notes'] ?? $prescription->exam_notes,
            'diagnosis' => $validated['diagnosis'] ?? $prescription->diagnosis,
            'diagnosis_status' => $validated['diagnosis_status'] ?? $prescription->diagnosis_status,
            'is_chronic' => $isChronic,
            'disease_name' => $diseaseName ?? $prescription->disease_name,
            'treatment_plan' => $validated['treatment_plan'] ?? $prescription->treatment_plan,
            'home_care' => $validated['home_care'] ?? $prescription->home_care,
            'follow_up_date' => $validated['follow_up_date'] ?? $prescription->follow_up_date,
            'follow_up_type' => $validated['follow_up_type'] ?? $prescription->follow_up_type,
            'follow_up_notes' => $validated['follow_up_notes'] ?? $prescription->follow_up_notes,
            'pet_id' => $petId ?? $prescription->pet_id,
            'medications_json' => $medsJson ?? $prescription->medications_json,
        ]);
        if ($recordFilePath) {
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'svg'];
            $isImageUpload = str_starts_with((string) $recordFileMime, 'image/')
                || ($recordFileExt && in_array($recordFileExt, $imageExtensions, true));
            $prescription->image_path = $isImageUpload ? $recordFilePath : null;
        }
        $prescription->save();

        if ($petId) {
            $this->updatePetHealthState($record->user_id, $petId, $isChronic, $diseaseName);
        }

        return response()->json([
            'success' => true,
            'record' => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'doctor_id' => $record->doctor_id,
                'clinic_id' => $record->vet_registeration_id,
                'file_name' => $record->file_name,
                'mime_type' => $record->mime_type,
                'notes' => $record->notes,
                'uploaded_at' => optional($record->created_at)->toIso8601String(),
                'url' => $this->buildRecordUrl($record->file_path),
            ],
            'prescription' => $prescription,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'visit_category' => ['nullable', 'string', 'max:255'],
            'case_severity' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'numeric'],
            'exam_notes' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'diagnosis_status' => ['nullable', 'string', 'max:255'],
            'disease_name' => ['nullable', 'string', 'max:255'],
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'medicines' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
            'pet_id' => ['nullable', 'integer'],
            'record_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $user = User::query()->select('id', 'last_vet_id')->find($validated['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        $clinicId = (int) $validated['clinic_id'];
        if ((int) $user->last_vet_id !== $clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Patient is not linked to this clinic',
            ], 422);
        }

        $doctorId = $validated['doctor_id'] ?? null;
        $petId = $validated['pet_id'] ?? null;
        if ($doctorId) {
            $doctor = Doctor::query()->select('id', 'vet_registeration_id')->find($doctorId);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor not found',
                ], 404);
            }

            if ((int) $doctor->vet_registeration_id !== $clinicId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor is not part of this clinic',
                ], 422);
            }
        }

        if ($petId !== null) {
            $petId = $this->ensurePetBelongsToUser($user->id, $petId);
            if ($petId === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pet not found for this patient',
                ], 422);
            }
        }

        $file = $request->file('record_file');
        $storedPath = $file->store('medical-records', 'public');
        $fileMimeType = $file->getClientMimeType();
        $fileExtension = strtolower($file->getClientOriginalExtension() ?: pathinfo($storedPath, PATHINFO_EXTENSION));

        $record = MedicalRecord::create([
            'user_id' => $user->id,
            'doctor_id' => $doctorId,
            'vet_registeration_id' => $clinicId,
            'file_path' => $storedPath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Persist consultation detail into prescriptions for downstream use
        $prescriptionPayload = [
            'medical_record_id' => $record->id,
            'user_id' => $user->id,
            'doctor_id' => $doctorId ?? 0,
            'content_html' => $validated['notes'] ?? 'Uploaded medical record',
            'visit_category' => $validated['visit_category'] ?? null,
            'case_severity' => $validated['case_severity'] ?? null,
            'visit_notes' => $validated['notes'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'heart_rate' => $validated['heart_rate'] ?? null,
            'exam_notes' => $validated['exam_notes'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'diagnosis_status' => $validated['diagnosis_status'] ?? null,
            'is_chronic' => ($validated['diagnosis_status'] ?? '') === 'chronic',
            'disease_name' => $validated['disease_name'] ?? $validated['diagnosis'] ?? null,
            'treatment_plan' => $validated['treatment_plan'] ?? null,
            'home_care' => $validated['home_care'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'follow_up_type' => $validated['follow_up_type'] ?? null,
            'follow_up_notes' => $validated['follow_up_notes'] ?? null,
            'pet_id' => $petId,
            'medications_json' => $this->maybeStructureMedicines($validated['medicines'] ?? null, $validated['diagnosis'] ?? null, $validated['notes'] ?? null),
        ];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'svg'];
        $isImageUpload = str_starts_with((string) $fileMimeType, 'image/')
            || ($fileExtension && in_array($fileExtension, $imageExtensions, true));
        $prescriptionPayload['image_path'] = $isImageUpload ? $storedPath : null;
        $prescription = Prescription::create($prescriptionPayload);
        if (!$prescription || !$prescription->exists) {
            return response()->json([
                'success' => false,
                'error' => 'Prescription could not be created',
            ], 500);
        }

        if ($petId) {
            $this->updatePetHealthState($user->id, $petId, ($validated['diagnosis_status'] ?? '') === 'chronic', $validated['disease_name'] ?? $validated['diagnosis'] ?? null);
        }

        return response()->json([
            'success' => true,
            'record' => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'doctor_id' => $record->doctor_id,
                'clinic_id' => $record->vet_registeration_id,
                'file_name' => $record->file_name,
                'mime_type' => $record->mime_type,
                'notes' => $record->notes,
                'uploaded_at' => optional($record->created_at)->toIso8601String(),
                'url' => $this->buildRecordUrl($record->file_path),
            ],
            'prescription' => $prescription,
        ], 201);
    }

    protected function recordsForUser(?User $user, $clinicIdInput, bool $skipClinicValidation = false)
    {
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $clinicId = $clinicIdInput ? (int) $clinicIdInput : null;
        if (!$skipClinicValidation && $clinicId && (int) $user->last_vet_id !== $clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Patient is not associated with this clinic',
            ], 403);
        }

        $records = MedicalRecord::query()
            ->where('user_id', $user->id)
            ->when($clinicId, fn ($query) => $query->where('vet_registeration_id', $clinicId))
            ->orderByDesc('created_at')
            ->get();

        $prescriptions = Prescription::query()
            ->whereIn('medical_record_id', $records->pluck('id')->filter()->all())
            ->get()
            ->keyBy('medical_record_id');

        $latestUserPrescription = Prescription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $recordsMapped = $records->map(function (MedicalRecord $record) use ($prescriptions, $latestUserPrescription) {
            $prescription = $prescriptions->get($record->id) ?? $latestUserPrescription;
            return [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'doctor_id' => $record->doctor_id,
                'clinic_id' => $record->vet_registeration_id,
                'pet_id' => $prescription?->pet_id,
                'file_name' => $record->file_name,
                'mime_type' => $record->mime_type,
                'notes' => $record->notes,
                'uploaded_at' => optional($record->created_at)->toIso8601String(),
                'url' => $this->buildRecordUrl($record->file_path),
                'prescription' => $prescription,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'records' => $recordsMapped,
            ],
        ]);
    }

    protected function ensurePetBelongsToUser(int $userId, int $petId): ?int
    {
        $tables = [];
        if (Schema::hasTable('user_pets')) {
            $tables[] = ['user_pets', 'user_id'];
        }
        if (Schema::hasTable('pets')) {
            $userColumn = Schema::hasColumn('pets', 'user_id')
                ? 'user_id'
                : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);
            if ($userColumn) {
                $tables[] = ['pets', $userColumn];
            }
        }

        foreach ($tables as [$table, $userColumn]) {
            $exists = DB::table($table)
                ->where('id', $petId)
                ->where($userColumn, $userId)
                ->exists();
            if ($exists) {
                return $petId;
            }
        }

        return null;
    }

    private function updatePetHealthState(int $userId, int $petId, bool $isChronic, ?string $diseaseName): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        $pet = DB::table('pets')
            ->where('id', $petId)
            ->where(function ($q) use ($userId) {
                if (Schema::hasColumn('pets', 'user_id')) {
                    $q->where('user_id', $userId);
                }
            })
            ->first();

        if (!$pet) {
            return;
        }

        $updates = [];
        if ($isChronic) {
            $updates['health_state'] = 'chronic';
        }
        if ($diseaseName) {
            $updates['suggested_disease'] = $diseaseName;
        }

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('pets')->where('id', $petId)->update($updates);
        }
    }

    private function maybeStructureMedicines(?string $raw, ?string $diagnosis, ?string $notes): ?array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $fallback = $this->fallbackMedParse($raw);

        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? \App\Support\GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return $fallback;
        }

        $model = \App\Support\GeminiConfig::chatModel() ?: \App\Support\GeminiConfig::defaultModel();
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);

        $prompt = <<<PROMPT
You are a veterinary prescription parser. Convert the free-text medications into a JSON array.
Input fields:
- diagnosis: {$diagnosis}
- notes: {$notes}
- medications text: "{$raw}"

Rules:
- Output ONLY JSON array. No prose. Each item: {"name": "...", "dose": "...", "frequency": "...", "duration": "...", "route": "...", "notes": "..."}.
- Keep unknown fields as empty strings.
- Do not invent extra meds beyond the input.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])->post($endpoint, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP' => 0.8,
                    'topK' => 32,
                    'maxOutputTokens' => 256,
                ],
            ]);

            $text = $response->json('candidates.0.content.parts.0.text');
            if (!$response->successful() || !$text) {
                return $fallback;
            }

            $jsonStart = strpos($text, '[');
            $json = $jsonStart !== false ? substr($text, $jsonStart) : $text;
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function fallbackMedParse(string $raw): ?array
    {
        $parts = preg_split('/[;\n]+/', $raw);
        $items = [];
        foreach ($parts as $part) {
            $text = trim($part);
            if ($text === '') {
                continue;
            }
            $items[] = [
                'name' => $text,
                'dose' => '',
                'frequency' => '',
                'duration' => '',
                'route' => '',
                'notes' => '',
            ];
        }

        return $items ?: null;
    }

    protected function resolveUser(string $identifier): ?User
    {
        $query = User::query()->select('id', 'name', 'last_vet_id', 'referral_code', 'phone', 'email', 'last_vet_slug');
        if (ctype_digit($identifier)) {
            return $query->find((int) $identifier);
        }

        return $query
            ->where('referral_code', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('last_vet_slug', $identifier)
            ->first();
    }

    protected function buildRecordUrl(string $filePath): string
    {
        $diskUrl = Storage::disk('public')->url($filePath);
        $path = parse_url($diskUrl, PHP_URL_PATH) ?? $diskUrl;
        $path = '/' . ltrim($path, '/');
        $prefix = trim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
        if ($prefix && $prefix !== '') {
            $path = '/' . trim($prefix, '/') . $path;
        }

        return url($path);
    }
}
