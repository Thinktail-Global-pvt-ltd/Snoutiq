<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;

class DashboardProfileController extends Controller
{
    /**
     * Resolve the authenticated dashboard context (role + clinic/doctor IDs).
     */
    protected function resolveContext(Request $request): array
    {
        $session = $request->session();
        $sessionAuth = $session->get('auth_full');
        $sessionUser = $session->get('user');

        $role = $session->get('role')
            ?? data_get($sessionAuth, 'role')
            ?? data_get($sessionUser, 'role')
            ?? 'clinic_admin';

        $clinicCandidates = [
            $session->get('clinic_id'),
            $session->get('vet_registerations_temp_id'),
            $session->get('vet_registeration_id'),
            $session->get('vet_id'),
            data_get($sessionUser, 'clinic_id'),
            data_get($sessionUser, 'vet_registeration_id'),
            data_get($sessionAuth, 'clinic_id'),
            data_get($sessionAuth, 'user.clinic_id'),
            data_get($sessionAuth, 'user.vet_registeration_id'),
        ];

        if ($role !== 'doctor') {
            array_unshift(
                $clinicCandidates,
                $session->get('user_id'),
                data_get($sessionUser, 'id')
            );
        }

        $clinicId = null;
        foreach ($clinicCandidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            $num = (int) $candidate;
            if ($num > 0) {
                $clinicId = $num;
                break;
            }
        }

        $doctorId = $session->get('doctor_id')
            ?? data_get($session->get('doctor'), 'id')
            ?? data_get($sessionAuth, 'doctor_id')
            ?? data_get($sessionAuth, 'user.doctor_id');

        $doctorId = $doctorId ? (int) $doctorId : null;

        return [
            'role' => $role,
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId && $doctorId > 0 ? $doctorId : null,
            'can_edit_clinic' => $role === 'clinic_admin',
            'can_edit_doctor' => in_array($role, ['clinic_admin', 'doctor'], true),
        ];
    }

    protected function clinicSelect(): array
    {
        return [
            'id',
            'clinic_profile',
            'name',
            'email',
            'mobile',
            'city',
            'pincode',
            'address',
            'license_no',
            'chat_price',
            'bio',
            'hospital_profile',
            'employee_id',
        ];
    }

    protected function doctorSelect(): array
    {
        return [
            'id',
            'doctor_name',
            'doctor_email',
            'doctor_mobile',
            'doctor_license',
            'doctor_image',
            'doctors_price',
            'vet_registeration_id',
            'created_at',
            'updated_at',
        ];
    }

    protected function normalizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * GET /api/dashboard/profile
     */
    public function show(Request $request)
    {
        $ctx = $this->resolveContext($request);

        if (! in_array($ctx['role'], ['clinic_admin', 'doctor'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Profile is available for clinic and doctor accounts only.',
            ], 403);
        }

        $clinic = null;
        if ($ctx['clinic_id']) {
            $clinic = VetRegisterationTemp::query()
                ->select($this->clinicSelect())
                ->find($ctx['clinic_id']);
        }

        $doctorsQuery = Doctor::query()->select($this->doctorSelect());
        if ($ctx['clinic_id']) {
            $doctorsQuery->where('vet_registeration_id', $ctx['clinic_id']);
        } elseif ($ctx['doctor_id']) {
            $doctorsQuery->where('id', $ctx['doctor_id']);
        }
        $doctors = $doctorsQuery->orderBy('doctor_name')->get();

        $currentDoctor = $ctx['doctor_id']
            ? $doctors->firstWhere('id', $ctx['doctor_id'])
            : null;

        $editableDoctorIds = [];
        if ($ctx['role'] === 'clinic_admin') {
            $editableDoctorIds = $doctors->pluck('id')->all();
        } elseif ($ctx['role'] === 'doctor' && $ctx['doctor_id']) {
            $editableDoctorIds = [$ctx['doctor_id']];
        }

        return response()->json([
            'success' => true,
            'role' => $ctx['role'],
            'clinic_id' => $ctx['clinic_id'],
            'doctor_id' => $ctx['doctor_id'],
            'clinic' => $clinic,
            'doctors' => $doctors,
            'doctor' => $currentDoctor,
            'editable' => [
                'clinic' => $ctx['can_edit_clinic'] && (bool) $ctx['clinic_id'],
                'doctor_ids' => $editableDoctorIds,
            ],
        ]);
    }

    /**
     * PUT /api/dashboard/profile/clinic
     */
    public function updateClinic(Request $request)
    {
        $ctx = $this->resolveContext($request);
        if (! ($ctx['can_edit_clinic'] && $ctx['clinic_id'])) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have permission to edit clinic details.',
            ], 403);
        }

        $payload = $request->validate([
            'clinic_profile' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:25',
            'city' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'license_no' => 'nullable|string|max:120',
            'chat_price' => 'nullable|numeric|min:0|max:1000000',
            'bio' => 'nullable|string|max:2000',
        ]);

        $clinic = VetRegisterationTemp::query()->find($ctx['clinic_id']);
        if (! $clinic) {
            return response()->json([
                'success' => false,
                'error' => 'Clinic not found.',
            ], 404);
        }

        $updates = [
            'clinic_profile' => $this->normalizeString($payload['clinic_profile'] ?? null),
            'name' => $this->normalizeString($payload['name'] ?? null),
            'email' => $this->normalizeString($payload['email'] ?? null),
            'mobile' => $this->normalizeString($payload['mobile'] ?? null),
            'city' => $this->normalizeString($payload['city'] ?? null),
            'pincode' => $this->normalizeString($payload['pincode'] ?? null),
            'address' => $this->normalizeString($payload['address'] ?? null),
            'license_no' => $this->normalizeString($payload['license_no'] ?? null),
            'chat_price' => array_key_exists('chat_price', $payload)
                ? ($payload['chat_price'] === null ? null : (float) $payload['chat_price'])
                : $clinic->chat_price,
            'bio' => $this->normalizeString($payload['bio'] ?? null),
        ];

        $clinic->fill($updates);
        $clinic->save();

        $fresh = VetRegisterationTemp::query()
            ->select($this->clinicSelect())
            ->find($clinic->id);

        return response()->json([
            'success' => true,
            'message' => 'Clinic profile updated successfully.',
            'clinic' => $fresh,
        ]);
    }

    /**
     * PUT /api/dashboard/profile/doctor/{doctor}
     */
    public function updateDoctor(Request $request, int $doctorId)
    {
        $ctx = $this->resolveContext($request);
        if (! $ctx['can_edit_doctor']) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have permission to edit doctor details.',
            ], 403);
        }

        $doctor = Doctor::query()->find($doctorId);
        if (! $doctor) {
            return response()->json([
                'success' => false,
                'error' => 'Doctor record not found.',
            ], 404);
        }

        if ($ctx['role'] === 'doctor' && $doctor->id !== $ctx['doctor_id']) {
            return response()->json([
                'success' => false,
                'error' => 'You can edit only your own profile.',
            ], 403);
        }

        if ($ctx['role'] === 'clinic_admin') {
            if (! $ctx['clinic_id'] || (int) $doctor->vet_registeration_id !== (int) $ctx['clinic_id']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor does not belong to your clinic.',
                ], 403);
            }
        }

        $payload = $request->validate([
            'doctor_name' => 'nullable|string|max:255',
            'doctor_email' => 'nullable|email|max:255',
            'doctor_mobile' => 'nullable|string|max:25',
            'doctor_license' => 'nullable|string|max:150',
            'doctors_price' => 'nullable|numeric|min:0|max:1000000',
        ]);

        $updates = [
            'doctor_name' => $this->normalizeString($payload['doctor_name'] ?? null),
            'doctor_email' => $this->normalizeString($payload['doctor_email'] ?? null),
            'doctor_mobile' => $this->normalizeString($payload['doctor_mobile'] ?? null),
            'doctor_license' => $this->normalizeString($payload['doctor_license'] ?? null),
            'doctors_price' => array_key_exists('doctors_price', $payload)
                ? ($payload['doctors_price'] === null ? null : (float) $payload['doctors_price'])
                : $doctor->doctors_price,
        ];

        $doctor->fill($updates);
        $doctor->save();

        $fresh = Doctor::query()
            ->select($this->doctorSelect())
            ->find($doctor->id);

        return response()->json([
            'success' => true,
            'message' => 'Doctor profile updated successfully.',
            'doctor' => $fresh,
        ]);
    }
}
