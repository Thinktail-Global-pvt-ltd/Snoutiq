<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardProfileController extends Controller
{
    /**
     * Resolve the authenticated dashboard context (role + clinic/doctor IDs).
     */
    protected function resolveContext(Request $request): array
    {
        // Prefer explicit query params when provided (to avoid relying on session for IDs)
        $queryRole = $this->normalizeString($request->query('role'));
        $queryClinicId = (int) $request->query('clinic_id', 0);
        $queryDoctorId = (int) $request->query('doctor_id', 0);

        $session = $request->session();
        $sessionAuth = $session->get('auth_full');
        $sessionUser = $session->get('user');

        $role = $queryRole
            ?? $session->get('role')
            ?? data_get($sessionAuth, 'role')
            ?? data_get($sessionUser, 'role')
            ?? 'clinic_admin';

        $clinicCandidates = [
            $queryClinicId > 0 ? $queryClinicId : null,
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

        if (!in_array($role, ['doctor', 'receptionist'], true)) {
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
        $receptionistId = null;
        if ($role === 'receptionist') {
            $receptionistId = $session->get('receptionist_id')
                ?? data_get($sessionAuth, 'receptionist_id')
                ?? data_get($sessionAuth, 'user_id')
                ?? $session->get('user_id')
                ?? data_get($sessionUser, 'id');
            $receptionistId = $receptionistId ? (int) $receptionistId : null;
        }

        if (!$clinicId && $role === 'receptionist' && $receptionistId) {
            $rec = Receptionist::find($receptionistId);
            if ($rec?->vet_registeration_id) {
                $clinicId = (int) $rec->vet_registeration_id;
            }
        }

        $doctorId = $session->get('doctor_id')
            ?? data_get($session->get('doctor'), 'id')
            ?? data_get($sessionAuth, 'doctor_id')
            ?? data_get($sessionAuth, 'user.doctor_id')
            ?? ($queryDoctorId > 0 ? $queryDoctorId : null);

        $doctorId = $doctorId ? (int) $doctorId : null;

        return [
            'role' => $role,
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId && $doctorId > 0 ? $doctorId : null,
            'receptionist_id' => $receptionistId,
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
            'slug',
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

    protected function passwordMatches(?string $storedPassword, string $providedPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (Str::startsWith($storedPassword, '$2y$') || Str::startsWith($storedPassword, '$argon2')) {
            return Hash::check($providedPassword, $storedPassword);
        }

        return hash_equals((string) $storedPassword, $providedPassword);
    }

    protected function referralCodeForClinic(VetRegisterationTemp $clinic): string
    {
        $idSeed = max(1, (int) $clinic->id);
        $base36 = strtoupper(str_pad(base_convert((string) $idSeed, 10, 36), 5, '0', STR_PAD_LEFT));
        $slugFragment = strtoupper(Str::substr(Str::slug($clinic->slug ?: $clinic->name), 0, 2));

        if ($slugFragment === '') {
            $slugFragment = 'CL';
        }

        return 'SN-'.$slugFragment.$base36;
    }

    /**
     * GET /api/dashboard/profile
     */
    public function show(Request $request)
    {
        $ctx = $this->resolveContext($request);

        if (! in_array($ctx['role'], ['clinic_admin', 'doctor', 'receptionist'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Profile is available for clinic, doctor, and receptionist accounts only.',
            ], 403);
        }

        $clinic = null;
        if ($ctx['clinic_id']) {
            $clinic = VetRegisterationTemp::query()
                ->select($this->clinicSelect())
                ->find($ctx['clinic_id']);
        }
        if ($clinic) {
            $clinic->referral_code = $this->referralCodeForClinic($clinic);
        }

        $doctorsQuery = Doctor::query()->select($this->doctorSelect());
        if ($ctx['clinic_id']) {
            // Always show all doctors belonging to the clinic id in the URL/query
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

        // Build links for the clinic public page + QR image (for both local and hosted usage)
        $clinicPagePath = null;
        $clinicPageUrl = null;
        $clinicQrImage = null;
        if ($clinic && $clinic->slug) {
            $clinicPagePath = '/vets/' . rawurlencode($clinic->slug);
            $clinicPageUrl = url($clinicPagePath);
            $clinicQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=12&data='
                . urlencode($clinicPageUrl);
        }

        return response()->json([
            'success' => true,
            'role' => $ctx['role'],
            'clinic_id' => $ctx['clinic_id'],
            'doctor_id' => $ctx['doctor_id'],
            'clinic' => $clinic,
            'doctors' => $doctors,
            'doctor' => $currentDoctor,
            'clinic_page_path' => $clinicPagePath,
            'clinic_page_url' => $clinicPageUrl,
            'clinic_qr_image' => $clinicQrImage,
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

    /**
     * PUT /api/dashboard/profile/password
     */
    public function updatePassword(Request $request)
    {
        $ctx = $this->resolveContext($request);
        if (! in_array($ctx['role'], ['clinic_admin', 'doctor', 'receptionist'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Password updates are available for clinic, doctor, and receptionist accounts only.',
            ], 403);
        }

        $payload = $request->validate([
            'current_password' => 'nullable|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $currentPassword = (string) ($payload['current_password'] ?? '');
        $newPassword = (string) ($payload['new_password'] ?? '');
        $hashedPassword = Hash::make($newPassword);

        if ($ctx['role'] === 'clinic_admin') {
            if (! $ctx['clinic_id']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Clinic profile not found.',
                ], 404);
            }
            if (! Schema::hasColumn('vet_registerations_temp', 'password')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Password updates are not enabled for this clinic.',
                ], 400);
            }
            $clinic = VetRegisterationTemp::query()->find($ctx['clinic_id']);
            if (! $clinic) {
                return response()->json([
                    'success' => false,
                    'error' => 'Clinic profile not found.',
                ], 404);
            }
            $storedPassword = (string) ($clinic->password ?? '');
            if ($storedPassword !== '') {
                if ($currentPassword === '') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is required.',
                    ], 422);
                }
                if (! $this->passwordMatches($storedPassword, $currentPassword)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is incorrect.',
                    ], 422);
                }
            }
            $clinic->password = $hashedPassword;
            $clinic->save();
        } elseif ($ctx['role'] === 'doctor') {
            if (! $ctx['doctor_id']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor profile not found.',
                ], 404);
            }
            $doctor = Doctor::query()->find($ctx['doctor_id']);
            if (! $doctor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor profile not found.',
                ], 404);
            }
            $columns = [];
            if (Schema::hasColumn('doctors', 'password')) {
                $columns[] = 'password';
            }
            if (Schema::hasColumn('doctors', 'doctor_password')) {
                $columns[] = 'doctor_password';
            }
            if (! $columns) {
                return response()->json([
                    'success' => false,
                    'error' => 'Password updates are not enabled for doctors.',
                ], 400);
            }
            $storedPassword = (string) ($doctor->password ?? $doctor->doctor_password ?? '');
            if ($storedPassword !== '') {
                if ($currentPassword === '') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is required.',
                    ], 422);
                }
                if (! $this->passwordMatches($storedPassword, $currentPassword)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is incorrect.',
                    ], 422);
                }
            }
            $updates = array_fill_keys($columns, $hashedPassword);
            $doctor->forceFill($updates);
            $doctor->save();
        } else {
            $receptionistId = $ctx['receptionist_id'];
            if (! $receptionistId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Receptionist profile not found.',
                ], 404);
            }
            $receptionist = Receptionist::query()->find($receptionistId);
            if (! $receptionist) {
                return response()->json([
                    'success' => false,
                    'error' => 'Receptionist profile not found.',
                ], 404);
            }
            $columns = [];
            if (Schema::hasColumn('receptionists', 'password')) {
                $columns[] = 'password';
            }
            if (Schema::hasColumn('receptionists', 'receptionist_password')) {
                $columns[] = 'receptionist_password';
            }
            if (! $columns) {
                return response()->json([
                    'success' => false,
                    'error' => 'Password updates are not enabled for receptionists.',
                ], 400);
            }
            $storedPassword = (string) ($receptionist->password ?? $receptionist->receptionist_password ?? '');
            if ($storedPassword !== '') {
                if ($currentPassword === '') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is required.',
                    ], 422);
                }
                if (! $this->passwordMatches($storedPassword, $currentPassword)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Current password is incorrect.',
                    ], 422);
                }
            }
            $updates = array_fill_keys($columns, $hashedPassword);
            $receptionist->forceFill($updates);
            $receptionist->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
