<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private const EDITABLE_ROLES = ['doctor', 'receptionist'];

    private function resolveClinicId(Request $request): ?int
    {
        $directKeys = [
            'clinic_id',
            'vet_registeration_id',
            'vet_id',
            'user_id',
        ];
        foreach ($directKeys as $key) {
            $value = $request->input($key) ?? $request->query($key);
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        $headers = [
            $request->header('X-Clinic-Id'),
            $request->header('X-Vet-Id'),
            $request->header('X-User-Id'),
            $request->header('X-Acting-User'),
            $request->header('X-Session-User'),
        ];
        foreach ($headers as $header) {
            if ($header !== null && $header !== '') {
                return (int) $header;
            }
        }

        $slug = $request->input('vet_slug')
            ?? $request->query('vet_slug')
            ?? $request->query('clinic_slug');
        if ($slug) {
            $clinicId = DB::table('vet_registerations_temp')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->value('id');
            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $role = session('role')
            ?? data_get(session('user'), 'role')
            ?? data_get(session('auth_full'), 'role');

        if (in_array($role, ['doctor', 'receptionist'], true)) {
            $clinicId = session('clinic_id')
                ?? session('vet_registerations_temp_id')
                ?? session('vet_registeration_id')
                ?? session('vet_id')
                ?? data_get(session('user'), 'clinic_id')
                ?? data_get(session('auth_full'), 'clinic_id')
                ?? data_get(session('auth_full'), 'user.clinic_id');
            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $sessionUserId = session('user_id') ?? data_get(session('user'), 'id');
        return $sessionUserId ? (int) $sessionUserId : null;
    }

    public function index(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $clinic = VetRegisterationTemp::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $doctors = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('doctor_name')
            ->get()
            ->map(function (Doctor $doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->doctor_name,
                    'email' => $doc->doctor_email,
                    'phone' => $doc->doctor_mobile,
                    'role' => $doc->staff_role ?: 'doctor',
                    'type' => 'doctor',
                    'image' => $doc->doctor_image,
                    'license' => $doc->doctor_license,
                    'created_at' => optional($doc->created_at)->toIso8601String(),
                ];
            });

        $receptionists = Receptionist::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('name')
            ->get()
            ->map(function (Receptionist $rec) {
                return [
                    'id' => $rec->id,
                    'name' => $rec->name,
                    'email' => $rec->email,
                    'phone' => $rec->phone,
                    'role' => $rec->role ?: 'receptionist',
                    'type' => 'receptionist',
                    'created_at' => optional($rec->created_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'clinic_admin' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'email' => $clinic->email,
                    'phone' => $clinic->mobile,
                    'role' => 'clinic_admin',
                    'type' => 'clinic',
                    'image' => $clinic->image,
                ],
                'doctors' => $doctors,
                'receptionists' => $receptionists,
                'editable_roles' => self::EDITABLE_ROLES,
            ],
        ]);
    }

    public function storeReceptionist(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $clinic = VetRegisterationTemp::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', Rule::in(self::EDITABLE_ROLES)],
        ]);
        $role = $validated['role'] ?? 'receptionist';

        // If the user chooses Doctor, save into doctors table; otherwise save to receptionist.
        if ($role === 'doctor') {
            $doctorData = [
                'vet_registeration_id' => $clinicId,
                'doctor_name' => $validated['name'],
                'doctor_email' => $validated['email'] ?? null,
                'doctor_mobile' => $validated['phone'] ?? null,
            ];

            // Only set staff_role if the column exists (older schemas may not have it)
            if (Schema::hasColumn('doctors', 'staff_role')) {
                $doctorData['staff_role'] = 'doctor';
            }

            $doctor = Doctor::create($doctorData);

            return response()->json([
                'status' => true,
                'message' => 'Doctor added',
                'data' => $doctor,
            ], 201);
        }

        $receptionistAttributes = [
            'vet_registeration_id' => $clinicId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'status' => 'active',
        ];

        // Default receptionist password set to plain 123456 when columns exist.
        if (Schema::hasColumn('receptionists', 'password')) {
            $receptionistAttributes['password'] = '123456';
        }
        if (Schema::hasColumn('receptionists', 'receptionist_password')) {
            $receptionistAttributes['receptionist_password'] = '123456';
        }

        $receptionist = Receptionist::create($receptionistAttributes);

        return response()->json([
            'status' => true,
            'message' => 'Staff member added',
            'data' => $receptionist,
        ], 201);
    }

    public function updateRole(Request $request, string $type, int $id)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(self::EDITABLE_ROLES)],
        ]);

        $role = $validated['role'];

        if ($type === 'doctor') {
            $doctor = Doctor::where('vet_registeration_id', $clinicId)->find($id);
            if (!$doctor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Doctor not found for this clinic',
                ], 404);
            }

            $doctor->staff_role = $role;
            $doctor->save();

            return response()->json([
                'status' => true,
                'message' => 'Doctor role updated',
                'data' => [
                    'id' => $doctor->id,
                    'role' => $doctor->staff_role,
                ],
            ]);
        }

        if ($type === 'receptionist') {
            $receptionist = Receptionist::where('vet_registeration_id', $clinicId)->find($id);
            if (!$receptionist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Receptionist not found for this clinic',
                ], 404);
            }

            $receptionist->role = $role;
            $receptionist->save();

            return response()->json([
                'status' => true,
                'message' => 'Receptionist role updated',
                'data' => [
                    'id' => $receptionist->id,
                    'role' => $receptionist->role,
                ],
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unsupported staff type. Allowed: doctor or receptionist.',
        ], 422);
    }

    public function update(Request $request, string $type, int $id)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', 'string', Rule::in(self::EDITABLE_ROLES)],
        ]);

        $name = $validated['name'] ?? null;
        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;

        if ($type === 'doctor') {
            $doctor = Doctor::where('vet_registeration_id', $clinicId)->find($id);
            if (!$doctor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Doctor not found for this clinic',
                ], 404);
            }

            $targetRole = $validated['role'] ?? ($doctor->staff_role ?? 'doctor');

            if ($targetRole === 'receptionist') {
                $payload = [
                    'vet_registeration_id' => $clinicId,
                    'name' => $name ?? $doctor->doctor_name,
                    'email' => $email ?? $doctor->doctor_email,
                    'phone' => $phone ?? $doctor->doctor_mobile,
                    'role' => 'receptionist',
                    'status' => 'active',
                ];

                if (Schema::hasColumn('receptionists', 'password')) {
                    $payload['password'] = '123456';
                }
                if (Schema::hasColumn('receptionists', 'receptionist_password')) {
                    $payload['receptionist_password'] = '123456';
                }

                $newReceptionist = DB::transaction(function () use ($doctor, $payload) {
                    $created = Receptionist::create($payload);
                    $doctor->delete();
                    return $created;
                });

                return response()->json([
                    'status' => true,
                    'message' => 'Doctor moved to receptionist',
                    'data' => [
                        'id' => $newReceptionist->id,
                        'name' => $newReceptionist->name,
                        'email' => $newReceptionist->email,
                        'phone' => $newReceptionist->phone,
                        'role' => $newReceptionist->role ?? 'receptionist',
                        'type' => 'receptionist',
                    ],
                ]);
            }

            if ($name !== null) {
                $doctor->doctor_name = $name;
            }
            if ($email !== null) {
                $doctor->doctor_email = $email;
            }
            if ($phone !== null) {
                $doctor->doctor_mobile = $phone;
            }
            if (array_key_exists('role', $validated)) {
                $doctor->staff_role = $validated['role'];
            }

            $doctor->save();

            return response()->json([
                'status' => true,
                'message' => 'Doctor updated',
                'data' => [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name,
                    'email' => $doctor->doctor_email,
                    'phone' => $doctor->doctor_mobile,
                    'role' => $doctor->staff_role ?? 'doctor',
                    'type' => 'doctor',
                ],
            ]);
        }

        if ($type === 'receptionist') {
            $receptionist = Receptionist::where('vet_registeration_id', $clinicId)->find($id);
            if (!$receptionist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Receptionist not found for this clinic',
                ], 404);
            }

            $targetRole = $validated['role'] ?? ($receptionist->role ?? 'receptionist');

            if ($targetRole === 'doctor') {
                $payload = [
                    'vet_registeration_id' => $clinicId,
                    'doctor_name' => $name ?? $receptionist->name,
                    'doctor_email' => $email ?? $receptionist->email,
                    'doctor_mobile' => $phone ?? $receptionist->phone,
                ];

                if (Schema::hasColumn('doctors', 'staff_role')) {
                    $payload['staff_role'] = 'doctor';
                }

                $newDoctor = DB::transaction(function () use ($receptionist, $payload) {
                    $created = Doctor::create($payload);
                    $receptionist->delete();
                    return $created;
                });

                return response()->json([
                    'status' => true,
                    'message' => 'Receptionist moved to doctor',
                    'data' => [
                        'id' => $newDoctor->id,
                        'name' => $newDoctor->doctor_name,
                        'email' => $newDoctor->doctor_email,
                        'phone' => $newDoctor->doctor_mobile,
                        'role' => $newDoctor->staff_role ?? 'doctor',
                        'type' => 'doctor',
                    ],
                ]);
            }

            if ($name !== null) {
                $receptionist->name = $name;
            }
            if ($email !== null) {
                $receptionist->email = $email;
            }
            if ($phone !== null) {
                $receptionist->phone = $phone;
            }
            if (array_key_exists('role', $validated)) {
                $receptionist->role = $validated['role'];
            }

            $receptionist->save();

            return response()->json([
                'status' => true,
                'message' => 'Receptionist updated',
                'data' => [
                    'id' => $receptionist->id,
                    'name' => $receptionist->name,
                    'email' => $receptionist->email,
                    'phone' => $receptionist->phone,
                    'role' => $receptionist->role ?? 'receptionist',
                    'type' => 'receptionist',
                ],
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unsupported staff type. Allowed: doctor or receptionist.',
        ], 422);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        if ($type === 'doctor') {
            $doctor = Doctor::where('vet_registeration_id', $clinicId)->find($id);
            if (!$doctor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Doctor not found for this clinic',
                ], 404);
            }

            $doctor->delete();

            return response()->json([
                'status' => true,
                'message' => 'Doctor deleted',
            ]);
        }

        if ($type === 'receptionist') {
            $receptionist = Receptionist::where('vet_registeration_id', $clinicId)->find($id);
            if (!$receptionist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Receptionist not found for this clinic',
                ], 404);
            }

            $receptionist->delete();

            return response()->json([
                'status' => true,
                'message' => 'Receptionist deleted',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unsupported staff type. Allowed: doctor or receptionist.',
        ], 422);
    }
}
