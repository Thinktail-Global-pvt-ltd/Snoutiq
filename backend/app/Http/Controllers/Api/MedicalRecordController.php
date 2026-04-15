<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class MedicalRecordController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

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

    /**
     * Alias endpoint for mobile clients:
     * GET /api/documents?user_id={id}&pet_id={id}
     * Returns medical records mapped to the requested pet.
     */
    public function documents(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'pet_id' => ['required', 'integer'],
            'clinic_id' => ['nullable', 'integer'],
        ]);

        $user = User::query()
            ->select('id', 'name')
            ->find((int) $validated['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        $petId = (int) $validated['pet_id'];
        if ($this->ensurePetBelongsToUser((int) $user->id, $petId) === null) {
            return response()->json([
                'success' => false,
                'error' => 'Pet not found for this user',
            ], 422);
        }

        $clinicId = isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null;

        $prescriptionQuery = Prescription::query()
            ->where('user_id', (int) $user->id)
            ->where('pet_id', $petId)
            ->whereNotNull('medical_record_id');

        if ($clinicId) {
            $prescriptionQuery->whereIn('medical_record_id', MedicalRecord::query()
                ->select('id')
                ->where('user_id', (int) $user->id)
                ->where('vet_registeration_id', $clinicId));
        }

        $recordIds = $prescriptionQuery
            ->orderByDesc('id')
            ->pluck('medical_record_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recordIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'pet_id' => $petId,
                    'records' => [],
                ],
            ]);
        }

        $records = MedicalRecord::query()
            ->where('user_id', (int) $user->id)
            ->whereIn('id', $recordIds->all())
            ->orderByDesc('created_at')
            ->get();

        $prescriptions = Prescription::query()
            ->where('user_id', (int) $user->id)
            ->where('pet_id', $petId)
            ->whereIn('medical_record_id', $records->pluck('id')->all())
            ->orderByDesc('id')
            ->get()
            ->groupBy('medical_record_id')
            ->map(fn ($items) => $items->first());

        $petPayload = null;
        if (Schema::hasTable('pets')) {
            $petPayload = Pet::query()->find($petId)?->toArray();
        }

        $recordsMapped = $records->map(function (MedicalRecord $record) use ($prescriptions, $petId, $petPayload) {
            return [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'doctor_id' => $record->doctor_id,
                'clinic_id' => $record->vet_registeration_id,
                'pet_id' => $petId,
                'pet' => $petPayload,
                'pets' => $petPayload,
                'file_name' => $record->file_name,
                'mime_type' => $record->mime_type,
                'notes' => $record->notes,
                'uploaded_at' => optional($record->created_at)->toIso8601String(),
                'url' => $this->buildRecordUrl($record->file_path),
                'prescription' => $prescriptions->get($record->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'pet_id' => $petId,
                'records' => $recordsMapped,
            ],
        ]);
    }

    public function update(Request $request, MedicalRecord $record)
    {
        $validated = $request->validate([
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'doctor_treatment' => ['nullable', 'string'],
            'visit_category' => ['nullable', 'string', 'max:255'],
            'case_severity' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'numeric'],
            'exam_notes' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'diagnosis_status' => ['nullable', 'string', 'max:255'],
            'disease_name' => ['nullable', 'string', 'max:255'],
            'prognosis' => ['nullable', 'string', 'in:good,fair,poor,grave'],
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'history_snapshot' => ['nullable', 'string'],
            'video_inclinic' => ['nullable', 'string', 'max:255'],
            'call_session' => ['nullable', 'string', 'max:255'],
            'medicines' => ['nullable', 'string', 'max:2000'],
            'medications_json' => ['nullable'],
            'follow_up_required' => ['nullable', 'boolean'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
            'system_affected' => ['nullable', 'string', 'max:100'],
            'system_affected_id' => ['nullable', 'integer', 'exists:affected_systems,id'],
            'mucous_membrane' => ['nullable', 'string', 'in:normal_pink,cherry_red,yellow,white'],
            'dehydration_level' => ['nullable', 'string', 'in:no,mild,moderate,severe'],
            'abdominal_pain_reaction' => ['nullable', 'string', 'in:painful,no_pain'],
            'auscultation' => ['nullable', 'string', 'in:normal,abnormal'],
            'physical_exam_other' => ['nullable', 'string'],
            'pet_id' => ['nullable', 'integer'],
            'video_appointment_id' => ['nullable', 'integer', 'exists:video_apointment,id'],
            'in_clinic_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'in_clinic_appointtment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'record_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);
        $hasDoctorTreatmentColumn = Schema::hasColumn('prescriptions', 'doctor_treatment');

        $recordFilePath = null;

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
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'error' => $file->getErrorMessage(),
                ], 422);
            }
            $filePayload = $this->storeRecordFile($file);
            if (!$filePayload) {
                return response()->json([
                    'success' => false,
                    'error' => 'File upload failed',
                ], 500);
            }
            $record->file_path = $filePayload['path'];
            $record->file_name = $filePayload['name'];
            $record->mime_type = $filePayload['mime'];
            $recordFilePath = $filePayload['path'];
        }

        $record->notes = $validated['notes'] ?? $record->notes;
        $record->doctor_id = $doctorId ?? $record->doctor_id;
        $record->save();

        $prescription = Prescription::firstOrNew(['medical_record_id' => $record->id]);
        $structuredMedications = $this->decodeMedicationsInput($request->input('medications_json'));
        $medsJson = $structuredMedications ?? $this->maybeStructureMedicines($validated['medicines'] ?? null, $validated['diagnosis'] ?? null, $validated['notes'] ?? null);
        $diseaseName = $validated['disease_name'] ?? $validated['diagnosis'] ?? null;
        $isChronic = ($validated['diagnosis_status'] ?? '') === 'chronic';
        $inClinicAppointmentId = $validated['in_clinic_appointment_id']
            ?? $validated['in_clinic_appointtment_id']
            ?? null;
        $resolvedSystemAffectedId = $this->resolveSystemAffectedId(
            $validated['system_affected_id'] ?? null,
            $validated['system_affected'] ?? null
        );

        $prescriptionPayload = [
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
            'prognosis' => $validated['prognosis'] ?? $prescription->prognosis,
            'treatment_plan' => $validated['treatment_plan'] ?? $prescription->treatment_plan,
            'home_care' => $validated['home_care'] ?? $prescription->home_care,
            'history_snapshot' => $validated['history_snapshot'] ?? $prescription->history_snapshot,
            'video_inclinic' => $validated['video_inclinic'] ?? $prescription->video_inclinic,
            'call_session' => $validated['call_session'] ?? $prescription->call_session,
            'follow_up_required' => $validated['follow_up_required'] ?? $prescription->follow_up_required,
            'follow_up_date' => $validated['follow_up_date'] ?? $prescription->follow_up_date,
            'follow_up_type' => $validated['follow_up_type'] ?? $prescription->follow_up_type,
            'follow_up_notes' => $validated['follow_up_notes'] ?? $prescription->follow_up_notes,
            'system_affected' => $validated['system_affected'] ?? $prescription->system_affected,
            'system_affected_id' => $resolvedSystemAffectedId ?? $prescription->system_affected_id,
            'mucous_membrane' => $validated['mucous_membrane'] ?? $prescription->mucous_membrane,
            'dehydration_level' => $validated['dehydration_level'] ?? $prescription->dehydration_level,
            'abdominal_pain_reaction' => $validated['abdominal_pain_reaction'] ?? $prescription->abdominal_pain_reaction,
            'auscultation' => $validated['auscultation'] ?? $prescription->auscultation,
            'physical_exam_other' => $validated['physical_exam_other'] ?? $prescription->physical_exam_other,
            'pet_id' => $petId ?? $prescription->pet_id,
            'video_appointment_id' => $validated['video_appointment_id'] ?? $prescription->video_appointment_id,
            'in_clinic_appointment_id' => $inClinicAppointmentId ?? $prescription->in_clinic_appointment_id,
            'medications_json' => $medsJson ?? $prescription->medications_json,
        ];
        if ($hasDoctorTreatmentColumn) {
            $prescriptionPayload['doctor_treatment'] = $validated['doctor_treatment'] ?? $prescription->doctor_treatment;
        }
        $prescription->fill($prescriptionPayload);
        if ($recordFilePath) {
            $prescription->image_path = $recordFilePath;
        }
        $prescription->save();
        $this->markVideoApointmentCompleted($validated['video_appointment_id'] ?? $prescription->video_appointment_id ?? null);
        $this->markCallSessionCompleted($validated['call_session'] ?? null);

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
            'doctor_treatment' => ['nullable', 'string'],
            'visit_category' => ['nullable', 'string', 'max:255'],
            'case_severity' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'numeric'],
            'exam_notes' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'diagnosis_status' => ['nullable', 'string', 'max:255'],
            'disease_name' => ['nullable', 'string', 'max:255'],
            'prognosis' => ['nullable', 'string', 'in:good,fair,poor,grave'],
            'treatment_plan' => ['nullable', 'string'],
            'home_care' => ['nullable', 'string'],
            'history_snapshot' => ['nullable', 'string'],
            'video_inclinic' => ['nullable', 'string', 'max:255'],
            'call_session' => ['nullable', 'string', 'max:255'],
            'medicines' => ['nullable', 'string', 'max:2000'],
            'medications_json' => ['nullable'],
            'follow_up_required' => ['nullable', 'boolean'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:255'],
            'follow_up_notes' => ['nullable', 'string'],
            'system_affected' => ['nullable', 'string', 'max:100'],
            'system_affected_id' => ['nullable', 'integer', 'exists:affected_systems,id'],
            'mucous_membrane' => ['nullable', 'string', 'in:normal_pink,cherry_red,yellow,white'],
            'dehydration_level' => ['nullable', 'string', 'in:no,mild,moderate,severe'],
            'abdominal_pain_reaction' => ['nullable', 'string', 'in:painful,no_pain'],
            'auscultation' => ['nullable', 'string', 'in:normal,abnormal'],
            'physical_exam_other' => ['nullable', 'string'],
            'pet_id' => ['nullable', 'integer'],
            'video_appointment_id' => ['nullable', 'integer', 'exists:video_apointment,id'],
            'in_clinic_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'in_clinic_appointtment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'record_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);
        $hasDoctorTreatmentColumn = Schema::hasColumn('prescriptions', 'doctor_treatment');

        $user = User::query()->select('id', 'last_vet_id')->find($validated['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        $clinicId = (int) $validated['clinic_id'];

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

        $recordFilePath = '';
        $filePayload = null;
        if ($request->hasFile('record_file')) {
            $file = $request->file('record_file');
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'error' => $file ? $file->getErrorMessage() : 'File upload failed',
                ], 422);
            }
            $filePayload = $this->storeRecordFile($file);
            if (!$filePayload) {
                return response()->json([
                    'success' => false,
                    'error' => 'File upload failed',
                ], 500);
            }
            $recordFilePath = $filePayload['path'];
        }
        $inClinicAppointmentId = $validated['in_clinic_appointment_id']
            ?? $validated['in_clinic_appointtment_id']
            ?? null;
        $resolvedSystemAffectedId = $this->resolveSystemAffectedId(
            $validated['system_affected_id'] ?? null,
            $validated['system_affected'] ?? null
        );

        $record = MedicalRecord::create([
            'user_id' => $user->id,
            'doctor_id' => $doctorId,
            'vet_registeration_id' => $clinicId,
            'file_path' => $recordFilePath,
            'file_name' => $filePayload['name'] ?? null,
            'mime_type' => $filePayload['mime'] ?? null,
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
            'prognosis' => $validated['prognosis'] ?? null,
            'treatment_plan' => $validated['treatment_plan'] ?? null,
            'home_care' => $validated['home_care'] ?? null,
            'history_snapshot' => $validated['history_snapshot'] ?? null,
            'video_inclinic' => $validated['video_inclinic'] ?? null,
            'call_session' => $validated['call_session'] ?? null,
            'follow_up_required' => $validated['follow_up_required'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'follow_up_type' => $validated['follow_up_type'] ?? null,
            'follow_up_notes' => $validated['follow_up_notes'] ?? null,
            'system_affected' => $validated['system_affected'] ?? null,
            'system_affected_id' => $resolvedSystemAffectedId,
            'mucous_membrane' => $validated['mucous_membrane'] ?? null,
            'dehydration_level' => $validated['dehydration_level'] ?? null,
            'abdominal_pain_reaction' => $validated['abdominal_pain_reaction'] ?? null,
            'auscultation' => $validated['auscultation'] ?? null,
            'physical_exam_other' => $validated['physical_exam_other'] ?? null,
            'pet_id' => $petId,
            'video_appointment_id' => $validated['video_appointment_id'] ?? null,
            'in_clinic_appointment_id' => $inClinicAppointmentId,
            'medications_json' => $this->decodeMedicationsInput($request->input('medications_json'))
                ?? $this->maybeStructureMedicines($validated['medicines'] ?? null, $validated['diagnosis'] ?? null, $validated['notes'] ?? null),
        ];
        if ($hasDoctorTreatmentColumn) {
            $prescriptionPayload['doctor_treatment'] = $validated['doctor_treatment'] ?? null;
        }
        if ($recordFilePath !== '') {
            $prescriptionPayload['image_path'] = $recordFilePath;
        }
        $prescription = Prescription::create($prescriptionPayload);
        if (!$prescription || !$prescription->exists) {
            return response()->json([
                'success' => false,
                'error' => 'Prescription could not be created',
            ], 500);
        }

        $this->markVideoApointmentCompleted($validated['video_appointment_id'] ?? $prescription->video_appointment_id ?? null);
        if ($petId) {
            $this->updatePetHealthState($user->id, $petId, ($validated['diagnosis_status'] ?? '') === 'chronic', $validated['disease_name'] ?? $validated['diagnosis'] ?? null);
        }
        $this->markCallSessionCompleted($validated['call_session'] ?? null);
        $prescriptionWhatsApp = $this->sendPrescriptionSentWhatsApp($prescription, $record);

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
            'prescription_whatsapp' => $prescriptionWhatsApp,
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

        $petIds = $prescriptions
            ->pluck('pet_id')
            ->push($latestUserPrescription?->pet_id)
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $petMap = collect();
        if (!empty($petIds) && Schema::hasTable('pets')) {
            $petMap = Pet::query()
                ->whereIn('id', $petIds)
                ->get()
                ->keyBy('id');
        }

        $recordsMapped = $records->map(function (MedicalRecord $record) use ($prescriptions, $latestUserPrescription, $petMap) {
            $prescription = $prescriptions->get($record->id) ?? $latestUserPrescription;
            $petId = $prescription?->pet_id ? (int) $prescription->pet_id : null;
            $petPayload = $petId ? $petMap->get($petId)?->toArray() : null;

            return [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'doctor_id' => $record->doctor_id,
                'clinic_id' => $record->vet_registeration_id,
                'pet_id' => $petId,
                'pet' => $petPayload,
                'pets' => $petPayload,
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

    private function storeRecordFile($file): ?array
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $directory = 'medical-records';
        $filename = $file->hashName();
        $storedPath = null;

        try {
            $disk = Storage::disk('public');
            $disk->makeDirectory($directory);
            $path = $disk->putFileAs($directory, $file, $filename);
            if (is_string($path)) {
                $storedPath = $path;
            }
        } catch (\Throwable $e) {
            $storedPath = null;
        }

        if (!$storedPath) {
            foreach ($this->recordFileTargets() as $target) {
                $dest = rtrim($target['root'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $directory;
                try {
                    File::ensureDirectoryExists($dest);
                    $file->move($dest, $filename);
                    $prefix = $target['prefix'] !== '' ? trim($target['prefix'], '/\\') . '/' : '';
                    $storedPath = $prefix . $directory . '/' . $filename;
                    break;
                } catch (\Throwable $e) {
                    $storedPath = null;
                }
            }
        }

        if (!$storedPath || !is_string($storedPath)) {
            return null;
        }

        return [
            'path' => $storedPath,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
        ];
    }

    private function recordFileTargets(): array
    {
        $targets = [];
        $publicRoot = rtrim(public_path(), DIRECTORY_SEPARATOR);
        $storageRoot = rtrim(storage_path('app/public'), DIRECTORY_SEPARATOR);
        $diskRoot = rtrim((string) config('filesystems.disks.public.root', ''), DIRECTORY_SEPARATOR);

        if ($diskRoot !== '') {
            $prefix = '';
            if ($publicRoot !== '' && str_starts_with($diskRoot, $publicRoot)) {
                $suffix = trim(substr($diskRoot, strlen($publicRoot)), DIRECTORY_SEPARATOR);
                $prefix = $suffix;
            } elseif ($diskRoot === $storageRoot) {
                $prefix = 'storage';
            }
            $targets[] = ['root' => $diskRoot, 'prefix' => $prefix];
        }

        $targets[] = ['root' => $publicRoot, 'prefix' => ''];
        $targets[] = ['root' => $storageRoot, 'prefix' => 'storage'];

        $seen = [];
        $unique = [];
        foreach ($targets as $target) {
            if ($target['root'] === '' || isset($seen[$target['root']])) {
                continue;
            }
            $seen[$target['root']] = true;
            $unique[] = $target;
        }

        return $unique;
    }

    private function updatePetHealthState(int $userId, int $petId, bool $isChronic, ?string $diseaseName): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        $hasHealthState = Schema::hasColumn('pets', 'health_state');
        $hasSuggestedDisease = Schema::hasColumn('pets', 'suggested_disease');
        if (!$hasHealthState && !$hasSuggestedDisease) {
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
        if ($isChronic && $hasHealthState) {
            $updates['health_state'] = 'chronic';
        }
        if ($diseaseName && $hasSuggestedDisease) {
            $updates['suggested_disease'] = $diseaseName;
        }

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('pets')->where('id', $petId)->update($updates);
        }
    }

    private function decodeMedicationsInput($input): ?array
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
            }
        }

        if (!is_array($input)) {
            return null;
        }

        $normalized = [];
        foreach ($input as $item) {
            if (is_object($item)) {
                $item = json_decode(json_encode($item), true);
            }
            if (!is_array($item)) {
                continue;
            }

            $med = [
                'name' => trim((string) ($item['name'] ?? $item['medicine'] ?? '')),
                'dose' => trim((string) ($item['dose'] ?? '')),
                'frequency' => trim((string) ($item['frequency'] ?? '')),
                'duration' => trim((string) ($item['duration'] ?? '')),
                'route' => trim((string) ($item['route'] ?? '')),
                'notes' => trim((string) ($item['notes'] ?? '')),
            ];

            $timings = $item['timings'] ?? $item['timing'] ?? [];
            if (is_string($timings)) {
                $timings = array_filter(array_map('trim', explode(',', $timings)));
            }
            if (is_array($timings)) {
                $med['timings'] = array_values(array_unique(array_filter(array_map('trim', $timings))));
            }

            $food = trim((string) ($item['food_relation'] ?? $item['food'] ?? ''));
            if ($food !== '') {
                $med['food_relation'] = $food;
            }

            $hasContent = array_filter($med, static fn ($value) => $value !== '' && $value !== []);
            if ($hasContent) {
                $normalized[] = $med;
            }
        }

        return $normalized ?: null;
    }

    private function resolveSystemAffectedId($systemAffectedIdInput, ?string $systemAffectedInput): ?int
    {
        if (is_numeric($systemAffectedIdInput) && (int) $systemAffectedIdInput > 0) {
            return (int) $systemAffectedIdInput;
        }

        $raw = trim((string) $systemAffectedInput);
        if ($raw === '' || !Schema::hasTable('affected_systems')) {
            return null;
        }

        $rawLower = strtolower($raw);
        $normalizedCode = preg_replace('/[^a-z0-9]+/', '_', $rawLower) ?? '';
        $normalizedCode = trim($normalizedCode, '_');

        $matchedId = DB::table('affected_systems')
            ->whereRaw('LOWER(code) = ?', [$rawLower])
            ->orWhereRaw('LOWER(code) = ?', [$normalizedCode])
            ->orWhereRaw('LOWER(name) = ?', [$rawLower])
            ->value('id');

        return is_numeric($matchedId) ? (int) $matchedId : null;
    }

    private function markCallSessionCompleted(?string $channelName): void
    {
        $channelName = trim((string) $channelName);
        if ($channelName === '') {
            return;
        }
        if (!Schema::hasTable('call_sessions') || !Schema::hasColumn('call_sessions', 'channel_name') || !Schema::hasColumn('call_sessions', 'is_completed')) {
            return;
        }
        DB::table('call_sessions')
            ->where('channel_name', $channelName)
            ->update(['is_completed' => 1, 'updated_at' => now()]);
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

    private function sendPrescriptionSentWhatsApp(Prescription $prescription, MedicalRecord $record): array
    {
        if ($this->hasSentPrescriptionWhatsApp($prescription)) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'already_sent'];
        }

        $user = User::query()
            ->select(['id', 'name', 'phone'])
            ->find($prescription->user_id);
        $pet = $prescription->pet_id ? Pet::query()->find($prescription->pet_id) : null;
        $doctor = $prescription->doctor_id ? Doctor::query()->find($prescription->doctor_id) : null;

        $to = $this->normalizeWhatsAppPhone($user?->phone);
        if (!$to) {
            $this->logPrescriptionWhatsApp($prescription, $record, 'skipped', null, null, null, 'missing_patient_phone');
            return ['sent' => false, 'skipped' => true, 'reason' => 'missing_patient_phone'];
        }

        if (!$this->whatsApp->isConfigured()) {
            $this->logPrescriptionWhatsApp($prescription, $record, 'skipped', $to, null, null, 'whatsapp_not_configured');
            return ['sent' => false, 'skipped' => true, 'reason' => 'whatsapp_not_configured'];
        }

        $template = config('services.whatsapp.templates.cf_prescription_sent', 'cf_prescription_sent');
        $language = config('services.whatsapp.templates.cf_prescription_sent_language', 'en');
        $doctorName = $this->cleanDoctorName($doctor?->doctor_name);
        $parentName = $this->cleanText($user?->name) ?: 'Pet Parent';
        $petName = $this->cleanText($pet?->name) ?: 'your pet';
        $diagnosis = $this->cleanText($prescription->diagnosis ?: $prescription->disease_name) ?: 'Not specified';
        $downloadUrl = $this->buildPrescriptionDownloadUrl($prescription);
        $reviewUrl = $this->resolveClinicReviewUrl($record->vet_registeration_id ?: $doctor?->vet_registeration_id);

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $diagnosis],
                    ['type' => 'text', 'text' => $downloadUrl],
                    ['type' => 'text', 'text' => $reviewUrl],
                ],
            ],
        ];

        try {
            $response = $this->whatsApp->sendTemplateWithResult(
                to: $to,
                template: $template,
                components: $components,
                language: $language,
                channelName: 'prescription_sent_pet_parent'
            );

            $this->logPrescriptionWhatsApp($prescription, $record, 'sent', $to, $template, $language, null, [
                'download_url' => $downloadUrl,
                'review_url' => $reviewUrl,
                'whatsapp_response' => $response,
            ]);

            return [
                'sent' => true,
                'template' => $template,
                'to' => $to,
                'download_url' => $downloadUrl,
                'review_url' => $reviewUrl,
                'whatsapp' => $response,
            ];
        } catch (\Throwable $e) {
            $this->logPrescriptionWhatsApp($prescription, $record, 'failed', $to, $template, $language, $e->getMessage(), [
                'download_url' => $downloadUrl,
                'review_url' => $reviewUrl,
            ]);

            Log::warning('medical_records.prescription_sent_whatsapp_failed', [
                'medical_record_id' => $record->id,
                'prescription_id' => $prescription->id,
                'user_id' => $prescription->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'skipped' => false,
                'reason' => $e->getMessage(),
                'template' => $template,
                'to' => $to,
                'download_url' => $downloadUrl,
                'review_url' => $reviewUrl,
            ];
        }
    }

    private function hasSentPrescriptionWhatsApp(Prescription $prescription): bool
    {
        if (!$prescription->id || !Schema::hasTable('vet_response_reminder_logs')) {
            return false;
        }

        return DB::table('vet_response_reminder_logs')
            ->whereJsonContains('meta->type', 'cf_prescription_sent')
            ->whereJsonContains('meta->prescription_id', $prescription->id)
            ->where('status', 'sent')
            ->exists();
    }

    private function buildPrescriptionDownloadUrl(Prescription $prescription): string
    {
        return $this->buildBackendUrl('/api/consultation/prescription/pdf?prescription_id='.(int) $prescription->id);
    }

    private function resolveClinicReviewUrl(?int $clinicId): string
    {
        $reviewUrl = '';
        if ($clinicId && Schema::hasTable('vet_registerations_temp') && Schema::hasColumn('vet_registerations_temp', 'google_review_url')) {
            $reviewUrl = (string) DB::table('vet_registerations_temp')
                ->where('id', $clinicId)
                ->value('google_review_url');
        }

        return trim($reviewUrl) !== '' ? trim($reviewUrl) : 'https://snoutiq.com';
    }

    private function buildBackendUrl(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if (!str_ends_with($base, '/backend')) {
            $base .= '/backend';
        }

        return $base.'/'.ltrim($path, '/');
    }

    private function normalizeWhatsAppPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    private function cleanDoctorName(?string $name): string
    {
        $clean = $this->cleanText($name);
        $clean = preg_replace('/^dr\.?\s+/i', '', $clean) ?: $clean;

        return trim($clean) !== '' ? trim($clean) : 'Snoutiq';
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?: '');
    }

    private function logPrescriptionWhatsApp(
        Prescription $prescription,
        MedicalRecord $record,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        array $extraMeta = []
    ): void {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return;
        }

        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $prescription->user_id,
                'pet_id' => $prescription->pet_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode(array_merge([
                    'type' => 'cf_prescription_sent',
                    'record_id' => $record->id,
                    'prescription_id' => $prescription->id,
                    'medical_record_id' => $record->id,
                    'clinic_id' => $record->vet_registeration_id,
                    'doctor_id' => $prescription->doctor_id,
                ], $extraMeta)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('medical_records.prescription_sent_whatsapp_log_failed', [
                'medical_record_id' => $record->id,
                'prescription_id' => $prescription->id,
                'error' => $e->getMessage(),
            ]);
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

    protected function buildRecordUrl(?string $filePath): ?string
    {
        $filePath = trim((string) $filePath);
        if ($filePath === '') {
            return null;
        }

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
