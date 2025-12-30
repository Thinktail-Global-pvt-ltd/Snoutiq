<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
            'record_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $clinicId = (int) $validated['clinic_id'];
        if ((int) $record->vet_registeration_id !== $clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Record not linked to this clinic',
            ], 422);
        }

        $doctorId = $validated['doctor_id'] ?? null;
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

        if ($request->hasFile('record_file')) {
            $file = $request->file('record_file');
            $storedPath = $file->store('medical-records', 'public');
            $record->file_path = $storedPath;
            $record->file_name = $file->getClientOriginalName();
            $record->mime_type = $file->getClientMimeType();
        }

        $record->notes = $validated['notes'] ?? $record->notes;
        $record->doctor_id = $doctorId ?? $record->doctor_id;
        $record->save();

        $prescription = Prescription::firstOrNew(['medical_record_id' => $record->id]);
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
            'treatment_plan' => $validated['treatment_plan'] ?? $prescription->treatment_plan,
            'home_care' => $validated['home_care'] ?? $prescription->home_care,
            'follow_up_date' => $validated['follow_up_date'] ?? $prescription->follow_up_date,
            'follow_up_type' => $validated['follow_up_type'] ?? $prescription->follow_up_type,
            'follow_up_notes' => $validated['follow_up_notes'] ?? $prescription->follow_up_notes,
        ]);
        $prescription->save();

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
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
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

        $file = $request->file('record_file');
        $storedPath = $file->store('medical-records', 'public');

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
            'treatment_plan' => $validated['treatment_plan'] ?? null,
            'home_care' => $validated['home_care'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'follow_up_type' => $validated['follow_up_type'] ?? null,
            'follow_up_notes' => $validated['follow_up_notes'] ?? null,
        ];
        $prescription = Prescription::create($prescriptionPayload);

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
