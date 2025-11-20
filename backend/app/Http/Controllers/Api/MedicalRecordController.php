<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicalRecordController extends Controller
{
    public function index(Request $request, int $userId)
    {
        $clinicId = (int) $request->query('clinic_id');
        $user = User::query()->select('id', 'name', 'last_vet_id')->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        if ($clinicId && (int) $user->last_vet_id !== $clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Patient is not associated with this clinic',
            ], 403);
        }

        $records = MedicalRecord::query()
            ->where('user_id', $userId)
            ->when($clinicId, fn ($query) => $query->where('vet_registeration_id', $clinicId))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (MedicalRecord $record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'doctor_id' => $record->doctor_id,
                    'clinic_id' => $record->vet_registeration_id,
                    'file_name' => $record->file_name,
                    'mime_type' => $record->mime_type,
                    'notes' => $record->notes,
                    'uploaded_at' => optional($record->created_at)->toIso8601String(),
                    'url' => Storage::disk('public')->url($record->file_path),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'records' => $records,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
                'url' => Storage::disk('public')->url($record->file_path),
            ],
        ], 201);
    }
}
